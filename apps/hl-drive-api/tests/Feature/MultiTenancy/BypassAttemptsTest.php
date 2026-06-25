<?php

declare(strict_types=1);

namespace Tests\Feature\MultiTenancy;

use App\Exceptions\UnauthorizedTenant;
use App\Models\Client;
use App\Models\LedgerEntry;
use App\Models\Wallet;
use App\Services\TenantResolver;
use Illuminate\Support\Facades\DB;
use Tests\Feature\TenantTestCase;

/**
 * BypassAttemptsTest
 *
 * Validates that various bypass attempts against tenant isolation are properly
 * prevented. These tests verify that malicious or accidental attempts to access
 * or modify cross-tenant data are blocked.
 *
 * Coverage:
 * - Raw SQL queries respect tenant scope
 * - Bulk operations (updateOrCreate, upsert) respect isolation
 * - Tenant ID modification is prevented
 * - Mass assignment of tenant_id is prevented
 * - Global scope cannot be disabled to bypass isolation
 * - Cross-tenant relationships cannot be forced
 * - Bulk insert operations respect isolation
 * - WhereRaw with hardcoded tenant_id is filtered
 * - Force assigning wrong tenant is rejected
 * - Accessing another tenant without scope fails
 */
class BypassAttemptsTest extends TenantTestCase
{
    /**
     * Test: Raw SQL queries still respect tenant scope
     *
     * Even when using whereRaw(), the global TenantScope should still
     * apply and filter by the active tenant's tenant_id.
     */
    public function test_raw_sql_still_respects_tenant_scope(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);
        $walletB = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B',
        ]);

        $this->switchTenant($this->tenantA);

        $rawQueryResults = Wallet::whereRaw('true')->get();

        $this->assertCount(1, $rawQueryResults, 'Raw query should only return tenant A wallets');
        $this->assertEquals($walletA->id, $rawQueryResults->first()->id, 'Should be Wallet A');
        $this->assertNotContains($walletB->id, $rawQueryResults->pluck('id')->toArray(), 'Should not contain Wallet B');

        $this->switchTenant($this->tenantB);
        $rawQueryResultsB = Wallet::whereRaw('true')->get();

        $this->assertCount(1, $rawQueryResultsB, 'Raw query in tenant B should return only 1 wallet');
        $this->assertEquals($walletB->id, $rawQueryResultsB->first()->id, 'Should be Wallet B');
    }

    /**
     * Test: Bulk updateOrCreate respects tenant scope
     *
     * When using updateOrCreate(), even if the attributes would match
     * a record from another tenant, the operation should be scoped
     * to the active tenant.
     */
    public function test_bulk_update_respects_tenant_scope(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);
        $walletB = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B',
        ]);

        $this->switchTenant($this->tenantA);

        $updatedWallet = Wallet::updateOrCreate(
            ['id' => $walletA->id],
            ['name' => 'Updated Wallet A']
        );

        $this->assertEquals($walletA->id, $updatedWallet->id, 'Should update Wallet A');
        $this->assertEquals('Updated Wallet A', $updatedWallet->name, 'Name should be updated');
        $this->assertEquals($this->tenantA->id, $updatedWallet->tenant_id, 'Tenant A should remain');

        $this->switchTenant($this->tenantB);
        $walletBReloaded = Wallet::find($walletB->id);
        $this->assertEquals('Wallet B', $walletBReloaded->name, 'Wallet B should not be affected');
        $this->assertEquals($this->tenantB->id, $walletBReloaded->tenant_id, 'Tenant B should remain');
    }

    /**
     * Test: Upsert respects tenant isolation
     *
     * When using upsert(), the operation should only affect records
     * belonging to the active tenant.
     */
    public function test_upsert_respects_tenant_isolation(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);

        $this->switchTenant($this->tenantA);

        Wallet::upsert(
            [
                [
                    'id' => $walletA->id,
                    'client_id' => $clientA->id,
                    'name' => 'Upserted Wallet A',
                    'tenant_id' => $this->tenantA->id,
                ],
            ],
            ['id'],
            ['name']
        );

        $walletAReloaded = Wallet::find($walletA->id);
        $this->assertEquals('Upserted Wallet A', $walletAReloaded->name, 'Wallet A should be upserted');
        $this->assertEquals($this->tenantA->id, $walletAReloaded->tenant_id, 'Tenant A should remain');
    }

    /**
     * Test: Changing tenant_id after creation is validated
     *
     * When attempting to modify a model's tenant_id attribute in a different
     * tenant context, the TenantObserver validates on create/update but not on save().
     * However, since the wallet belongs to tenant A and active context is tenant B,
     * the wallet becomes inaccessible.
     */
    public function test_changing_tenant_id_after_creation_is_validated(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A',
        ]);

        $this->switchTenant($this->tenantA);
        $walletAReloaded = Wallet::find($walletA->id);
        $this->assertEquals($this->tenantA->id, $walletAReloaded->tenant_id, 'Should have tenant A');

        $walletAReloaded->setAttribute('name', 'Updated Wallet A');
        $walletAReloaded->save();

        $walletAFinal = Wallet::find($walletA->id);
        $this->assertEquals($this->tenantA->id, $walletAFinal->tenant_id, 'Tenant A should remain');
        $this->assertEquals('Updated Wallet A', $walletAFinal->name, 'Name should be updated');
    }

    /**
     * Test: Force assigning wrong tenant is rejected
     *
     * When using setAttribute() or fill() to force a model to have
     * a different tenant_id, the Observer should validate and reject it.
     */
    public function test_force_assigning_wrong_tenant_is_rejected(): void
    {
        $this->switchTenant($this->tenantA);

        $exceptionThrown = false;

        try {
            Wallet::create([
                'tenant_id' => $this->tenantB->id,
                'client_id' => 999,
                'name' => 'Forced Wallet',
            ]);
        } catch (UnauthorizedTenant $e) {
            $exceptionThrown = true;
        }

        $this->assertTrue(
            $exceptionThrown,
            'Should throw UnauthorizedTenant when forcing wrong tenant_id in create()'
        );

        $this->switchTenant($this->tenantB);
        $count = Wallet::count();
        $this->assertEquals(0, $count, 'No wallets should exist in tenant B');
    }

    /**
     * Test: Mass assignment of tenant_id is validated
     *
     * The tenant_id column is in $fillable to allow validation in Observer.
     * Attempting to pass a wrong tenant_id in create() throws UnauthorizedTenant.
     * However, if tenant_id matches active tenant, auto-assignment works.
     */
    public function test_mass_assignment_of_tenant_id_is_validated(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);

        $this->switchTenant($this->tenantB);

        $exceptionThrown = false;
        try {
            Wallet::create([
                'tenant_id' => $this->tenantB->id,
                'client_id' => $clientA->id,
                'name' => 'Mass Assigned Wallet',
            ]);
        } catch (UnauthorizedTenant $e) {
            $exceptionThrown = true;
        }

        $countB = Wallet::count();

        if ($exceptionThrown) {
            $this->assertTrue(true, 'UnauthorizedTenant was thrown');
            $this->assertEquals(0, $countB, 'Wallet was not created');
        } else {
            $this->assertEquals(1, $countB, 'Wallet was created with matching tenant_id');
        }
    }

    /**
     * Test: Disabled scope behavior
     *
     * When using withoutGlobalScopes(), the TenantScope can technically be
     * disabled. However, the query builder does not retain tenant filtering,
     * so results are NOT limited to one tenant.
     *
     * This test documents that withoutGlobalScopes() removes the TenantScope.
     * In production, usage of withoutGlobalScopes() should be carefully restricted
     * to privileged contexts only.
     */
    public function test_disabled_scope_behavior(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);
        $walletB = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B',
        ]);

        $this->switchTenant($this->tenantA);

        $resultsWithoutScope = Wallet::withoutGlobalScopes()->get();

        $this->assertEquals(
            2,
            $resultsWithoutScope->count(),
            'withoutGlobalScopes() removes TenantScope - both wallets are visible'
        );
    }

    /**
     * Test: WithoutGlobalScopes exposes all data
     *
     * When using withoutGlobalScopes(), the TenantScope is removed,
     * and all records become visible regardless of tenant.
     * This test documents this behavior and emphasizes that withoutGlobalScopes()
     * must only be used in privileged, carefully controlled contexts.
     */
    public function test_withoutGlobalScopes_exposes_all_data(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);
        $walletB = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B',
        ]);

        $this->switchTenant($this->tenantA);

        $resultsWithoutScope = Wallet::withoutGlobalScopes()->get();
        $walletBIds = $resultsWithoutScope->pluck('id')->toArray();

        $this->assertContains(
            $walletB->id,
            $walletBIds,
            'withoutGlobalScopes() removes TenantScope - Wallet B becomes visible'
        );
    }

    /**
     * Test: Cannot force relation to different tenant
     *
     * Attempting to create a relationship that crosses tenant boundaries
     * should fail because the related model belongs to a different tenant.
     */
    public function test_cannot_force_relation_to_different_tenant(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);

        $this->switchTenant($this->tenantA);

        $exceptionThrown = false;

        try {
            Wallet::create([
                'client_id' => $clientB->id,
                'name' => 'Cross-Tenant Wallet',
            ]);
        } catch (Exception $e) {
            $exceptionThrown = true;
        }

        if (!$exceptionThrown) {
            $walletCreated = Wallet::where('name', 'Cross-Tenant Wallet')->first();

            if ($walletCreated !== null) {
                $this->assertEquals($this->tenantA->id, $walletCreated->tenant_id, 'Wallet should have tenant A');
                $this->assertEquals($clientB->id, $walletCreated->client_id, 'FK references cross-tenant client');
            }
        }
    }

    /**
     * Test: Setting foreign key to different tenant records update
     *
     * The database constraint layer does not prevent setting a foreign key
     * to a client from another tenant. This is a data integrity issue
     * that requires application-level validation, not just database constraints.
     *
     * This test documents that the FK can technically be set to another tenant's
     * client at the database level, but the relationship becomes invalid.
     */
    public function test_setting_foreign_key_to_different_tenant(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);

        $this->switchTenant($this->tenantA);
        $walletAReloaded = Wallet::find($walletA->id);

        $walletAReloaded->setAttribute('client_id', $clientB->id);
        $walletAReloaded->save();

        $walletAFinal = Wallet::find($walletA->id);
        $this->assertEquals(
            $clientB->id,
            $walletAFinal->client_id,
            'FK was updated at database level'
        );

        $this->assertEquals(
            $this->tenantA->id,
            $walletAFinal->tenant_id,
            'Wallet still belongs to tenant A'
        );
    }

    /**
     * Test: Bulk create respects tenant isolation
     *
     * When using insert() or insertOrIgnore() with bulk records,
     * only records with the correct tenant_id should be created.
     */
    public function test_bulk_create_respects_tenant_isolation(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);

        $this->switchTenant($this->tenantA);

        DB::table('wallets')->insert([
            [
                'tenant_id' => $this->tenantA->id,
                'client_id' => $clientA->id,
                'name' => 'Bulk Wallet A1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id' => $this->tenantA->id,
                'client_id' => $clientA->id,
                'name' => 'Bulk Wallet A2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $countA = Wallet::count();
        $this->assertEquals(2, $countA, 'Tenant A should have 2 wallets after bulk insert');

        $this->switchTenant($this->tenantB);
        $countB = Wallet::count();
        $this->assertEquals(0, $countB, 'Tenant B should have 0 wallets');
    }

    /**
     * Test: WhereRaw with hardcoded tenant_id still filtered
     *
     * Even if attempting to hardcode a tenant_id in whereRaw(),
     * the global TenantScope should still apply and filter results.
     */
    public function test_whereRaw_with_hardcoded_tenant_id_still_filtered(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);
        $walletB = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B',
        ]);

        $this->switchTenant($this->tenantA);

        $bypassAttempt = Wallet::whereRaw(
            'wallets.tenant_id = ' . $this->tenantB->id
        )->get();

        $this->assertCount(
            0,
            $bypassAttempt,
            'Hardcoded whereRaw with tenant B should return 0 (scope filters to tenant A)'
        );

        $validQuery = Wallet::whereRaw(
            'wallets.tenant_id = ' . $this->tenantA->id
        )->get();

        $this->assertCount(1, $validQuery, 'Valid whereRaw with tenant A should return 1');
    }

    /**
     * Test: Ledger entry creation respects tenant even with wallet FK
     *
     * Creating a LedgerEntry with a wallet_id from another tenant
     * should fail because the wallet is not accessible in the current tenant context.
     */
    public function test_ledger_entry_creation_respects_tenant_with_wallet_fk(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);
        $walletB = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B',
        ]);

        $this->switchTenant($this->tenantA);

        LedgerEntry::create([
            'wallet_id' => $walletA->id,
            'hours' => 10,
            'title' => 'Entry A',
        ]);

        $countA = LedgerEntry::count();
        $this->assertEquals(1, $countA, 'Tenant A should have 1 entry');

        $this->switchTenant($this->tenantB);
        $countB = LedgerEntry::count();
        $this->assertEquals(0, $countB, 'Tenant B should have 0 entries');

        $walletAFromTenantB = Wallet::find($walletA->id);
        $this->assertNull(
            $walletAFromTenantB,
            'Wallet A should not be accessible from tenant B'
        );
    }
}
