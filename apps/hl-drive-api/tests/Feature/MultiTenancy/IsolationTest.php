<?php

declare(strict_types=1);

namespace Tests\Feature\MultiTenancy;

use App\Models\Client;
use App\Models\InstructorStudentLink;
use App\Models\LedgerEntry;
use App\Models\User;
use App\Models\Wallet;
use Tests\Feature\TenantTestCase;

/**
 * IsolationTest
 *
 * Validates that model queries respect tenant isolation.
 * Tests that when querying a model with BelongsToTenant trait,
 * only data for the active tenant is returned.
 *
 * Coverage:
 * - Client::all() isolation
 * - Wallet::all() isolation
 * - LedgerEntry::all() isolation
 * - InstructorStudentLink isolation
 * - Comprehensive isolation validation
 */
class IsolationTest extends TenantTestCase
{
    /**
     * Test: Client::all() returns only tenant clients
     *
     * When switching between tenants, Client::all() should only return
     * clients that belong to the active tenant.
     */
    public function test_client_all_returns_only_tenant_clients(): void
    {
        // Create clients for each tenant using raw model creation
        // Explicitly set tenant_id to ensure it's saved correctly
        $clientA = new Client(['name' => 'Client A', 'tenant_id' => $this->tenantA->id]);
        $clientA->saveQuietly();

        $clientB = new Client(['name' => 'Client B', 'tenant_id' => $this->tenantB->id]);
        $clientB->saveQuietly();

        $clientC = new Client(['name' => 'Client C', 'tenant_id' => $this->tenantC->id]);
        $clientC->saveQuietly();

        // Verify isolation by checking count for each tenant
        $this->switchTenant($this->tenantA);
        $this->assertEquals(1, Client::count(), 'Tenant A should have exactly 1 client');
        $this->assertTrue(
            Client::where('id', $clientA->id)->exists(),
            'Client A should be accessible'
        );
        $this->assertFalse(
            Client::where('id', $clientB->id)->exists(),
            'Client B should NOT be accessible'
        );
        $this->assertFalse(
            Client::where('id', $clientC->id)->exists(),
            'Client C should NOT be accessible'
        );

        // Switch to tenant B
        $this->switchTenant($this->tenantB);
        $this->assertEquals(1, Client::count(), 'Tenant B should have exactly 1 client');
        $this->assertTrue(
            Client::where('id', $clientB->id)->exists(),
            'Client B should be accessible'
        );
        $this->assertFalse(
            Client::where('id', $clientA->id)->exists(),
            'Client A should NOT be accessible'
        );
        $this->assertFalse(
            Client::where('id', $clientC->id)->exists(),
            'Client C should NOT be accessible'
        );

        // Switch to tenant C
        $this->switchTenant($this->tenantC);
        $this->assertEquals(1, Client::count(), 'Tenant C should have exactly 1 client');
        $this->assertTrue(
            Client::where('id', $clientC->id)->exists(),
            'Client C should be accessible'
        );
        $this->assertFalse(
            Client::where('id', $clientA->id)->exists(),
            'Client A should NOT be accessible'
        );
        $this->assertFalse(
            Client::where('id', $clientB->id)->exists(),
            'Client B should NOT be accessible'
        );
    }

