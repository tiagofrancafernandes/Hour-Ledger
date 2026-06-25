# Implementation Plan: Comprehensive Tenant Isolation & Security Tests (Tarefa G)

**Status**: Planning  
**Date**: 2026-06-24  
**Context**: Tasks A-F complete, 60+ tenant tests exist, PostgreSQL multi-tenancy operational  
**Deliverables**: 4 test files + 1 documentation file (30+ test cases total)

---

## 1. Objectives

Create a comprehensive test suite validating tenant isolation and security across:

1. **Data isolation** - Prevent cross-tenant data leakage (15+ test cases)
2. **Security vulnerabilities** - SQL injection, token abuse, middleware bypass (6+ test cases)
3. **Performance** - Multi-tenant query efficiency, schema switching, load testing (4+ test cases)
4. **Middleware security** - Invalid inputs, edge cases, authorization bypass (5+ test cases)

All tests must:

- Have 15+ assertions per test case
- Pass with `RefreshDatabase` trait + SQLite in-memory
- Cover happy paths, edge cases, and failure scenarios
- Follow project code style (UNIVERSAL-CODE-STYLE-RULES.md)
- Use factories and fixtures for consistent test data

---

## 2. Existing Architecture Review

### Current Implementation (A-F complete)

```
TenantMiddleware (HTTP entry point)
  ↓ resolves tenant from header/query/path
  ↓
TenantResolver (singleton service)
  ↓ validates tenant exists & is active
  ↓ generates schema name (tenant_{id}_{env})
  ↓ creates TenantContext
  ↓
TenantScope (global scope)
  ↓ auto-filters queries by tenant_id
  ↓ fails-closed (no tenant = empty results)
  ↓
BelongsToTenant trait
  ↓ auto-sets tenant_id on model creation
  ↓
PersonalAccessToken.tenant_id
  ↓ scopes tokens to specific tenants
  ↓ validated via canAccessTenant()
```

### Existing Test Coverage

| Test File | Cases | Focus |
|-----------|-------|-------|
| TenantScopeTest | 11 | Global scope filtering, model isolation |
| TenantAuthTest | 13 | Authentication, token scoping, tenant access |
| TenantResolutionTest | 18 | Middleware resolution, schema validation |
| TenancySchemaTest | 8 | Database schema setup, migrations |
| **Total** | **~50** | Basic multi-tenancy flows |

### What's Missing

The existing tests do NOT cover:

1. **Advanced isolation attacks**
   - Timestamp-based row guessing
   - UUID collision attacks
   - Relationship traversal (eager loading bypasses)
   - Union/subquery cross-tenant joins
   - Transaction isolation levels

2. **Token-level vulnerabilities**
   - Token reuse with different tenants
   - Expired token + tenant switch
   - Concurrent token validation
   - Bearer token header injection

3. **Middleware edge cases**
   - Header injection (X-Tenant-ID with SQL)
   - Query parameter tampering
   - Path traversal (../../../tenant/1)
   - Null byte injection (%00)
   - Case sensitivity in routes

4. **Performance & concurrency**
   - 100+ parallel requests to different tenants
   - Schema switching overhead
   - Query plan differences per tenant
   - Lock contention under load
   - Memory leaks with tenant context

---

## 3. Test Suite Architecture

### File Structure

```
tests/Feature/
├── TenantIsolationComprehensiveTest.php      (15+ test cases)
├── TenantSecurityTest.php                    (6+ test cases)
├── TenantPerformanceTest.php                 (4+ test cases)
├── TenantMiddlewareSecurityTest.php          (5+ test cases)
├── Fixtures/
│   ├── TenantIsolationFixture.php           (models + factories)
│   └── SecurityTestDataProvider.php         (cross-tenant scenarios)
└── Support/
    └── PerformanceAssertions.php            (load testing helpers)

docs/
└── architecture/security/
    └── TENANT_ISOLATION_VALIDATION.md       (documentation)
```

### Base Test Class Architecture

