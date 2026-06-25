<?php

declare(strict_types=1);

namespace Tests\Feature\MultiTenancy;

use App\Models\Client;
use App\Models\LedgerEntry;
use App\Models\Wallet;
use Tests\Feature\TenantTestCase;

/**
 * ScopeTest
 *
 * Validates that TenantScope is correctly applied to various query operations.
 * Tests that scope filtering works with where clauses, joins, relations, counts, and exists checks.
 *
 * Coverage:
 * - Where clause respects tenant scope
 * - Join operations don't leak data
 * - Relation queries don't leak data
 * - Count reflects only tenant data
 * - Exists checks only in tenant
 */
class ScopeTest extends TenantTestCase
{
    /**
     * Test: Where clause respects tenant scope
     *
     * Validates that when using where() on a model with BelongsToTenant,
     * the TenantScope is still applied in addition to the where clause.
     */
    public function test_where_clause_respects_tenant_scope(): void
    {
        $this->switchTenant($this->tenantA);
        $clientA = new Client(['name' => 'Premium Client']);
        $clientA->save();

        $this->switchTenant($this->tenantB);
        $clientB = new Client(['name' => 'Premium Client']);
        $clientB->save();

        $this->switchTenant($this->tenantA);
        $result = Client::where('name', 'Premium Client')->get();

        $this->assertEquals(1, $result->count(), 'Should find exactly 1 Premium Client in tenant A');
        $this->assertEquals($clientA->id, $result->first()->id, 'Should find the correct client from tenant A');
        $this->assertFalse(
            $result->contains('id', $clientB->id),
            'Should NOT include client from tenant B even with matching name'
        );

        // When we switch to tenant B, we should only get clientB
        $this->switchTenant($this->tenantB);
        $result = Client::where('name', 'Premium Client')->get();

        $this->assertEquals(1, $result->count(), 'Should find exactly 1 Premium Client in tenant B');
        $this->assertEquals($clientB->id, $result->first()->id, 'Should find the correct client from tenant B');
        $this->assertFalse(
            $result->contains('id', $clientA->id),
            'Should NOT include client from tenant A even with matching name'
        );
    }

    /**
     * Test: Join does not leak data between tenants
     *
     * Validates that when using joins, the TenantScope is still applied
     * and prevents cross-tenant data leakage through join operations.
     */
    public function test_join_does_not_leak_data(): void
    {
        $this->switchTenant($this->tenantA);
        $clientA = new Client(['name' => 'Client A']);
        $clientA->save();
        $walletA = new Wallet(['client_id' => $clientA->id, 'name' => 'Wallet A']);
        $walletA->save();

        $this->switchTenant($this->tenantB);
        $clientB = new Client(['name' => 'Client B']);
        $clientB->save();
        $walletB = new Wallet(['client_id' => $clientB->id, 'name' => 'Wallet B']);
        $walletB->save();

        $this->switchTenant($this->tenantA);

        $result = Wallet::join('clients', 'wallets.client_id', '=', 'clients.id')
            ->select('wallets.*')
            ->get();

        $this->assertEquals(1, $result->count(), 'Tenant A should see only 1 wallet in join');
        $this->assertEquals($walletA->id, $result->first()->id, 'Should see wallet A in join');

        // Switch to tenant B
        $this->switchTenant($this->tenantB);

        $result = Wallet::join('clients', 'wallets.client_id', '=', 'clients.id')
            ->select('wallets.*')
            ->get();

        $this->assertEquals(1, $result->count(), 'Tenant B should see only 1 wallet in join');
        $this->assertEquals($walletB->id, $result->first()->id, 'Should see wallet B in join');
        $this->assertNotEquals(
            $walletA->id,
            $result->first()->id,
            'Should NOT see wallet A in tenant B join'
        );
    }

    /**
     * Test: Relations do not leak data between tenants
     *
     * Validates that when loading related models through relationships,
     * the TenantScope is applied and prevents cross-tenant data leakage.
     */
    public function test_relations_do_not_leak_data(): void
    {
        $this->switchTenant($this->tenantA);
        $clientA = new Client(['name' => 'Client A']);
        $clientA->save();
        $walletA = new Wallet(['client_id' => $clientA->id, 'name' => 'Wallet A']);
        $walletA->save();
        $entryA = new LedgerEntry(['wallet_id' => $walletA->id, 'hours' => 10.50]);
        $entryA->save();

        $this->switchTenant($this->tenantB);
        $clientB = new Client(['name' => 'Client B']);
        $clientB->save();
        $walletB = new Wallet(['client_id' => $clientB->id, 'name' => 'Wallet B']);
        $walletB->save();
        $entryB = new LedgerEntry(['wallet_id' => $walletB->id, 'hours' => 10.50]);
        $entryB->save();

        $this->switchTenant($this->tenantA);

        $wallet = Wallet::find($walletA->id);
        $this->assertNotNull($wallet, 'Wallet A should be found in tenant A');

        $entries = $wallet->ledgerEntries()->get();
        $this->assertEquals(1, $entries->count(), 'Wallet A should have 1 ledger entry');
        $this->assertEquals($entryA->id, $entries->first()->id, 'Should see entry A in relation');

        // Test: Load wallet relations in tenant B
        $this->switchTenant($this->tenantB);

        $wallet = Wallet::find($walletB->id);
        $this->assertNotNull($wallet, 'Wallet B should be found in tenant B');

        $entries = $wallet->ledgerEntries()->get();
        $this->assertEquals(1, $entries->count(), 'Wallet B should have 1 ledger entry');
        $this->assertEquals($entryB->id, $entries->first()->id, 'Should see entry B in relation');

        // Verify we can't see tenant A's wallet in tenant B
        $this->assertNull(
            Wallet::find($walletA->id),
            'Wallet A should NOT be found in tenant B'
        );
    }

