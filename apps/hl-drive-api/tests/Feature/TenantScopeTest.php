<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LedgerEntry;
use App\Models\Tenant;
use App\Models\Wallet;
use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TenantScopeTest validates that queries are automatically filtered by tenant_id.
 *
 * These tests ensure:
 * - Global scope functions correctly
 * - No tenant context returns empty results
 * - With tenant context returns only tenant's data
 * - Cross-tenant queries are prevented
 * - Models with trait behave correctly
 */
class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    protected TenantResolver $tenantResolver;

    protected Tenant $tenant1;

    protected Tenant $tenant2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantResolver = app(TenantResolver::class);

        // Create test tenants
        $this->tenant1 = Tenant::factory()->create([
            'name' => 'Tenant One',
            'slug' => 'tenant-one',
        ]);

        $this->tenant2 = Tenant::factory()->create([
            'name' => 'Tenant Two',
            'slug' => 'tenant-two',
        ]);
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        $this->tenantResolver->clear();
        parent::tearDown();
    }

    /**
     * Test that queries without tenant context return empty results.
     *
     * This is the "fail-closed" security approach: if no tenant is active,
     * queries return no data, preventing accidental global access.
     */
    public function testQueriesWithoutTenantContextReturnEmpty(): void
    {
        // Ensure no tenant is set
        $this->tenantResolver->clear();

        // Create a client for tenant1
        Client::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Client in Tenant 1',
        ]);

        // Query without tenant context should return 0 results
        $results = Client::all();

        $this->assertCount(0, $results);
    }

    /**
     * Test that queries with tenant context return only that tenant's data.
     */
    public function testQueriesWithTenantContextReturnTenantData(): void
    {
        // Set tenant1 as active
        $this->tenantResolver->setTenantId($this->tenant1->id);

        // Create clients for both tenants
        $client1 = Client::create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Client in Tenant 1',
        ]);

        // Manually create a client for tenant2 without scope
        Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Client in Tenant 2',
        ]);

        // Query with tenant1 active
        $results = Client::all();

        // Should only get tenant1's data
        $this->assertCount(1, $results);
        $this->assertEquals($client1->id, $results->first()->id);
    }

    /**
     * Test that switching tenant context properly filters data.
     */
    public function testSwitchingTenantContextFiltersDataCorrectly(): void
    {
        // Create clients for both tenants
        $client1 = Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Client in Tenant 1',
        ]);

        $client2 = Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Client in Tenant 2',
        ]);

        // Set tenant1 and verify
        $this->tenantResolver->setTenantId($this->tenant1->id);
        $results = Client::all();

        $this->assertCount(1, $results);
        $this->assertEquals($client1->id, $results->first()->id);

        // Switch to tenant2 and verify
        $this->tenantResolver->clear();
        $this->tenantResolver->setTenantId($this->tenant2->id);
        $results = Client::all();

        $this->assertCount(1, $results);
        $this->assertEquals($client2->id, $results->first()->id);
    }

    /**
     * Test that creating a record without tenant_id fails.
     *
     * The observer should prevent creation if tenant_id is not set
     * and no tenant is active.
     */
    public function testCreatingRecordWithoutTenantIdFails(): void
    {
        $this->tenantResolver->clear();

        // Attempt to create without tenant_id
        $this->expectException(\Exception::class);

        Client::create([
            'name' => 'Client without tenant',
            // tenant_id intentionally omitted
        ]);
    }

    /**
     * Test that observer auto-sets tenant_id when creating records.
     */
    public function testObserverAutoSetsTenantId(): void
    {
        // Set tenant1 as active
        $this->tenantResolver->setTenantId($this->tenant1->id);

        // Create without explicitly setting tenant_id
        // Use makeInstance to get the model before it goes through observer
        $client = Client::create([
            'name' => 'Auto-tenanted Client',
            // tenant_id will be set by observer
        ]);

        // Verify tenant_id was set by observer
        $this->assertNotNull($client->tenant_id);
        $this->assertEquals($this->tenant1->id, $client->tenant_id);
    }

    /**
     * Test that update operations respect tenant scope.
     */
    public function testUpdateOperationsRespectTenantScope(): void
    {
        // Create clients for both tenants
        $client1 = Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Client in Tenant 1',
        ]);

        Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Client in Tenant 2',
        ]);

        // Set tenant1 as active
        $this->tenantResolver->setTenantId($this->tenant1->id);

        // Update only returns tenant1's clients
        $updated = Client::where('name', 'Client in Tenant 1')->update([
            'name' => 'Updated Tenant 1 Client',
        ]);

        // Should update 1 record
        $this->assertEquals(1, $updated);

        // Verify via query
        $result = Client::where('name', 'Updated Tenant 1 Client')->first();
        $this->assertNotNull($result);
        $this->assertEquals($this->tenant1->id, $result->tenant_id);
    }

    /**
     * Test that delete operations respect tenant scope.
     */
    public function testDeleteOperationsRespectTenantScope(): void
    {
        // Create clients for both tenants
        $client1 = Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant1->id,
            'name' => 'Client in Tenant 1',
        ]);

        $client2 = Client::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant2->id,
            'name' => 'Client in Tenant 2',
        ]);

        // Set tenant1 as active
        $this->tenantResolver->setTenantId($this->tenant1->id);

        // Delete should only affect tenant1's data
        $deleted = Client::where('name', 'Client in Tenant 1')->delete();

        // Should delete 1 record
        $this->assertEquals(1, $deleted);

        // Verify tenant1's client is gone
        $this->assertNull(Client::find($client1->id));

        // Switch to tenant2 and verify client2 still exists
        $this->tenantResolver->clear();
        $this->tenantResolver->setTenantId($this->tenant2->id);
        $result = Client::find($client2->id);
        $this->assertNotNull($result);
    }

    /**
     * Test that relationships work within tenant context.
     */
    public function testRelationshipsWorkWithinTenantContext(): void
    {
        // Set tenant1 as active
        $this->tenantResolver->setTenantId($this->tenant1->id);

        // Create client
        $client = Client::create([
            'name' => 'Test Client',
        ]);

        // Create wallets for the client
        $wallet = Wallet::create([
            'client_id' => $client->id,
            'name' => 'Test Wallet',
            'currency_code' => 'USD',
        ]);

        // Query client and load wallets
        $loadedClient = Client::with('wallets')->find($client->id);

        $this->assertNotNull($loadedClient);
        $this->assertCount(1, $loadedClient->wallets);
        $this->assertEquals($wallet->id, $loadedClient->wallets->first()->id);
    }

    /**
     * Test that nested relationships are filtered by tenant.
     */
    public function testNestedRelationshipsRespectTenantScope(): void
    {
        // Create data for both tenants
        $this->tenantResolver->setTenantId($this->tenant1->id);
        $client1 = Client::create(['name' => 'Client 1']);
        $wallet1 = Wallet::create(['client_id' => $client1->id, 'name' => 'Wallet 1']);
        $entry1 = LedgerEntry::create([
            'wallet_id' => $wallet1->id,
            'hours' => 5,
            'title' => 'Entry 1',
        ]);

        $this->tenantResolver->clear();
        $this->tenantResolver->setTenantId($this->tenant2->id);
        $client2 = Client::create(['name' => 'Client 2']);
        $wallet2 = Wallet::create(['client_id' => $client2->id, 'name' => 'Wallet 2']);
        $entry2 = LedgerEntry::create([
            'wallet_id' => $wallet2->id,
            'hours' => 10,
            'title' => 'Entry 2',
        ]);

        // Query tenant1's entries
        $this->tenantResolver->clear();
        $this->tenantResolver->setTenantId($this->tenant1->id);
        $entries = LedgerEntry::with('wallet.client')->get();

        $this->assertCount(1, $entries);
        $this->assertEquals($entry1->id, $entries->first()->id);
    }

    /**
     * Test getTenantId method on model instance.
     */
    public function testGetTenantIdMethod(): void
    {
        $this->tenantResolver->setTenantId($this->tenant1->id);

        $client = Client::create(['name' => 'Test Client']);

        $this->assertEquals($this->tenant1->id, $client->getTenantId());
    }

    /**
     * Test isInTenant method on model instance.
     */
    public function testIsInTenantMethod(): void
    {
        $this->tenantResolver->setTenantId($this->tenant1->id);

        $client = Client::create(['name' => 'Test Client']);

        // Should return true for correct tenant
        $this->assertTrue($client->isInTenant($this->tenant1->id));

        // Should return false for wrong tenant
        $this->assertFalse($client->isInTenant($this->tenant2->id));
    }

    /**
     * Test that withoutGlobalScopes bypasses tenant filtering.
     */
    public function testWithoutGlobalScopesBypassesTenantFiltering(): void
    {
        // Set tenant1 to create both clients under the same tenant context
        $this->tenantResolver->setTenantId($this->tenant1->id);

        // Create client1 under tenant1 context
        $client1 = Client::create([
            'name' => 'Client in Tenant 1',
        ]);

        // Switch to tenant2 to create client2
        $this->tenantResolver->clear();
        $this->tenantResolver->setTenantId($this->tenant2->id);

        $client2 = Client::create([
            'name' => 'Client in Tenant 2',
        ]);

        // Now query without scope - should return both even with tenant1 active
        $this->tenantResolver->clear();
        $this->tenantResolver->setTenantId($this->tenant1->id);

        // Without scopes should return both
        $results = Client::withoutGlobalScopes()->get();

        $this->assertCount(2, $results);
    }
}