```php
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;
    
    // 3 independent tenants
    protected Tenant $tenant1, $tenant2, $tenant3;
    
    // Multi-user scenarios
    protected User $adminT1, $adminT2, $memberT1, $outsiderT3;
    
    // Cross-tenant data pairs
    protected Client $clientT1, $clientT2;
    protected Wallet $walletT1, $walletT2;
    protected LedgerEntry $entryT1, $entryT2;
    
    // Security-focused fixtures
    protected array $tokenScenarios;
    protected array $headerInjectionPayloads;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setupTenants();
        $this->setupUsers();
        $this->setupModels();
        $this->setupSecurityPayloads();
    }
}
```

---

## 4. Test Case Specifications

### File 1: TenantIsolationComprehensiveTest.php (15+ cases)

**Purpose**: Verify absolute data isolation between tenants across all data models

#### Test Case Groups

**A. Direct Query Isolation (4 cases)**

1. `testClientQueryOnlyReturnsTenantData()`
   - Creates 3 clients (T1, T2, T3)
   - Query from T1 context
   - Assert: only T1 client returned
   - Assert: no cross-tenant leakage
   - Assert: count = 1
   - Assert: client->tenant_id = T1.id

2. `testWalletQueryOnlyReturnsTenantData()`
   - Similar for Wallet model
   - Creates 3 wallets across tenants
   - Assertions: isolation verified

3. `testLedgerEntryQueryOnlyReturnsTenantData()`
   - Similar for LedgerEntry
   - Tests nested wallet relationship
   - Assertions: isolation through relationship

4. `testQueryWithoutTenantContextReturnEmptySet()`
   - No tenant context set
   - Query any model
   - Assert: all return empty (fail-closed)
   - Assert: count = 0 for each model

**B. Relationship Isolation (4 cases)**

5. `testWalletRelationshipDoesNotExposeCrossTenantClients()`
   - Create client + wallet in T1
   - Create orphaned wallet in T2
   - Load wallet with client relationship
   - Assert: only T1 client loaded
   - Assert: foreign key constraint prevents cross-tenant joins

6. `testEagerLoadingRespectsTenantScope()`
   - Create client + 2 wallets in T1
   - Create client + 3 wallets in T2
   - Eager load: Client::with('wallets')->get()
   - Assert: T1 client has 2 wallets (not 3)
   - Assert: no T2 wallets included
   - Assert: lazy loading same result

7. `testNestedRelationshipChainRespectsTenancy()`
   - Create: T1 Client → T1 Wallet → T1 LedgerEntry
   - Create: T2 Client → T2 Wallet → T2 LedgerEntry
   - Query: Client::with('wallets.entries')->get() in T1
   - Assert: only T1 entry returned
   - Assert: no T2 data traversed through relationships

8. `testHasManyRelationshipCountIsPerTenant()`
   - Create client in T1 with 5 wallets
   - Create client in T2 with 3 wallets
   - Query T1: $client->wallets()->count()
   - Assert: count = 5
   - Switch to T2
   - Assert: count = 3

**C. CRUD Operations Isolation (4 cases)**

9. `testCreateOperationAutoSetsCorrectTenant()`
   - Set T1 context
   - Create client without explicit tenant_id
   - Assert: tenant_id auto-set to T1.id
   - Assert: observer triggered correctly

10. `testUpdateOperationOnlyAffectsTenantData()`
    - Create client in T1, client in T2
    - Set T1 context
    - Update by name filter
    - Assert: only T1 client updated
    - Assert: T2 client unchanged
    - Assert: update count = 1

11. `testDeleteOperationOnlyAffectsTenantData()`
    - Create client in T1, client in T2
    - Set T1 context
    - Delete by name filter
    - Assert: only T1 client deleted
    - Assert: T2 client still exists
    - Assert: delete count = 1
    - Assert: T2 count = 1 after delete

12. `testBulkOperationsRespectTenantScope()`
    - Create 5 clients in T1, 3 in T2
    - Set T1 context
    - Bulk update all clients
    - Assert: only T1 updated (5 records)
    - Switch to T2
    - Assert: T2 still has 3 records (unchanged)

