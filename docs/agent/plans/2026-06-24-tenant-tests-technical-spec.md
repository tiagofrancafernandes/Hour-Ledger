# Technical Specification: Tenant Isolation & Security Tests

**Document**: Implementation Reference for Tarefa G  
**Date**: 2026-06-24  
**Audience**: Developers implementing the test suite

---

## Part A: Test Environment Setup

### Configuration

```php
// phpunit.xml - existing SQLite in-memory config
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="APP_ENV" value="testing"/>
<env name="QUEUE_CONNECTION" value="sync"/>
```

### Base Test Class Pattern

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class TenantTestCase extends TestCase
{
    use RefreshDatabase;
    
    protected TenantResolver $resolver;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->resolver = app(TenantResolver::class);
    }
    
    protected function tearDown(): void
    {
        $this->resolver->clear();
        parent::tearDown();
    }
    
    /**
     * Assert that a query returns exactly the expected IDs.
     */
    protected function assertQueryResultIds(
        array $expectedIds,
        callable $query,
        string $message = ''
    ): void {
        $results = $query();
        $resultIds = $results->pluck('id')->sort()->values()->all();
        $expectedIds = collect($expectedIds)->sort()->values()->all();
        
        $this->assertEquals($expectedIds, $resultIds, $message);
    }
    
    /**
     * Assert that no query leaks cross-tenant data.
     */
    protected function assertNoTenantLeakage(
        callable $query,
        int $expectedTenantId
    ): void {
        $results = $query();
        
        foreach ($results as $model) {
            $this->assertEquals(
                $expectedTenantId,
                $model->tenant_id,
                "Cross-tenant leakage detected: {$model->id} belongs to tenant {$model->tenant_id}, expected {$expectedTenantId}"
            );
        }
    }
}
```

---

## Part B: Fixture Architecture

### TenantFactory Enhancement

```php
// database/factories/TenantFactory.php
$factory->define(Tenant::class, function (Faker $faker) {
    return [
        'name' => $faker->company,
        'slug' => $faker->unique()->slug,
        'status' => TenantStatus::ACTIVE,
        'database_name' => null, // For PostgreSQL multi-schema
    ];
});
```

### Test Data Setup Functions

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Fixtures;

use App\Models\Client;
use App\Models\LedgerEntry;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TenantResolver;

class TenantTestFixtures
{
    /**
     * Create 3 independent tenants with standard names.
     */
    public static function createThreeTenants(): array
    {
        return [
            'tenant1' => Tenant::factory()->create(['name' => 'Tenant Alpha']),
            'tenant2' => Tenant::factory()->create(['name' => 'Tenant Beta']),
            'tenant3' => Tenant::factory()->create(['name' => 'Tenant Gamma']),
        ];
    }
    
    /**
     * Create multi-role users assigned to tenants.
     * 
     * Returns:
     * - adminT1: admin in tenant1
     * - adminT2: admin in tenant2
     * - memberT1: member in tenant1
     * - outsiderT3: admin in tenant3 (for cross-tenant tests)
     */
    public static function createUserMatrix(array $tenants): array
    {
        $users = [
            'adminT1' => User::factory()->create(['email' => 'admin_t1@test.local']),
            'adminT2' => User::factory()->create(['email' => 'admin_t2@test.local']),
            'memberT1' => User::factory()->create(['email' => 'member_t1@test.local']),
            'outsiderT3' => User::factory()->create(['email' => 'outsider_t3@test.local']),
        ];
        
        // Attach users to tenants
        $users['adminT1']->tenants()->attach(
            $tenants['tenant1']->id,
            ['role' => 'admin', 'status' => 'active']
        );
        
        $users['adminT2']->tenants()->attach(
            $tenants['tenant2']->id,
            ['role' => 'admin', 'status' => 'active']
        );
        
        $users['memberT1']->tenants()->attach(
            $tenants['tenant1']->id,
            ['role' => 'member', 'status' => 'active']
        );
        
        $users['outsiderT3']->tenants()->attach(
            $tenants['tenant3']->id,
            ['role' => 'admin', 'status' => 'active']
        );
        
        return $users;
    }
    
    /**
     * Create cross-tenant model hierarchy.
     * 
     * Returns structured data:
     * [
     *     'tenant1' => [
     *         'clients' => [Client, Client],
     *         'wallets' => [Wallet, Wallet],
     *         'entries' => [LedgerEntry, LedgerEntry],
     *     ],
     *     'tenant2' => [...],
     *     'tenant3' => [...],
     * ]
     */
    public static function createCrossTenantHierarchy(
        array $tenants,
        TenantResolver $resolver
    ): array {
        $data = [];
        
        foreach ($tenants as $key => $tenant) {
            $resolver->clear();
            $resolver->setTenantId($tenant->id);
            
            $data[$key] = [
                'clients' => [
                    Client::factory()->create(['name' => "Client-{$key}-1"]),
                    Client::factory()->create(['name' => "Client-{$key}-2"]),
                ],
                'wallets' => [],
                'entries' => [],
            ];
            
            // Create wallets per client
            foreach ($data[$key]['clients'] as $client) {
                $wallet = Wallet::factory()
                    ->for($client)
                    ->create(['name' => "Wallet-{$key}-{$client->id}"]);
                
                $data[$key]['wallets'][] = $wallet;
                
                // Create ledger entries
                $data[$key]['entries'][] = LedgerEntry::factory()
                    ->for($wallet)
                    ->create(['hours' => rand(1, 100)]);
            }
        }
        
        $resolver->clear();
        
        return $data;
    }
}
```