    /**
     * Test: Wallet::all() returns only tenant wallets
     *
     * When switching between tenants, Wallet::all() should only return
     * wallets that belong to the active tenant.
     */
    public function test_wallet_all_returns_only_tenant_wallets(): void
    {
        // Create wallets for each tenant (must create clients and wallets in tenant context)
        // Create clients first
        $this->switchTenant($this->tenantA);
        $clientA = new Client(['name' => 'Client A']);
        $clientA->save();

        $this->switchTenant($this->tenantB);
        $clientB = new Client(['name' => 'Client B']);
        $clientB->save();

        $this->switchTenant($this->tenantC);
        $clientC = new Client(['name' => 'Client C']);
        $clientC->save();

        // Create wallets using raw model creation
        $this->switchTenant($this->tenantA);
        $walletA = new Wallet(['client_id' => $clientA->id, 'name' => 'Wallet A']);
        $walletA->save();

        $this->switchTenant($this->tenantB);
        $walletB = new Wallet(['client_id' => $clientB->id, 'name' => 'Wallet B']);
        $walletB->save();

        $this->switchTenant($this->tenantC);
        $walletC = new Wallet(['client_id' => $clientC->id, 'name' => 'Wallet C']);
        $walletC->save();

        // Verify isolation by checking count for each tenant
        $this->switchTenant($this->tenantA);
        $this->assertEquals(1, Wallet::count(), 'Tenant A should have exactly 1 wallet');
        $this->assertTrue(
            Wallet::where('id', $walletA->id)->exists(),
            'Wallet A should be accessible'
        );
        $this->assertFalse(
            Wallet::where('id', $walletB->id)->exists(),
            'Wallet B should NOT be accessible'
        );
        $this->assertFalse(
            Wallet::where('id', $walletC->id)->exists(),
            'Wallet C should NOT be accessible'
        );

        // Switch to tenant B
        $this->switchTenant($this->tenantB);
        $this->assertEquals(1, Wallet::count(), 'Tenant B should have exactly 1 wallet');
        $this->assertTrue(
            Wallet::where('id', $walletB->id)->exists(),
            'Wallet B should be accessible'
        );
        $this->assertFalse(
            Wallet::where('id', $walletA->id)->exists(),
            'Wallet A should NOT be accessible'
        );
        $this->assertFalse(
            Wallet::where('id', $walletC->id)->exists(),
            'Wallet C should NOT be accessible'
        );

        // Switch to tenant C
        $this->switchTenant($this->tenantC);
        $this->assertEquals(1, Wallet::count(), 'Tenant C should have exactly 1 wallet');
        $this->assertTrue(
            Wallet::where('id', $walletC->id)->exists(),
            'Wallet C should be accessible'
        );
        $this->assertFalse(
            Wallet::where('id', $walletA->id)->exists(),
            'Wallet A should NOT be accessible'
        );
        $this->assertFalse(
            Wallet::where('id', $walletB->id)->exists(),
            'Wallet B should NOT be accessible'
        );
    }