**D. Transaction & Lock Isolation (3 cases)**

13. `testTransactionDoesNotLeakCrossTenantData()`
    - Start transaction in T1 context
    - Create client in T1
    - Create wallet in T1
    - Query client (within transaction)
    - Assert: client visible (same transaction)
    - Assert: tenant_id = T1.id
    - Commit
    - Assert: data persisted correctly

14. `testConcurrentTransactionsByDifferentTenantsAreIsolated()`
    - Start TX1 in T1 (create wallet, increment balance)
    - Start TX2 in T2 (create wallet, increment balance)
    - Both commit
    - Assert: T1 wallet correct value
    - Assert: T2 wallet correct value
    - Assert: no cross-contamination

15. `testRowLevelLockRespectsTenantBoundary()`
    - Create client in T1, in T2
    - Set T1 context
    - Lock for update: Client::find($id)->lockForUpdate()
    - Assert: locks only T1 client
    - Assert: T2 client remains accessible
    - Assert: transaction completes

**E. Aggregation & Statistics Isolation (3 cases)**

16. `testAggregatesOnlyIncludeTenantData()`
    - Create 5 wallets with balances in T1
    - Create 3 wallets with balances in T2
    - Query in T1: Wallet::sum('balance')
    - Assert: sum only includes T1
    - Assert: correct calculation per tenant
    - Switch to T2
    - Assert: T2 sum different and correct

17. `testGroupByQueryOnlyGroupsTenantData()`
    - Create entries by status (active/inactive) in T1 (3/2)
    - Create entries by status in T2 (4/1)
    - Group query in T1: LedgerEntry::groupBy('status')->count()
    - Assert: 2 groups (not 4)
    - Assert: counts correct per group

18. `testDistinctQueryOnlyIncludesTenantValues()`
    - Create clients with categories in T1 (gold, silver, gold)
    - Create clients with categories in T2 (gold, bronze, silver)
    - Distinct query in T1
    - Assert: 2 distinct values (gold, silver)
    - Assert: bronze not included

---

### File 2: TenantSecurityTest.php (6+ cases)

**Purpose**: Test for known attack vectors and security bypasses

#### Test Case Groups

**A. SQL Injection Prevention (2 cases)**

1. `testSqlInjectionInHeaderDoesNotBypassTenantScope()`
   - Payload: X-Tenant-ID: "1 OR 1=1"
   - Payload: X-Tenant-ID: "1; DROP TABLE clients;"
   - Payload: X-Tenant-ID: "1 UNION SELECT * FROM clients"
   - Assert: middleware rejects (not an integer)
   - Assert: TenantResolver fails or sanitizes
   - Assert: no query executed
   - Assertions: 15+ (per payload)

2. `testSqlInjectionInQueryParameterDoesNotBypassScope()`
   - Payload: ?tenant=1' OR '1'='1
   - Payload: ?tenant=1; DELETE FROM clients WHERE 1=1
   - Payload: ?tenant=(SELECT id FROM tenants LIMIT 1)
   - Assert: middleware rejects (integer cast)
   - Assert: TenantResolver throws exception
   - Assertions: 15+ (per payload)

**B. Token Abuse Scenarios (2 cases)**

3. `testTokenScopedToTenant1CannotAccessTenant2()`
   - Create token with tenant_id = T1.id
   - Use header: Authorization: Bearer {token}
   - Try to access T2 endpoint (via X-Tenant-ID header = T2)
   - Assert: middleware should prevent access (need ValidateTenantToken middleware)
   - OR verify: token.canAccessTenant(T2) = false
   - Assert: token limited to T1
   - Assert: getTenantId() = T1.id
   - Assertions: 15+ (various scenarios)

4. `testExpiredTokenWithTenantSwitchIsRejected()`
   - Create token with tenant_id = T1.id
   - Manually expire token (carbon back date)
   - Try to use token with T2 context
   - Assert: authentication fails (expired)
   - Assert: tenant scope validation doesn't execute
   - Assert: response 401 Unauthorized
   - Assertions: 15+ (various expiry scenarios)

