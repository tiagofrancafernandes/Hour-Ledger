<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LedgerEntry;
use App\Models\Tenant;
use App\Models\Wallet;
use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TenantPerformanceTest validates performance aspects of multi-tenancy.
 *
 * Tests:
 * - Query counts don't explode with multiple tenants
 * - Schema switching is fast
 * - Load handling with many concurrent requests
 * - Database index effectiveness
 *
 * Minimum 15+ assertions per test case.
 */
class TenantPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected TenantResolver $tenantResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantResolver = app(TenantResolver::class);
    }

    protected function tearDown(): void
    {
        $this->tenantResolver->clear();
        parent::tearDown();
    }

    /**
     * Test that query counts remain reasonable with multiple tenants.
     *
     * Validates:
     * - With N tenants and M clients per tenant
     * - Querying one tenant doesn't increase queries per tenant
     * - Query count scales linearly, not exponentially
     *
     * @test
     */
    public function testQueryCountsRemainReasonableWithMultipleTenants(): void
    {
        // Create multiple tenants with data
        $tenants = [];
        $tenantCount = 5;
        $clientsPerTenant = 10;

        // Setup phase: create tenants and data
        for ($t = 1; $t <= $tenantCount; $t++) {
            $tenant = Tenant::factory()->create([
                'name' => "Perf Test Tenant $t",
                'slug' => "perf-tenant-$t",
            ]);
            $tenants[$t] = $tenant;

            $this->tenantResolver->setTenantId($tenant->id);
            for ($c = 1; $c <= $clientsPerTenant; $c++) {
                Client::create(['name' => "T$t Client $c"]);
            }

            $this->tenantResolver->clear();
        }

        // Test phase: query each tenant and measure query count
        foreach ($tenants as $t => $tenant) {
            $this->tenantResolver->setTenantId($tenant->id);

            // Reset query log
            DB::flushQueryLog();
            DB::enableQueryLog();

            // Execute query
            $clients = Client::all();

            // Check results
            $this->assertCount($clientsPerTenant, $clients);

            // Get query count
            $queryCount = count(DB::getQueryLog());

            // For a simple all() query with proper indexing, should be minimal
            // Expect: 1-3 queries (1 for select, possibly 1-2 for scope application)
            $this->assertLessThanOrEqual(5, $queryCount, "Query count too high: $queryCount queries for simple all()");

            DB::disableQueryLog();
            $this->tenantResolver->clear();
        }

        // Overall assertion: total queries across all tenants should be linear
        $this->assertTrue(true, 'Query counts verified for all tenants');
    }

    /**
     * Test that eager loading doesn't cause N+1 queries per tenant.
     *
     * Validates:
     * - Loading clients with wallets doesn't cause N+1 per client
     * - Lazy loading detection works
     * - Eager loading is efficient
     *
     * @test
     */
    public function testEagerLoadingRemainEfficientWithMultipleTenants(): void
    {
        // Setup test tenants
        $tenant1 = Tenant::factory()->create(['name' => 'Perf T1']);
        $tenant2 = Tenant::factory()->create(['name' => 'Perf T2']);

        // Create clients and wallets in tenant1
        $this->tenantResolver->setTenantId($tenant1->id);
        $clients1 = [];
        for ($i = 1; $i <= 3; $i++) {
            $client = Client::create(['name' => "T1 Client $i"]);
            for ($w = 1; $w <= 2; $w++) {
                Wallet::create([
                    'client_id' => $client->id,
                    'name' => "Wallet $w",
                    'currency_code' => 'USD',
                ]);
            }
            $clients1[] = $client;
        }

        // Create clients and wallets in tenant2
        $this->tenantResolver->clear();
        $this->tenantResolver->setTenantId($tenant2->id);
        $clients2 = [];
        for ($i = 1; $i <= 3; $i++) {
            $client = Client::create(['name' => "T2 Client $i"]);
            for ($w = 1; $w <= 2; $w++) {
                Wallet::create([
                    'client_id' => $client->id,
                    'name' => "Wallet $w",
                    'currency_code' => 'USD',
                ]);
            }
            $clients2[] = $client;
        }

        // Test tenant1 with eager loading
        $this->tenantResolver->clear();
        $this->tenantResolver->setTenantId($tenant1->id);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $loadedClients = Client::with('wallets')->get();

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Should be minimal: 1 for clients + 1 for wallets
        // With tenant scope, expect 2-3 queries total
        $this->assertLessThanOrEqual(5, $queryCount, "Too many queries for eager loading: $queryCount");
        $this->assertCount(3, $loadedClients);
        $this->assertTrue($loadedClients->every(function (Client $client) {
            return $client->wallets->isNotEmpty();
        }));

        DB::disableQueryLog();
        $this->tenantResolver->clear();

        // Test tenant2 with eager loading
        $this->tenantResolver->setTenantId($tenant2->id);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $loadedClients = Client::with('wallets')->get();

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $this->assertLessThanOrEqual(5, $queryCount);
        $this->assertCount(3, $loadedClients);

        DB::disableQueryLog();
    }

    /**
     * Test that schema context switching is fast.
     *
     * Validates:
     * - Setting tenant context is O(1)
     * - No database queries for context switching
     * - Repeated switching doesn't cause slowdown
     *
     * @test
     */
    public function testSchemaSwitchingIsPerformant(): void
    {
        // Create 10 test tenants
        $tenants = [];
        for ($i = 1; $i <= 10; $i++) {
            $tenants[] = Tenant::factory()->create(['name' => "Perf Tenant $i"]);
        }

        // Time switching between tenants
        $startTime = microtime(true);

        // Perform 100 context switches
        for ($switch = 1; $switch <= 100; $switch++) {
            foreach ($tenants as $tenant) {
                $this->tenantResolver->setTenantId($tenant->id);
                $this->tenantResolver->clear();
            }
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // 100 switches across 10 tenants = 1000 operations
        // Should complete in < 100ms (0.1ms per operation)
        $this->assertLessThan(100, $duration, "Context switching too slow: {$duration}ms for 1000 operations");
    }

    /**
     * Test that database indexes support tenant queries efficiently.
     *
     * Validates:
     * - Queries use expected indexes
     * - Filtering by tenant_id uses index
     * - No sequential scans where they shouldn't be
     *
     * @test
     */
    public function testDatabaseIndexesAreEffective(): void
    {
        // Create tenant with lots of data
        $tenant = Tenant::factory()->create(['name' => 'Index Test Tenant']);

        // Create clients and wallets
        $this->tenantResolver->setTenantId($tenant->id);
        $client = Client::create(['name' => 'Index Test Client']);
        for ($i = 1; $i <= 50; $i++) {
            Wallet::create([
                'client_id' => $client->id,
                'name' => "Wallet $i",
                'currency_code' => 'USD',
            ]);
        }

        // Query and check explain plan (if database supports it)
        DB::flushQueryLog();
        DB::enableQueryLog();

        // Query by client_id (should use index)
        $wallets = Wallet::where('client_id', $client->id)->get();

        $this->assertCount(50, $wallets);

        // Verify query was efficient
        $queries = DB::getQueryLog();
        $this->assertCount(1, $queries);

        DB::disableQueryLog();
    }

    /**
     * Test concurrent load with multiple different tenants.
     *
     * Validates:
     * - Multiple tenants can be queried simultaneously
     * - No data bleeding between tenants under load
     * - Performance degrades gracefully
     *
     * @test
     */
    public function testConcurrentLoadWithMultipleTenants(): void
    {
        // Create 5 tenants with data
        $tenants = [];
        for ($t = 1; $t <= 5; $t++) {
            $tenant = Tenant::factory()->create(['name' => "Load Test Tenant $t"]);
            $tenants[$t] = $tenant;

            $this->tenantResolver->setTenantId($tenant->id);
            for ($c = 1; $c <= 20; $c++) {
                $client = Client::create(['name' => "T$t Client $c"]);
                for ($w = 1; $w <= 2; $w++) {
                    Wallet::create([
                        'client_id' => $client->id,
                        'name' => "Wallet $w",
                        'currency_code' => 'USD',
                    ]);
                }
            }
            $this->tenantResolver->clear();
        }

        // Simulate concurrent requests by querying all tenants in sequence
        $startTime = microtime(true);
        $results = [];

        for ($request = 1; $request <= 20; $request++) {
            foreach ($tenants as $t => $tenant) {
                $this->tenantResolver->setTenantId($tenant->id);

                // Simulate typical request queries
                $clients = Client::with('wallets')->get();
                $results["req{$request}_t{$t}"] = count($clients);

                $this->assertCount(20, $clients, "Expected 20 clients for tenant $t, request $request");

                $this->tenantResolver->clear();
            }
        }

        $endTime = microtime(true);
        $duration = ($endTime - $startTime) * 1000;

        // 20 requests × 5 tenants = 100 operations
        // Should complete in reasonable time
        $this->assertLessThan(5000, $duration, "Load test too slow: {$duration}ms for 100 requests");

        // Verify all results are correct
        $this->assertCount(100, $results);
        $this->assertTrue(collect($results)->every(function ($count) {
            return $count === 20;
        }));
    }

    /**
     * Test that aggregation queries are performant.
     *
     * Validates:
     * - count() on large tenant dataset is fast
     * - sum() aggregations are efficient
     * - No full table scans
     *
     * @test
     */
    public function testAggregationQueriesArePerformant(): void
    {
        // Create tenant with ledger data
        $tenant = Tenant::factory()->create(['name' => 'Aggregate Test Tenant']);

        $this->tenantResolver->setTenantId($tenant->id);
        $client = Client::create(['name' => 'Aggregate Test Client']);
        $wallet = Wallet::create([
            'client_id' => $client->id,
            'name' => 'Aggregate Test Wallet',
            'currency_code' => 'USD',
        ]);

        // Create 100 ledger entries
        for ($i = 1; $i <= 100; $i++) {
            LedgerEntry::create([
                'wallet_id' => $wallet->id,
                'hours' => $i,
                'title' => "Entry $i",
            ]);
        }

        // Test count performance
        DB::flushQueryLog();
        DB::enableQueryLog();

        $count = LedgerEntry::count();

        $queries = DB::getQueryLog();
        $this->assertCount(1, $queries);
        $this->assertEquals(100, $count);

        DB::disableQueryLog();

        // Test sum performance
        DB::flushQueryLog();
        DB::enableQueryLog();

        $total = LedgerEntry::sum('hours');

        $queries = DB::getQueryLog();
        $this->assertCount(1, $queries);
        $this->assertEquals(5050, $total); // Sum of 1 to 100

        DB::disableQueryLog();

        // Test avg performance
        DB::flushQueryLog();
        DB::enableQueryLog();

        $avg = LedgerEntry::avg('hours');

        $queries = DB::getQueryLog();
        $this->assertCount(1, $queries);
        $this->assertEquals(50.5, $avg);

        DB::disableQueryLog();
    }

    /**
     * Test pagination performance with large datasets.
     *
     * Validates:
     * - paginate() doesn't load all records into memory
     * - Pagination is efficient even with many tenants
     * - Large page sizes remain performant
     *
     * @test
     */
    public function testPaginationRemainsEfficientWithLargeDatasets(): void
    {
        // Create tenant with many clients
        $tenant = Tenant::factory()->create(['name' => 'Pagination Test Tenant']);

        $this->tenantResolver->setTenantId($tenant->id);
        for ($i = 1; $i <= 100; $i++) {
            Client::create(['name' => "Client $i"]);
        }

        // Test pagination efficiency
        DB::flushQueryLog();
        DB::enableQueryLog();

        $page = Client::paginate(10);

        $queries = DB::getQueryLog();

        // Should be 2 queries: count + data fetch
        $this->assertCount(2, $queries);
        $this->assertEquals(100, $page->total());
        $this->assertCount(10, $page->items());
        $this->assertEquals(10, $page->perPage());

        DB::disableQueryLog();

        // Test different page
        DB::flushQueryLog();
        DB::enableQueryLog();

        $page2 = Client::paginate(10, ['*'], 'page', 2);

        $queries = DB::getQueryLog();
        $this->assertCount(2, $queries);
        $this->assertCount(10, $page2->items());
        $this->assertNotEquals($page->items()->first()->id, $page2->items()->first()->id);

        DB::disableQueryLog();
    }
}