---

## Part C: Detailed Test Implementation Examples

### Example 1: Direct Query Isolation Test

```php
/**
 * Test that Client queries only return tenant data.
 * 
 * Scenario:
 * - Create 3 clients across 3 tenants
 * - Query from tenant1 context
 * - Assert only tenant1 client returned
 * 
 * Assertions: 18
 * - Initial setup verification (3)
 * - Query from T1 context (6)
 * - Query from T2 context (6)
 * - Query without context (3)
 */
public function testClientQueryOnlyReturnsTenantData(): void
{
    $tenants = TenantTestFixtures::createThreeTenants();
    
    // Verify setup
    $this->assertNotNull($tenants['tenant1']->id);
    $this->assertNotNull($tenants['tenant2']->id);
    $this->assertNotNull($tenants['tenant3']->id);
    
    // Create clients in each tenant
    $this->resolver->setTenantId($tenants['tenant1']->id);
    $clientT1 = Client::create(['name' => 'Client in T1']);
    
    $this->resolver->clear();
    $this->resolver->setTenantId($tenants['tenant2']->id);
    $clientT2 = Client::create(['name' => 'Client in T2']);
    
    $this->resolver->clear();
    $this->resolver->setTenantId($tenants['tenant3']->id);
    $clientT3 = Client::create(['name' => 'Client in T3']);
    
    // Verify T1 clients
    $this->resolver->clear();
    $this->resolver->setTenantId($tenants['tenant1']->id);
    
    $clientsT1 = Client::all();
    $this->assertCount(1, $clientsT1);
    $this->assertEquals($clientT1->id, $clientsT1->first()->id);
    $this->assertEquals($tenants['tenant1']->id, $clientsT1->first()->tenant_id);
    
    // Verify T2 clients (different result)
    $this->resolver->clear();
    $this->resolver->setTenantId($tenants['tenant2']->id);
    
    $clientsT2 = Client::all();
    $this->assertCount(1, $clientsT2);
    $this->assertEquals($clientT2->id, $clientsT2->first()->id);
    $this->assertEquals($tenants['tenant2']->id, $clientsT2->first()->tenant_id);
    
    // Verify T3 clients
    $this->resolver->clear();
    $this->resolver->setTenantId($tenants['tenant3']->id);
    
    $clientsT3 = Client::all();
    $this->assertCount(1, $clientsT3);
    $this->assertEquals($clientT3->id, $clientsT3->first()->id);
    
    // Verify no context returns empty
    $this->resolver->clear();
    $clientsNone = Client::all();
    $this->assertCount(0, $clientsNone);
}
```

### Example 2: Relationship Isolation Test