**C. Middleware Bypass Attempts (2 cases)**

5. `testMiddlewareBypassViaNullByteInjection()`
   - Payload: X-Tenant-ID: "1\x00' OR '1'='1"
   - Payload: Path: /api/tenant/1%00/clients
   - Payload: Query: ?tenant=1%00
   - Assert: middleware sanitizes/rejects
   - Assert: no SQL execution
   - Assert: 403 Forbidden response
   - Assertions: 15+ (per payload)

6. `testMiddlewareBypassViaPathTraversal()`
   - Payload: /api/tenant/../../tenant/1/clients
   - Payload: /api/tenant/1/../../tenant/2/clients
   - Payload: /api/tenant/1//clients
   - Assert: middleware resolves correctly (normalize path)
   - Assert: tenant extraction works correctly
   - Assert: no traversal to other tenant data
   - Assertions: 15+ (per payload)

---

### File 3: TenantPerformanceTest.php (4+ cases)

**Purpose**: Verify multi-tenant performance under load and isolation overhead

#### Test Case Groups

**A. Query Performance (2 cases)**

1. `testQueryPerformanceWithLargeTenantDataSet()`
   - Seed T1 with 1000 clients, 5000 wallets, 50000 entries
   - Seed T2 with similar volume
   - Measure query time: Client::all()->get()
   - Assert: execution time < 100ms (SQLite may vary)
   - Assert: correct tenant filtering applied
   - Assert: no full-table scans
   - Assert: index usage via explain plan
   - Assertions: 15+ (timing, row counts, query structure)

2. `testEagerLoadingPerformanceWithMultipleTenants()`
   - Create relationships as above
   - Query: Client::with('wallets.entries')->get()
   - Measure N+1 query problem
   - Assert: single query (not 1 + N queries)
   - Assert: performance acceptable
   - Assert: tenant filtering still applied
   - Assertions: 15+ (query count, timing, row verification)

**B. Concurrent Load Testing (2 cases)**

3. `testHandles100ParallelRequestsDifferentTenants()`
   - Create 3 tenants, 3 users per tenant
   - Spawn 100 parallel requests (mix of tenants)
   - Each request: create client, fetch clients, update wallet
   - Assert: all 100 succeed
   - Assert: correct isolation maintained (no cross-tenant results)
   - Assert: no deadlocks
   - Assert: consistent data state post-requests
   - Assertions: 15+ (per-tenant validation, timing, error counts)

4. `testSchemaContextSwitchingUnderLoad()`
   - 50 requests to T1, 50 to T2, interleaved
   - Each request: set tenant, query data, clear tenant
   - Assert: schema switching works correctly
   - Assert: no context leakage between requests
   - Assert: performance degrades linearly (not exponentially)
   - Assert: final data state correct for both tenants
   - Assertions: 15+ (timing per-switch, correctness checks)

---

### File 4: TenantMiddlewareSecurityTest.php (5+ cases)

**Purpose**: Deep security testing of middleware layer

#### Test Case Groups

**A. Header/Parameter Validation (2 cases)**

1. `testMiddlewareRejectsInvalidTenantIdFormats()`
   - Payload: X-Tenant-ID: "abc" (non-numeric)
   - Payload: X-Tenant-ID: "-1" (negative)
   - Payload: X-Tenant-ID: "0" (zero)
   - Payload: X-Tenant-ID: "" (empty)
   - Payload: X-Tenant-ID: "  " (whitespace)
   - Payload: X-Tenant-ID: "999999999" (out of range)
   - Payload: X-Tenant-ID: "1.5" (float)
   - Assert: all rejected with 403
   - Assert: no exception thrown (caught gracefully)
   - Assert: error message doesn't leak tenant info
   - Assertions: 15+ (per-payload format check + error validation)