    /**
     * Test: Count reflects only tenant data
     *
     * Validates that the count() method respects TenantScope and
     * returns only the count of records in the active tenant.
     */
    public function test_count_reflects_only_tenant_data(): void
    {
        $this->switchTenant($this->tenantA);
        for ($i = 1; $i <= 3; $i++) {
            $client = new Client(['name' => "Client A-$i"]);
            $client->save();
        }

        $this->switchTenant($this->tenantB);
        for ($i = 1; $i <= 5; $i++) {
            $client = new Client(['name' => "Client B-$i"]);
            $client->save();
        }

        $this->switchTenant($this->tenantC);
        for ($i = 1; $i <= 2; $i++) {
            $client = new Client(['name' => "Client C-$i"]);
            $client->save();
        }

        $this->switchTenant($this->tenantA);
        $countA = Client::count();
        $this->assertEquals(3, $countA, 'Tenant A should have exactly 3 clients');

        // Count in tenant B
        $this->switchTenant($this->tenantB);
        $countB = Client::count();
        $this->assertEquals(5, $countB, 'Tenant B should have exactly 5 clients');

        // Count in tenant C
        $this->switchTenant($this->tenantC);
        $countC = Client::count();
        $this->assertEquals(2, $countC, 'Tenant C should have exactly 2 clients');

        // Verify total count doesn't leak between tenants
        $this->switchTenant($this->tenantA);
        $this->assertEquals(3, Client::count(), 'Should still be 3 in tenant A');
        $this->assertNotEquals(10, Client::count(), 'Should NOT sum all tenants');
    }

    /**
     * Test: Count with where clause respects tenant scope
     *
     * Validates that count() combined with where() still respects TenantScope.
     */
    public function test_count_with_where_respects_scope(): void
    {
        $this->switchTenant($this->tenantA);
        $vip = new Client(['name' => 'VIP Client']);
        $vip->save();
        for ($i = 1; $i <= 2; $i++) {
            $client = new Client(['name' => "Client A-$i"]);
            $client->save();
        }

        $this->switchTenant($this->tenantB);
        $vip = new Client(['name' => 'VIP Client']);
        $vip->save();
        for ($i = 1; $i <= 3; $i++) {
            $client = new Client(['name' => "Client B-$i"]);
            $client->save();
        }

        $this->switchTenant($this->tenantA);
        $vipCount = Client::where('name', 'VIP Client')->count();
        $this->assertEquals(1, $vipCount, 'Tenant A should have 1 VIP client');

        // Count VIP clients in tenant B
        $this->switchTenant($this->tenantB);
        $vipCount = Client::where('name', 'VIP Client')->count();
        $this->assertEquals(1, $vipCount, 'Tenant B should have 1 VIP client');
    }

    /**
     * Test: Exists checks only in tenant
     *
     * Validates that the exists() method respects TenantScope and
     * only checks for records in the active tenant.
     */
    public function test_exists_checks_only_in_tenant(): void
    {
        $this->switchTenant($this->tenantA);
        $clientA = new Client(['name' => 'Client A']);
        $clientA->save();

        $this->switchTenant($this->tenantB);
        $clientB = new Client(['name' => 'Client B']);
        $clientB->save();

        $this->switchTenant($this->tenantA);
        $this->assertTrue(
            Client::where('id', $clientA->id)->exists(),
            'Client A should exist in tenant A'
        );
        $this->assertFalse(
            Client::where('id', $clientB->id)->exists(),
            'Client B should NOT exist in tenant A'
        );

        // Check existence in tenant B
        $this->switchTenant($this->tenantB);
        $this->assertTrue(
            Client::where('id', $clientB->id)->exists(),
            'Client B should exist in tenant B'
        );
        $this->assertFalse(
            Client::where('id', $clientA->id)->exists(),
            'Client A should NOT exist in tenant B'
        );
    }

    /**
     * Test: Exists with complex where conditions respects scope
     *
     * Validates that exists() with multiple where conditions still respects TenantScope.
     */
    public function test_exists_with_complex_conditions(): void
    {
        $this->switchTenant($this->tenantA);
        $clientA = new Client([
            'name' => 'Acme Corp',
            'email' => 'contact@acme.com',
        ]);
        $clientA->save();

        $this->switchTenant($this->tenantB);
        $clientB = new Client([
            'name' => 'Acme Corp',
            'email' => 'contact@acme.com',
            ]);
        $clientB->save();

        $this->switchTenant($this->tenantA);
        $exists = Client::where('name', 'Acme Corp')
            ->where('email', 'contact@acme.com')
            ->exists();

        $this->assertTrue(
            $exists,
            'Client should exist with matching conditions in tenant A'
        );

        // Same query should not find tenant B's client in tenant A
        $existsInB = Client::where('id', $clientB->id)
            ->where('name', 'Acme Corp')
            ->where('email', 'contact@acme.com')
            ->exists();

        $this->assertFalse(
            $existsInB,
            'Client from tenant B should NOT exist in tenant A query'
        );
    }
}