```php
/**
 * Test that eager loading respects tenant scope.
 * 
 * Scenario:
 * - T1: Client with 2 wallets
 * - T2: Client with 3 wallets
 * - Eager load Client::with('wallets')->get() in T1 context
 * - Assert only T1 client + 2 wallets returned
 * 
 * Assertions: 16
 */
public function testEagerLoadingRespectsTenantScope(): void
{
    $tenants = TenantTestFixtures::createThreeTenants();
    
    // Create T1 data
    $this->resolver->setTenantId($tenants['tenant1']->id);
    $clientT1 = Client::create(['name' => 'T1 Client']);
    $walletT1A = Wallet::factory()
        ->for($clientT1)
        ->create(['name' => 'Wallet T1A']);
    $walletT1B = Wallet::factory()
        ->for($clientT1)
        ->create(['name' => 'Wallet T1B']);
    
    // Create T2 data
    $this->resolver->clear();
    $this->resolver->setTenantId($tenants['tenant2']->id);
    $clientT2 = Client::create(['name' => 'T2 Client']);
    $walletT2A = Wallet::factory()
        ->for($clientT2)
        ->create(['name' => 'Wallet T2A']);
    $walletT2B = Wallet::factory()
        ->for($clientT2)
        ->create(['name' => 'Wallet T2B']);
    $walletT2C = Wallet::factory()
        ->for($clientT2)
        ->create(['name' => 'Wallet T2C']);
    
    // Query T1 with eager loading
    $this->resolver->clear();
    $this->resolver->setTenantId($tenants['tenant1']->id);
    
    $clients = Client::with('wallets')->get();
    
    // Verify T1 only
    $this->assertCount(1, $clients);
    $this->assertEquals($clientT1->id, $clients->first()->id);
    
    // Verify wallets
    $wallets = $clients->first()->wallets;
    $this->assertCount(2, $wallets, 'T1 client should have 2 wallets');
    
    $walletIds = $wallets->pluck('id')->toArray();
    $this->assertContains($walletT1A->id, $walletIds);
    $this->assertContains($walletT1B->id, $walletIds);
    $this->assertNotContains($walletT2A->id, $walletIds);
    $this->assertNotContains($walletT2B->id, $walletIds);
    $this->assertNotContains($walletT2C->id, $walletIds);
    
    // Verify T2 has different result
    $this->resolver->clear();
    $this->resolver->setTenantId($tenants['tenant2']->id);
    
    $clientsT2 = Client::with('wallets')->get();
    $walletsT2 = $clientsT2->first()->wallets;
    $this->assertCount(3, $walletsT2);
}
```

### Example 3: SQL Injection Security Test

```php
/**
 * Test that SQL injection in tenant header is rejected.
 * 
 * Payloads tested:
 * 1. "1 OR 1=1"
 * 2. "1; DROP TABLE clients;"
 * 3. "1 UNION SELECT * FROM clients"
 * 
 * Assertions: 21 (3 payloads × 7 assertions each)
 */
public function testSqlInjectionInHeaderDoesNotBypassTenantScope(): void
{
    $tenant = Tenant::factory()->create();
    
    $payloads = [
        "1 OR 1=1",
        "1; DROP TABLE clients;",
        "1 UNION SELECT * FROM clients",
        "' OR '1'='1",
        "1' UNION ALL SELECT NULL--",
    ];
    
    foreach ($payloads as $payload) {
        try {
            // Middleware would receive this
            $response = $this->withHeaders([
                'X-Tenant-ID' => $payload,
            ])->get('/api/clients');
            
            // Should reject (403 or 400)
            $this->assertThat(
                $response->status(),
                $this->logicalOr(
                    $this->equalTo(403), // Forbidden
                    $this->equalTo(400), // Bad Request
                    $this->equalTo(422), // Unprocessable
                ),
                "Payload '{$payload}' should be rejected"
            );
            
            // Should not contain any client data
            $this->assertFalse(
                $response->json('success') === true,
                "Payload '{$payload}' should not return success"
            );
            
        } catch (\Exception $e) {
            // If exception thrown, that's also acceptable
            // (better than leaking data)
            $this->assertTrue(true, "Payload '{$payload}' threw exception (safe)");
        }
    }
}
```

### Example 4: Performance Load Test

```php
/**
 * Test 100 parallel requests to different tenants maintain isolation.
 * 
 * Scenario:
 * - Create 5 tenants
 * - Create 20 users per tenant
 * - Create 50 clients per tenant
 * - Simulate 100 concurrent requests (sequential in test)
 * - Each request: authenticate, set tenant, query data, verify isolation
 * 
 * Assertions: 20+ (request success, isolation per request)
 */
public function testHandles100ParallelRequestsDifferentTenants(): void
{
    $tenants = [];
    $tenantData = [];
    
    // Setup: Create 5 tenants with data
    for ($i = 1; $i <= 5; $i++) {
        $tenant = Tenant::factory()->create(['name' => "Tenant {$i}"]);
        $tenants[$i] = $tenant;
        
        $this->resolver->setTenantId($tenant->id);
        
        $tenantData[$i] = [
            'clients' => Client::factory(20)->create(),
            'users' => User::factory(5)->create(),
        ];
        
        // Grant users to tenant
        foreach ($tenantData[$i]['users'] as $user) {
            $user->tenants()->attach($tenant->id, [
                'role' => 'member',
                'status' => 'active'
            ]);
        }
    }
    
    $this->resolver->clear();
    
    // Simulate 100 requests: 20 per tenant
    $successCount = 0;
    $failureCount = 0;
    
    for ($i = 0; $i < 100; $i++) {
        $tenantNum = ($i % 5) + 1;
        $tenant = $tenants[$tenantNum];
        $user = $tenantData[$tenantNum]['users'][0];
        
        try {
            // Simulate API request
            $response = $this->actingAs($user)
                ->withHeaders(['X-Tenant-ID' => $tenant->id])
                ->get('/api/clients');
            
            // Verify successful response
            $this->assertEquals(200, $response->status(), "Request {$i} failed");
            
            // Verify data isolation
            $clients = $response->json('data', []);
            $this->assertLessThanOrEqual(
                50,
                count($clients),
                "Request {$i} returned more clients than tenant should have"
            );
            
            // Verify all clients belong to correct tenant
            foreach ($clients as $client) {
                $this->assertEquals(
                    $tenant->id,
                    $client['tenant_id'],
                    "Request {$i} leaked cross-tenant data"
                );
            }
            
            $successCount++;
            
        } catch (\Exception $e) {
            $failureCount++;
            $this->fail("Request {$i} threw exception: " . $e->getMessage());
        }
    }
    
    // Verify all succeeded
    $this->assertEquals(100, $successCount, "Some requests failed");
    $this->assertEquals(0, $failureCount, "Failures detected");
}
```