    /**
     * Test: LedgerEntry::all() returns only tenant entries
     *
     * When switching between tenants, LedgerEntry::all() should only return
     * ledger entries that belong to the active tenant.
     */
    public function test_ledger_entry_all_returns_only_tenant_entries(): void
    {
        // Create ledger entries for each tenant using raw model creation
        // Create ledger entries for each tenant with explicit tenant_id
        $clientA = new Client(['name' => 'Client A', 'tenant_id' => $this->tenantA->id]);
        $clientA->saveQuietly();
        $walletA = new Wallet(['client_id' => $clientA->id, 'name' => 'Wallet A', 'tenant_id' => $this->tenantA->id]);
        $walletA->saveQuietly();
        $entryA = new LedgerEntry(['wallet_id' => $walletA->id, 'hours' => 10.50, 'tenant_id' => $this->tenantA->id]);
        $entryA->saveQuietly();

        $clientB = new Client(['name' => 'Client B', 'tenant_id' => $this->tenantB->id]);
        $clientB->saveQuietly();
        $walletB = new Wallet(['client_id' => $clientB->id, 'name' => 'Wallet B', 'tenant_id' => $this->tenantB->id]);
        $walletB->saveQuietly();
        $entryB = new LedgerEntry(['wallet_id' => $walletB->id, 'hours' => 10.50, 'tenant_id' => $this->tenantB->id]);
        $entryB->saveQuietly();

        $clientC = new Client(['name' => 'Client C', 'tenant_id' => $this->tenantC->id]);
        $clientC->saveQuietly();
        $walletC = new Wallet(['client_id' => $clientC->id, 'name' => 'Wallet C', 'tenant_id' => $this->tenantC->id]);
        $walletC->saveQuietly();
        $entryC = new LedgerEntry(['wallet_id' => $walletC->id, 'hours' => 10.50, 'tenant_id' => $this->tenantC->id]);
        $entryC->saveQuietly();

        // Verify isolation by checking count for each tenant
        $this->switchTenant($this->tenantA);
        $this->assertEquals(
            1,
            LedgerEntry::count(),
            'Tenant A should have exactly 1 ledger entry'
        );
        $this->assertTrue(
            LedgerEntry::where('id', $entryA->id)->exists(),
            'Entry A should be accessible'
        );
        $this->assertFalse(
            LedgerEntry::where('id', $entryB->id)->exists(),
            'Entry B should NOT be accessible'
        );
        $this->assertFalse(
            LedgerEntry::where('id', $entryC->id)->exists(),
            'Entry C should NOT be accessible'
        );

        // Switch to tenant B
        $this->switchTenant($this->tenantB);
        $this->assertEquals(
            1,
            LedgerEntry::count(),
            'Tenant B should have exactly 1 ledger entry'
        );
        $this->assertTrue(
            LedgerEntry::where('id', $entryB->id)->exists(),
            'Entry B should be accessible'
        );
        $this->assertFalse(
            LedgerEntry::where('id', $entryA->id)->exists(),
            'Entry A should NOT be accessible'
        );
        $this->assertFalse(
            LedgerEntry::where('id', $entryC->id)->exists(),
            'Entry C should NOT be accessible'
        );

        // Switch to tenant C
        $this->switchTenant($this->tenantC);
        $this->assertEquals(
            1,
            LedgerEntry::count(),
            'Tenant C should have exactly 1 ledger entry'
        );
        $this->assertTrue(
            LedgerEntry::where('id', $entryC->id)->exists(),
            'Entry C should be accessible'
        );
        $this->assertFalse(
            LedgerEntry::where('id', $entryA->id)->exists(),
            'Entry A should NOT be accessible'
        );
        $this->assertFalse(
            LedgerEntry::where('id', $entryB->id)->exists(),
            'Entry B should NOT be accessible'
        );
    }

    /**
     * Test: User tenant access isolation
     *
     * Users are globally-scoped but have tenant associations through the user_tenants pivot table.
     * This test verifies that tenant access relationships are correctly isolated.
     */
    public function test_user_tenant_access_isolation(): void
    {
        // Verify the setup: each user has access to their respective tenant
        $this->assertTrue(
            $this->userA->hasAccessToTenant($this->tenantA->id),
            'User A should have access to Tenant A'
        );
        $this->assertFalse(
            $this->userA->hasAccessToTenant($this->tenantB->id),
            'User A should NOT have access to Tenant B'
        );

        $this->assertTrue(
            $this->userB->hasAccessToTenant($this->tenantB->id),
            'User B should have access to Tenant B'
        );
        $this->assertFalse(
            $this->userB->hasAccessToTenant($this->tenantA->id),
            'User B should NOT have access to Tenant A'
        );

        $this->assertTrue(
            $this->userC->hasAccessToTenant($this->tenantC->id),
            'User C should have access to Tenant C'
        );
        $this->assertFalse(
            $this->userC->hasAccessToTenant($this->tenantA->id),
            'User C should NOT have access to Tenant A'
        );
    }