2. `testMiddlewareRejectsInvalidPathFormats()`
   - Payload: /api/tenant/abc/clients
   - Payload: /api/tenant//clients (double slash)
   - Payload: /api/tenant/ (trailing slash only)
   - Payload: /api/tenant (no ID)
   - Payload: /api/abc/123/clients (wrong prefix)
   - Assert: appropriate handling (403 or route not found)
   - Assert: tenant scope still applied (if reached)
   - Assertions: 15+ (routing + middleware interaction)

**B. Authentication + Tenant Interaction (2 cases)**

3. `testUnauthenticatedRequestWithTenantHeaderIsRejected()`
   - X-Tenant-ID: T1.id, no auth header
   - Assert: 401 Unauthorized (auth middleware catches first)
   - OR if tenant middleware runs first: 403 Forbidden
   - Assert: consistent with middleware ordering
   - Assertions: 15+ (various header combinations)

4. `testAuthenticatedUserWithoutTenantAccessIsForbidden()`
   - User authenticated, member of T1 only
   - Try to access T2 via X-Tenant-ID header
   - Assert: middleware should check user->tenant access
   - Assert: 403 Forbidden
   - Assert: UnauthorizedTenant exception thrown
   - Assertions: 15+ (permission level checks, role validation)

**C. Edge Case Behavior (1+ cases)**

5. `testMiddlewareHandlesMultipleTenantDetectionMethods()`
   - Provide tenant via header + query parameter (conflicting)
   - Assert: header takes priority (per docs)
   - Provide tenant via header + path (conflicting)
   - Assert: header takes priority
   - Provide tenant via query + path (only these)
   - Assert: query parameter takes priority
   - Assert: consistent behavior across all combinations
   - Assertions: 15+ (priority order validation per scenario)

---

## 5. Test Fixtures & Utilities

### Fixture 1: TenantIsolationFixture.php

```php
class TenantIsolationFixture
{
    public static function createThreeTenantScenario(): array
    {
        return [
            'tenant1' => Tenant::factory()->create(['name' => 'Tenant One']),
            'tenant2' => Tenant::factory()->create(['name' => 'Tenant Two']),
            'tenant3' => Tenant::factory()->create(['name' => 'Tenant Three']),
        ];
    }
    
    public static function createMultiUserScenario(array $tenants): array
    {
        // Create admins, members, outsiders for each tenant
        // Return structured user data
    }
    
    public static function createCrossTenantModels(array $tenants): array
    {
        // Create clients, wallets, entries for each tenant
        // Return structured model data
    }
}
```

### Fixture 2: SecurityTestDataProvider.php

```php
class SecurityTestDataProvider
{
    public static function sqlInjectionPayloads(): array
    {
        return [
            'or-true' => "1 OR 1=1",
            'drop-table' => "1; DROP TABLE clients;",
            'union' => "1 UNION SELECT * FROM tenants",
            // More payloads...
        ];
    }
    
    public static function headerInjectionPayloads(): array
    {
        return [
            'null-byte' => "1\x00' OR '1'='1",
            'crlf' => "1\r\nX-Custom: header",
            // More payloads...
        ];
    }
}
```

### Utility: PerformanceAssertions.php

```php
trait PerformanceAssertions
{
    protected function assertQueryExecutionTime(
        int $maxMs,
        callable $query
    ): void {
        $start = microtime(true);
        $query();
        $elapsed = (microtime(true) - $start) * 1000;
        
        $this->assertLessThan($maxMs, $elapsed);
    }
    
    protected function assertNoNPlusOneQueries(
        callable $query,
        int $expectedQueries
    ): void {
        DB::enableQueryLog();
        $query();
        $count = count(DB::getQueryLog());
        
        $this->assertEquals($expectedQueries, $count);
    }
}
```

---

## 6. Implementation Order (Milestones)

### Milestone 1: Foundation (Day 1)
- [ ] Create base test class with common setup
- [ ] Create fixtures + data providers
- [ ] Create performance assertion traits
- [ ] 3 tests: basic isolation, relationship isolation, CRUD

### Milestone 2: Isolation Tests (Day 2)
- [ ] Complete TenantIsolationComprehensiveTest.php (15 tests)
- [ ] Verify all pass with RefreshDatabase
- [ ] Test on actual database (if needed)