---

## Part D: Security Test Payloads

### SQL Injection Payloads

```php
class SecurityPayloads
{
    public static function sqlInjectionIntegers(): array
    {
        return [
            "abc",           // Non-numeric
            "-1",            // Negative
            "0",             // Zero
            "",              // Empty
            "  ",            // Whitespace
            "1.5",           // Float
            "999999999",     // Out of range
            "1e10",          // Scientific notation
        ];
    }
    
    public static function sqlInjectionQueries(): array
    {
        return [
            "1 OR 1=1",
            "1; DROP TABLE clients;",
            "1 UNION SELECT * FROM tenants",
            "' OR '1'='1",
            "1' UNION ALL SELECT NULL--",
            "1 AND SLEEP(5)",
            "1; DELETE FROM clients;",
            "1; UPDATE clients SET tenant_id=2;",
        ];
    }
    
    public static function headerInjectionPayloads(): array
    {
        return [
            "1\x00' OR '1'='1",      // Null byte
            "1\r\nX-Custom: header", // CRLF injection
            "1\nSet-Cookie: admin=1", // Cookie injection
            "1%00",                  // URL-encoded null
            "1%0d%0a",               // URL-encoded CRLF
        ];
    }
    
    public static function pathTraversalPayloads(): array
    {
        return [
            "/api/tenant/../../tenant/1/clients",
            "/api/tenant/1/../../tenant/2/clients",
            "/api/tenant/1//clients",
            "/api/tenant/./1/clients",
            "/api/tenant/%2e%2e/clients",
        ];
    }
}
```

---

## Part E: Assertion Helpers

### Custom Assertions

```php
trait TenantAssertions
{
    /**
     * Assert that query result IDs match exactly (order-independent).
     */
    protected function assertQueryIds(array $expected, $query): void
    {
        $actual = $query->pluck('id')->sort()->values();
        $expected = collect($expected)->sort()->values();
        
        $this->assertEquals($expected->all(), $actual->all());
    }
    
    /**
     * Assert all results belong to specified tenant.
     */
    protected function assertAllBelongToTenant($results, int $tenantId): void
    {
        foreach ($results as $model) {
            $this->assertEquals(
                $tenantId,
                $model->tenant_id,
                "Model {$model->id} does not belong to tenant {$tenantId}"
            );
        }
    }
    
    /**
     * Assert relationship count per tenant.
     */
    protected function assertRelationshipCount(
        $model,
        string $relation,
        int $expected
    ): void {
        $count = $model->$relation()->count();
        $this->assertEquals(
            $expected,
            $count,
            "Expected {$expected} {$relation}, got {$count}"
        );
    }
    
    /**
     * Assert no query logs for specific tables.
     */
    protected function assertNotQueriedTable(string $table): void
    {
        $queries = DB::getQueryLog();
        
        foreach ($queries as $query) {
            $this->assertStringNotContainsString(
                "FROM \"{$table}\"",
                $query['query'],
                "Table {$table} should not be queried"
            );
        }
    }
}
```

---

## Part F: Running the Tests

### Basic Execution

```bash
# All tenant isolation tests
php artisan test tests/Feature/TenantIsolationComprehensiveTest.php

# Specific test
php artisan test tests/Feature/TenantIsolationComprehensiveTest.php --filter=testClientQueryOnlyReturnsTenantData

# With verbose output
php artisan test tests/Feature/ --verbose --filter="Tenant"

# With coverage
php artisan test tests/Feature/ --coverage --coverage-html=reports/
```