    /**
     * Test: InstructorStudentLink isolation by tenant
     *
     * InstructorStudentLink has a tenant_id column and can be filtered by tenant directly.
     * Note: InstructorStudentLink does NOT use BelongsToTenant trait but has tenant_id column
     * for data organization. This test verifies basic tenant filtering works correctly.
     */
    public function test_link_isolation_by_tenant(): void
    {
        // Create instructor and student users
        $instructorA = User::factory()->create(['email' => 'instructor_a@test.com']);
        $studentA = User::factory()->create(['email' => 'student_a@test.com']);

        $instructorB = User::factory()->create(['email' => 'instructor_b@test.com']);
        $studentB = User::factory()->create(['email' => 'student_b@test.com']);

        $instructorC = User::factory()->create(['email' => 'instructor_c@test.com']);
        $studentC = User::factory()->create(['email' => 'student_c@test.com']);

        // Create links for each tenant using create() with explicit attributes
        // Cannot use factory due to observer validation; must switch tenant context first
        $linkA = new InstructorStudentLink([
            'tenant_id' => $this->tenantA->id,
            'instructor_id' => $instructorA->id,
            'student_id' => $studentA->id,
            'status' => 'ACTIVE',
            'access_level' => 'FULL',
        ]);
        $linkA->saveQuietly();

        $linkB = new InstructorStudentLink([
            'tenant_id' => $this->tenantB->id,
            'instructor_id' => $instructorB->id,
            'student_id' => $studentB->id,
            'status' => 'ACTIVE',
            'access_level' => 'FULL',
        ]);
        $linkB->saveQuietly();

        $linkC = new InstructorStudentLink([
            'tenant_id' => $this->tenantC->id,
            'instructor_id' => $instructorC->id,
            'student_id' => $studentC->id,
            'status' => 'ACTIVE',
            'access_level' => 'FULL',
        ]);
        $linkC->saveQuietly();

        // Verify isolation by checking count and filtering by tenant
        $this->assertEquals(
            1,
            InstructorStudentLink::where('tenant_id', $this->tenantA->id)->count(),
            'Tenant A should have exactly 1 link'
        );
        $this->assertEquals(
            1,
            InstructorStudentLink::where('tenant_id', $this->tenantB->id)->count(),
            'Tenant B should have exactly 1 link'
        );
        $this->assertEquals(
            1,
            InstructorStudentLink::where('tenant_id', $this->tenantC->id)->count(),
            'Tenant C should have exactly 1 link'
        );

        $this->assertTrue(
            InstructorStudentLink::where('tenant_id', $this->tenantA->id)
                ->where('id', $linkA->id)
                ->exists(),
            'Link A should be found in Tenant A'
        );
        $this->assertFalse(
            InstructorStudentLink::where('tenant_id', $this->tenantA->id)
                ->where('id', $linkB->id)
                ->exists(),
            'Link B should NOT be found in Tenant A'
        );
    }

    /**
     * Test: Models with BelongsToTenant trait are properly isolated
     *
     * This comprehensive test validates the complete isolation behavior:
     * - TenantScope is applied automatically
     * - When switching tenants, results change correctly
     * - No cross-tenant data leakage
     */
    public function test_tenant_isolation_comprehensive(): void
    {
        // Setup: Create data for all three tenants
        $clientA = new Client(['name' => 'Client A', 'tenant_id' => $this->tenantA->id]);
        $clientA->saveQuietly();
        $walletA = new Wallet(['client_id' => $clientA->id, 'name' => 'Wallet A', 'tenant_id' => $this->tenantA->id]);
        $walletA->saveQuietly();

        $clientB = new Client(['name' => 'Client B', 'tenant_id' => $this->tenantB->id]);
        $clientB->saveQuietly();
        $walletB = new Wallet(['client_id' => $clientB->id, 'name' => 'Wallet B', 'tenant_id' => $this->tenantB->id]);
        $walletB->saveQuietly();

        // Test isolation for multiple models simultaneously
        $this->switchTenant($this->tenantA);
        $this->assertEquals(1, Client::count(), 'Should see 1 client in tenant A');
        $this->assertEquals(1, Wallet::count(), 'Should see 1 wallet in tenant A');

        // Switch to different tenant
        $this->switchTenant($this->tenantB);
        $this->assertEquals(1, Client::count(), 'Should see 1 client in tenant B');
        $this->assertEquals(1, Wallet::count(), 'Should see 1 wallet in tenant B');

        // Verify no cross-tenant data leakage
        $this->assertFalse(
            Client::where('id', $clientA->id)->exists(),
            'Client A should NOT be visible in Tenant B'
        );
        $this->assertFalse(
            Wallet::where('id', $walletA->id)->exists(),
            'Wallet A should NOT be visible in Tenant B'
        );
    }
}
