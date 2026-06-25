<?php

declare(strict_types=1);

namespace Tests\Feature\MultiTenancy;

use App\Models\Client;
use App\Models\LedgerEntry;
use App\Models\Wallet;
use Tests\Feature\TenantTestCase;

/**
 * DataLeakageTest
 *
 * Validates that data leakage is prevented through relationships,
 * eager loading, and aggregation operations.
 *
 * Tests that model relationships respect tenant isolation and that
 * cross-tenant data cannot be accessed through related models or
 * eager loading mechanisms.
 *
 * Coverage:
 * - belongsTo relations prevent cross-tenant access
 * - hasMany relations only return tenant data
 * - hasManyThrough relations respect isolation
 * - Nested relations prevent multi-level leakage
 * - Eager loading with with() respects tenant scope
 * - Lazy loading with load() respects tenant scope
 * - whereHas() only counts tenant records
 * - withCount() doesn't leak cross-tenant counts
 * - Model::count() only counts tenant data
 * - Aggregates (sum, count) respect tenant scope
 * - Relation counts through queries respect tenant
 * - Aggregates with where clauses respect tenant
 */
class DataLeakageTest extends TenantTestCase
{
    /**
     * Test: belongsTo relation prevents leakage
     *
     * When a Wallet from tenant A tries to load its Client,
     * if the Client belongs to a different tenant, the relation
     * should return null (or be filtered by the TenantScope).
     */
    public function test_belongsTo_relation_prevents_leakage(): void
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

        // Switch to tenant A and verify relations work within tenant
        $this->switchTenant($this->tenantA);
        $walletAReloaded = Wallet::find($walletA->id);
        $this->assertNotNull($walletAReloaded, 'Wallet A should be found in tenant A');
        $this->assertNotNull($walletAReloaded->client, 'Client A should be accessible from Wallet A');
        $this->assertEquals($clientA->id, $walletAReloaded->client->id, 'Should be Client A');