### Milestone 3: Security Tests (Day 3)
- [ ] Complete TenantSecurityTest.php (6 tests)
- [ ] SQL injection test suite
- [ ] Token abuse scenarios
- [ ] Middleware bypass attempts

### Milestone 4: Performance & Middleware (Day 4)
- [ ] Complete TenantPerformanceTest.php (4 tests)
- [ ] Complete TenantMiddlewareSecurityTest.php (5 tests)
- [ ] Load testing (100+ parallel requests)

### Milestone 5: Documentation (Day 5)
- [ ] Create TENANT_ISOLATION_VALIDATION.md
- [ ] Document each test scenario
- [ ] Security validation checklist
- [ ] Performance baseline document

---

## 7. Success Criteria

### Test Execution

```bash
php artisan test tests/Feature/TenantIsolationComprehensiveTest.php
php artisan test tests/Feature/TenantSecurityTest.php
php artisan test tests/Feature/TenantPerformanceTest.php
php artisan test tests/Feature/TenantMiddlewareSecurityTest.php
```

- All tests pass: ✓
- Execution time < 30 seconds (parallel): ✓
- RefreshDatabase trait functions: ✓
- SQLite in-memory works: ✓
- No deprecation warnings: ✓

### Code Quality

- Each test has 15+ assertions: ✓
- No `skip()` or conditional assertions: ✓
- Follows UNIVERSAL-CODE-STYLE-RULES.md: ✓
- Clear test names + docstrings: ✓
- Fixtures reusable across tests: ✓

### Security Validation

- SQL injection payloads all rejected: ✓
- Token abuse scenarios prevented: ✓
- Middleware bypass attempts failed: ✓
- Cross-tenant data never leaks: ✓
- Performance under 100 concurrent requests: ✓

### Documentation

- TENANT_ISOLATION_VALIDATION.md created: ✓
- All test scenarios documented: ✓
- Security validation checklist: ✓
- Performance baseline recorded: ✓

---

## 8. Key Assumptions & Constraints

### Assumptions

1. TenantMiddleware runs after auth middleware (if needed)
2. PersonalAccessToken scoping is optional (test both scenarios)
3. RefreshDatabase trait creates fresh in-memory SQLite per test
4. Schema switching is transparent to tests (migrations run per tenant)
5. All models use BelongsToTenant trait + TenantScope

### Constraints

1. SQLite in-memory has different performance characteristics than PostgreSQL
   - Parallel tests may not be realistic
   - Load testing uses simulated concurrent logic

2. RefreshDatabase can't test actual PostgreSQL schema isolation
   - Tests verify schema name generation, not actual switching
   - Could add manual test for PostgreSQL if CI supports it

3. 100+ parallel requests requires either:
   - Concurrent HTTP requests (HTTP testing)
   - Or simulated via multiple test cases with shared state

---

## 9. Approval Checklist

Before implementation, confirm:

- [ ] Test plan aligns with security requirements
- [ ] Number of test cases acceptable (30+ total)
- [ ] Fixtures + factories can be implemented
- [ ] Performance benchmarks are realistic for SQLite
- [ ] No conflicts with existing test infrastructure
- [ ] Documentation scope acceptable
- [ ] Timeline (5 days) is feasible

---

## 10. Known Risks & Mitigation

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Tests too strict for SQLite | Failures on CI | Use separate PostgreSQL test suite if needed |
| Performance benchmarks unrealistic | Test flakiness | Use relative metrics, not absolute times |
| Parallel request simulation incomplete | False confidence | Document limitations in TENANT_ISOLATION_VALIDATION.md |
| Fixtures too complex | Hard to maintain | Start simple, grow as needed |
| Security payloads not comprehensive | Gaps in coverage | Use OWASP top 10 as baseline |

---

## Next Steps

1. User review & approval of this plan
2. Begin Milestone 1 (foundation)
3. Checkpoint after Milestone 2 (isolation tests)
4. Final validation after all 4 test files complete
5. Documentation handoff
