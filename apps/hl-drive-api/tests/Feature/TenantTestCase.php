<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantContext;
use App\Models\User;
use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * TenantTestCase
 *
 * Base test case for multi-tenancy tests. Provides:
 * - Automatic creation of 3 tenants (A, B, C)
 * - Automatic creation of 3 users (one per tenant)
 * - Tenant context switching helper
 * - Tenant isolation assertion helper
 *
 * Usage:
 *   class MyTest extends TenantTestCase {
 *       public function test_something() {
 *           $this->switchTenant($this->tenantA);
 *           // Test code
 *       }
 *   }
 */
abstract class TenantTestCase extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected Tenant $tenantC;

    protected User $userA;
    protected User $userB;
    protected User $userC;

    /**
     * Set up test tenants and users
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestTenants();
        $this->createTestUsers();
    }

    /**
     * Create 3 test tenants (A, B, C)
     */
    protected function createTestTenants(): void
    {
        $this->tenantA = Tenant::factory()->create(['name' => 'Tenant A']);
        $this->tenantB = Tenant::factory()->create(['name' => 'Tenant B']);
        $this->tenantC = Tenant::factory()->create(['name' => 'Tenant C']);
    }

    /**
     * Create 3 test users (one per tenant)
     */
    protected function createTestUsers(): void
    {
        $this->userA = User::factory()->create([
            'name' => 'User A',
            'email' => 'usera@test.com',
        ]);

        $this->userB = User::factory()->create([
            'name' => 'User B',
            'email' => 'userb@test.com',
        ]);

        $this->userC = User::factory()->create([
            'name' => 'User C',
            'email' => 'userc@test.com',
        ]);

        // Grant users access to their respective tenants
        $this->userA->tenants()->attach([
            $this->tenantA->id => ['role' => 'admin', 'status' => 'active'],
        ]);

        $this->userB->tenants()->attach([
            $this->tenantB->id => ['role' => 'admin', 'status' => 'active'],
        ]);

        $this->userC->tenants()->attach([
            $this->tenantC->id => ['role' => 'admin', 'status' => 'active'],
        ]);
    }

    /**
     * Switch to a different tenant context
     *
     * This sets the active tenant in the TenantResolver service,
     * allowing subsequent queries to be filtered by the tenant_id.
     *
     * For testing, we set the tenant directly using reflection to bypass
     * validation that might fail in test environments.
     *
     * @param Tenant $tenant The tenant to switch to
     */
    protected function switchTenant(Tenant $tenant): void
    {
        // Rebind TenantResolver to ensure fresh instance
        app()->forgetInstance(TenantResolver::class);
        $tenantResolver = app(TenantResolver::class);

        $this->setTenantContextDirectly($tenantResolver, $tenant);
    }

    /**
     * Set tenant context directly (for testing purposes)
     *
     * Uses reflection to set private properties for testing scenarios.
     * This bypasses validation to allow testing of isolation logic.
     *
     * @param TenantResolver $resolver The resolver instance
     * @param Tenant $tenant The tenant to set
     */
    private function setTenantContextDirectly(TenantResolver $resolver, Tenant $tenant): void
    {
        $reflection = new \ReflectionClass($resolver);

        // Set tenantId
        $tenantIdProp = $reflection->getProperty('tenantId');
        $tenantIdProp->setAccessible(true);
        $tenantIdProp->setValue($resolver, $tenant->id);

        // Set schema
        $schemaProp = $reflection->getProperty('schema');
        $schemaProp->setAccessible(true);
        $schemaProp->setValue($resolver, $tenant->schemaName('testing'));

        // Set context
        $contextClass = new \ReflectionClass(TenantContext::class);
        $context = new TenantContext($tenant->id, $tenant->schemaName('testing'), null);

        $contextProp = $reflection->getProperty('context');
        $contextProp->setAccessible(true);
        $contextProp->setValue($resolver, $context);
    }

    /**
     * Assert that a model is properly isolated by tenant
     *
     * Verifies that when switching between tenants, queries only return
     * data for the active tenant. This is a basic check that the
     * TenantScope is working correctly.
     *
     * @param string $modelClass The full class name of the model to test
     * @param int $expectedCountForEachTenant Expected record count per tenant
     */
    protected function assertTenantIsolation(string $modelClass, int $expectedCountForEachTenant = 1): void
    {
        // Verify data isolation for each tenant
        $this->switchTenant($this->tenantA);
        $countA = $modelClass::count();
        $this->assertEquals(
            $expectedCountForEachTenant,
            $countA,
            "Expected {$expectedCountForEachTenant} records in tenant A, got {$countA}"
        );

        $this->switchTenant($this->tenantB);
        $countB = $modelClass::count();
        $this->assertEquals(
            $expectedCountForEachTenant,
            $countB,
            "Expected {$expectedCountForEachTenant} records in tenant B, got {$countB}"
        );

        $this->switchTenant($this->tenantC);
        $countC = $modelClass::count();
        $this->assertEquals(
            $expectedCountForEachTenant,
            $countC,
            "Expected {$expectedCountForEachTenant} records in tenant C, got {$countC}"
        );
    }

    /**
     * Assert that no tenant is currently active
     *
     * Useful for testing behavior when tenant context is not set.
     */
    protected function assertNoActiveTenant(): void
    {
        $tenantResolver = app(TenantResolver::class);
        $this->assertNull(
            $tenantResolver->getTenantId(),
            'Expected no active tenant, but one was set'
        );
    }

    /**
     * Assert that a specific tenant is currently active
     *
     * @param Tenant $tenant The expected active tenant
     */
    protected function assertActiveTenant(Tenant $tenant): void
    {
        $tenantResolver = app(TenantResolver::class);
        $this->assertEquals(
            $tenant->id,
            $tenantResolver->getTenantId(),
            "Expected tenant {$tenant->id} to be active"
        );
    }

    /**
     * Get a test user for a specific tenant
     *
     * @param Tenant $tenant The tenant to get a user for
     *
     * @return User
     */
    protected function getUserForTenant(Tenant $tenant): User
    {
        if ($tenant->id === $this->tenantA->id) {
            return $this->userA;
        }

        if ($tenant->id === $this->tenantB->id) {
            return $this->userB;
        }

        if ($tenant->id === $this->tenantC->id) {
            return $this->userC;
        }

        throw new \RuntimeException("No user found for tenant {$tenant->id}");
    }
}