        // Switch to tenant B and verify tenant A's wallet is not accessible
        $this->switchTenant($this->tenantB);
        $walletAFromTenantB = Wallet::find($walletA->id);
        $this->assertNull($walletAFromTenantB, 'Wallet A should not be accessible from tenant B');
    }

    /**
     * Test: hasMany relation respects tenant scope
     *
     * When loading Client.wallets(), only wallets from the active
     * tenant should be returned.
     */
    public function test_hasMany_relation_respects_scope(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA1 = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A1',
        ]);
        $walletA2 = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A2',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);
        $walletB1 = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B1',
        ]);

        // Switch to tenant A and verify only A's wallets are returned
        $this->switchTenant($this->tenantA);
        $clientAReloaded = Client::find($clientA->id);
        $this->assertNotNull($clientAReloaded, 'Client A should be found');

        $walletsFromA = $clientAReloaded->wallets;
        $this->assertCount(2, $walletsFromA, 'Client A should have exactly 2 wallets');
        $walletIds = $walletsFromA->pluck('id')->toArray();
        $this->assertContains($walletA1->id, $walletIds, 'Wallet A1 should be in results');
        $this->assertContains($walletA2->id, $walletIds, 'Wallet A2 should be in results');
        $this->assertNotContains($walletB1->id, $walletIds, 'Wallet B1 should NOT be in results');

        // Switch to tenant B and verify only B's wallets are returned
        $this->switchTenant($this->tenantB);
        $clientBReloaded = Client::find($clientB->id);
        $walletsFromB = $clientBReloaded->wallets;
        $this->assertCount(1, $walletsFromB, 'Client B should have exactly 1 wallet');
        $this->assertEquals($walletB1->id, $walletsFromB->first()->id, 'Should be Wallet B1');
    }

    /**
     * Test: hasManyThrough respects isolation
     *
     * When loading Client.ledgerEntries through Wallet,
     * only entries from the active tenant should be returned.
     */
    public function test_hasManyThrough_respects_isolation(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A',
        ]);
        $entryA = $this->createModelInTenant($this->tenantA, LedgerEntry::class, [
            'wallet_id' => $walletA->id,
            'hours' => 10,
            'title' => 'Entry A',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);
        $walletB = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B',
        ]);
        $entryB = $this->createModelInTenant($this->tenantB, LedgerEntry::class, [
            'wallet_id' => $walletB->id,
            'hours' => 20,
            'title' => 'Entry B',
        ]);

        // Switch to tenant A and verify only A's entries are returned
        $this->switchTenant($this->tenantA);
        $clientAReloaded = Client::find($clientA->id);
        $this->assertNotNull($clientAReloaded, 'Client A should be found');

        // Verify through relation returns only tenant A entries
        $this->assertEquals(1, LedgerEntry::count(), 'Tenant A should have exactly 1 ledger entry');
        $entryFromWallet = $clientAReloaded->wallets()->first()->ledgerEntries;
        $this->assertCount(1, $entryFromWallet, 'Should have exactly 1 entry through wallet');
        $this->assertEquals($entryA->id, $entryFromWallet->first()->id, 'Should be Entry A');
    }

    /**
     * Test: nested relations prevent cross-tenant access
     *
     * Verify that accessing Wallet -> Client -> anotherWallet
     * doesn't leak data across tenants through multiple relation hops.
     */
    public function test_nested_relation_prevents_cross_tenant_access(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA1 = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A1',
        ]);
        $walletA2 = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A2',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);
        $walletB = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B',
        ]);

        // Switch to tenant A
        $this->switchTenant($this->tenantA);

        // Access Wallet A1 -> Client A -> all wallets
        $walletA1Reloaded = Wallet::find($walletA1->id);
        $clientFromWallet = $walletA1Reloaded->client;
        $this->assertNotNull($clientFromWallet, 'Client should be accessible');

        $allWalletsForClient = $clientFromWallet->wallets;
        $this->assertCount(2, $allWalletsForClient, 'Client A should have exactly 2 wallets');
        $walletIds = $allWalletsForClient->pluck('id')->toArray();
        $this->assertContains($walletA1->id, $walletIds, 'Wallet A1 should be included');
        $this->assertContains($walletA2->id, $walletIds, 'Wallet A2 should be included');
        $this->assertNotContains($walletB->id, $walletIds, 'Wallet B should NOT be included');
    }

    /**
     * Test: with() eager loading prevents leakage
     *
     * When using with('wallets'), only wallets from the active
     * tenant should be loaded.
     */
    public function test_with_eager_loading_prevents_leakage(): void
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

        // Switch to tenant A and eager load wallets
        $this->switchTenant($this->tenantA);
        $clientsWithWallets = Client::with('wallets')->get();
        $this->assertCount(1, $clientsWithWallets, 'Should have exactly 1 client in tenant A');

        $clientAWithWallets = $clientsWithWallets->first();
        $this->assertEquals($clientA->id, $clientAWithWallets->id, 'Should be Client A');
        $this->assertCount(1, $clientAWithWallets->wallets, 'Should have exactly 1 wallet');
        $this->assertEquals($walletA->id, $clientAWithWallets->wallets->first()->id, 'Should be Wallet A');

        // Switch to tenant B and verify only B's wallets are loaded
        $this->switchTenant($this->tenantB);
        $clientsWithWalletsB = Client::with('wallets')->get();
        $this->assertCount(1, $clientsWithWalletsB, 'Should have exactly 1 client in tenant B');
        $this->assertCount(1, $clientsWithWalletsB->first()->wallets, 'Should have exactly 1 wallet');
    }

    /**
     * Test: load() eager loading respects tenant scope
     *
     * When using load() on an already-fetched model,
     * the scope should still apply to loaded relations.
     */
    public function test_load_eager_loading_respects_scope(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA1 = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A1',
        ]);
        $walletA2 = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A2',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);
        $walletB = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B',
        ]);

        // Switch to tenant A
        $this->switchTenant($this->tenantA);

        // Fetch client without relations, then load
        $clientAFetched = Client::find($clientA->id);
        $this->assertFalse($clientAFetched->relationLoaded('wallets'), 'Wallets should not be loaded yet');

        $clientAFetched->load('wallets');
        $this->assertTrue($clientAFetched->relationLoaded('wallets'), 'Wallets should now be loaded');
        $this->assertCount(2, $clientAFetched->wallets, 'Should have exactly 2 wallets');
        $walletIds = $clientAFetched->wallets->pluck('id')->toArray();
        $this->assertContains($walletA1->id, $walletIds, 'Wallet A1 should be loaded');
        $this->assertContains($walletA2->id, $walletIds, 'Wallet A2 should be loaded');
        $this->assertNotContains($walletB->id, $walletIds, 'Wallet B should NOT be loaded');
    }

    /**
     * Test: whereHas() only counts tenant records
     *
     * When using whereHas() to filter by related model existence,
     * only tenant records should be counted.
     */
    public function test_whereHas_only_counts_tenant_records(): void
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

        // Switch to tenant A
        $this->switchTenant($this->tenantA);

        // Count clients that have wallets
        $clientsWithWallets = Client::whereHas('wallets')->count();
        $this->assertEquals(1, $clientsWithWallets, 'Should have exactly 1 client with wallets in tenant A');

        // Switch to tenant B
        $this->switchTenant($this->tenantB);
        $clientsWithWalletsB = Client::whereHas('wallets')->count();
        $this->assertEquals(1, $clientsWithWalletsB, 'Should have exactly 1 client with wallets in tenant B');
    }

    /**
     * Test: withCount() doesn't leak cross-tenant counts
     *
     * When using withCount(), the count should reflect only
     * related records from the active tenant.
     */
    public function test_withCount_doesnt_leak_cross_tenant_counts(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA1 = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A1',
        ]);
        $walletA2 = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A2',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);
        $walletB1 = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B1',
        ]);
        $walletB2 = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B2',
        ]);

        // Switch to tenant A and count wallets per client
        $this->switchTenant($this->tenantA);
        $clientsWithCounts = Client::withCount('wallets')->get();
        $this->assertCount(1, $clientsWithCounts, 'Should have exactly 1 client');
        $this->assertEquals(2, $clientsWithCounts->first()->wallets_count, 'Client A should have 2 wallets');

        // Switch to tenant B
        $this->switchTenant($this->tenantB);
        $clientsWithCountsB = Client::withCount('wallets')->get();
        $this->assertCount(1, $clientsWithCountsB, 'Should have exactly 1 client');
        $this->assertEquals(2, $clientsWithCountsB->first()->wallets_count, 'Client B should have 2 wallets');
    }

    /**
     * Test: Model::count() only counts tenant wallets
     *
     * When calling Wallet::count(), only wallets from the active
     * tenant should be counted.
     */
    public function test_wallet_count_only_counts_tenant_wallets(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA1 = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A1',
        ]);
        $walletA2 = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A2',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);
        $walletB1 = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B1',
        ]);
        $walletB2 = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B2',
        ]);
        $walletB3 = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B3',
        ]);

        // Switch to tenant A and count
        $this->switchTenant($this->tenantA);
        $countA = Wallet::count();
        $this->assertEquals(2, $countA, 'Tenant A should have exactly 2 wallets');

        // Switch to tenant B and count
        $this->switchTenant($this->tenantB);
        $countB = Wallet::count();
        $this->assertEquals(3, $countB, 'Tenant B should have exactly 3 wallets');
    }

    /**
     * Test: sum() aggregate only sums tenant entries
     *
     * When calling sum() on LedgerEntry::hours, only entries
     * from the active tenant should be summed.
     */
    public function test_ledger_sum_only_sums_tenant_entries(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A',
        ]);
        $entryA1 = $this->createModelInTenant($this->tenantA, LedgerEntry::class, [
            'wallet_id' => $walletA->id,
            'hours' => 10,
            'title' => 'Entry A1',
        ]);
        $entryA2 = $this->createModelInTenant($this->tenantA, LedgerEntry::class, [
            'wallet_id' => $walletA->id,
            'hours' => 20,
            'title' => 'Entry A2',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);
        $walletB = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B',
        ]);
        $entryB1 = $this->createModelInTenant($this->tenantB, LedgerEntry::class, [
            'wallet_id' => $walletB->id,
            'hours' => 100,
            'title' => 'Entry B1',
        ]);

        // Switch to tenant A and sum
        $this->switchTenant($this->tenantA);
        $sumA = LedgerEntry::sum('hours');
        $this->assertEquals(30, $sumA, 'Tenant A sum should be 30 (10+20)');

        // Switch to tenant B and sum
        $this->switchTenant($this->tenantB);
        $sumB = LedgerEntry::sum('hours');
        $this->assertEquals(100, $sumB, 'Tenant B sum should be 100');
    }

    /**
     * Test: relation count respects tenant context
     *
     * When calling $client->wallets()->count(), only wallets
     * from the active tenant should be counted.
     */
    public function test_count_through_relation_respects_tenant(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA1 = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A1',
        ]);
        $walletA2 = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Wallet A2',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);
        $walletB1 = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Wallet B1',
        ]);

        // Switch to tenant A
        $this->switchTenant($this->tenantA);
        $clientAReloaded = Client::find($clientA->id);
        $countA = $clientAReloaded->wallets()->count();
        $this->assertEquals(2, $countA, 'Client A should have exactly 2 wallets');

        // Switch to tenant B
        $this->switchTenant($this->tenantB);
        $clientBReloaded = Client::find($clientB->id);
        $countB = $clientBReloaded->wallets()->count();
        $this->assertEquals(1, $countB, 'Client B should have exactly 1 wallet');
    }

    /**
     * Test: aggregate with where clause respects tenant
     *
     * When calling Wallet::where(...)->count(), the where clause
     * should work in conjunction with the tenant scope.
     */
    public function test_aggregate_with_where_clause_respects_tenant(): void
    {
        $clientA = $this->createModelInTenant($this->tenantA, Client::class, ['name' => 'Client A']);
        $walletA1 = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Premium Wallet',
        ]);
        $walletA2 = $this->createModelInTenant($this->tenantA, Wallet::class, [
            'client_id' => $clientA->id,
            'name' => 'Basic Wallet',
        ]);

        $clientB = $this->createModelInTenant($this->tenantB, Client::class, ['name' => 'Client B']);
        $walletB1 = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Premium Wallet',
        ]);
        $walletB2 = $this->createModelInTenant($this->tenantB, Wallet::class, [
            'client_id' => $clientB->id,
            'name' => 'Premium Wallet',
        ]);

        // Switch to tenant A and count wallets containing 'Premium'
        $this->switchTenant($this->tenantA);
        $premiumCountA = Wallet::where('name', 'like', '%Premium%')->count();
        $this->assertEquals(1, $premiumCountA, 'Tenant A should have exactly 1 premium wallet');

        // Switch to tenant B and count wallets containing 'Premium'
        $this->switchTenant($this->tenantB);
        $premiumCountB = Wallet::where('name', 'like', '%Premium%')->count();
        $this->assertEquals(2, $premiumCountB, 'Tenant B should have exactly 2 premium wallets');
    }
}