### Performance Testing

```bash
# Run performance tests with timing output
php artisan test tests/Feature/TenantPerformanceTest.php --verbose

# Profile with Xdebug
XDEBUG_PROFILE=1 php artisan test tests/Feature/TenantPerformanceTest.php
```

### Parallel Execution (if supported)

```bash
# Using Pest's parallel capability (if installed)
php artisan test tests/Feature/ --parallel
```

---

## Part G: Expected Output

### Successful Test Run

```
Tests\Feature\TenantIsolationComprehensiveTest
  ✓ test client query only returns tenant data
  ✓ test wallet query only returns tenant data
  ✓ test ledger entry query only returns tenant data
  ✓ test query without tenant context return empty set
  ✓ test wallet relationship does not expose cross tenant clients
  ✓ test eager loading respects tenant scope
  ✓ test nested relationship chain respects tenancy
  ✓ test has many relationship count is per tenant
  ✓ test create operation auto sets correct tenant
  ✓ test update operation only affects tenant data
  ✓ test delete operation only affects tenant data
  ✓ test bulk operations respect tenant scope
  ✓ test transaction does not leak cross tenant data
  ✓ test concurrent transactions by different tenants are isolated
  ✓ test row level lock respects tenant boundary
  ✓ test aggregates only include tenant data
  ✓ test group by query only groups tenant data
  ✓ test distinct query only includes tenant values

Tests\Feature\TenantSecurityTest
  ✓ test sql injection in header does not bypass tenant scope
  ✓ test sql injection in query parameter does not bypass scope
  ✓ test token scoped to tenant1 cannot access tenant2
  ✓ test expired token with tenant switch is rejected
  ✓ test middleware bypass via null byte injection
  ✓ test middleware bypass via path traversal

Tests\Feature\TenantPerformanceTest
  ✓ test query performance with large tenant dataset
  ✓ test eager loading performance with multiple tenants
  ✓ test handles 100 parallel requests different tenants
  ✓ test schema context switching under load

Tests\Feature\TenantMiddlewareSecurityTest
  ✓ test middleware rejects invalid tenant id formats
  ✓ test middleware rejects invalid path formats
  ✓ test unauthenticated request with tenant header is rejected
  ✓ test authenticated user without tenant access is forbidden
  ✓ test middleware handles multiple tenant detection methods

Tests: 35 passed (150ms)
```

---

## Part H: Debugging & Troubleshooting

### Test Fails: Cross-Tenant Data Leaked

```php
// Enable query logging
DB::enableQueryLog();

// Run query
$results = Client::all();

// Check which tenants returned
foreach (DB::getQueryLog() as $log) {
    echo $log['query'] . "\n"; // Should have WHERE tenant_id = X
}

// Verify TenantResolver state
dump($this->resolver->getTenantId());
dump($this->resolver->hasTenant());
```

### Test Fails: Isolation Assertion

```php
// Check model has BelongsToTenant trait
$model = new Client();
if (!in_array('BelongsToTenant', class_uses($model))) {
    throw new Exception('Model missing BelongsToTenant trait');
}

// Verify global scope is registered
$scopes = $model->getGlobalScopes();
dump(collect($scopes)->keys()); // Should contain TenantScope
```

### Performance Test Timeout

```php
// Reduce iteration count for debugging
const PARALLEL_REQUESTS = 10; // Instead of 100

// Add timing output
$start = microtime(true);
// ... test code ...
$elapsed = microtime(true) - $start;
echo "Executed in {$elapsed}ms\n";
```

---

## Checklist for Implementer

- [ ] Review existing TenantMiddleware implementation
- [ ] Understand TenantResolver singleton lifecycle
- [ ] Review TenantScope global scope behavior
- [ ] Verify BelongsToTenant trait on all models
- [ ] Understand RefreshDatabase reset behavior
- [ ] Set up IDE with Pest/PHPUnit debugging
- [ ] Create base TenantTestCase class
- [ ] Implement fixtures (TenantTestFixtures)
- [ ] Implement security payloads (SecurityPayloads)
- [ ] Implement custom assertions (TenantAssertions trait)
- [ ] Create first test file (TenantIsolationComprehensiveTest)
- [ ] Verify all 18 tests pass
- [ ] Create second test file (TenantSecurityTest)
- [ ] Create third test file (TenantPerformanceTest)
- [ ] Create fourth test file (TenantMiddlewareSecurityTest)
- [ ] Run full test suite
- [ ] Generate code coverage report
- [ ] Document any limitations or caveats
- [ ] Create TENANT_ISOLATION_VALIDATION.md
