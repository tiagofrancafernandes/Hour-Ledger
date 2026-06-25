<?php

declare(strict_types=1);

namespace Tests\Feature\MultiTenancy;

use App\Models\Client;
use App\Models\Wallet;
use App\Services\TenantResolver;
use Tests\Feature\TenantTestCase;

/**
 * ContextTest
 *
 * Validates that tenant context switching and isolation work correctly
 * across sequential queries. Tests the TenantResolver's role in maintaining
 * proper context isolation.
 *
 * Coverage:
 * - Changing tenant changes query results
 * - Null context returns no data
 * - Invalid context returns no data
 * - Sequential queries maintain isolation
 */
class ContextTest extends TenantTestCase
{
    /**
     * Test: Changing tenant changes query results
     *
     * Validates that when we switch the active tenant, subsequent queries
     * immediately return data for the new tenant.
     */
    public function test_changing_tenant_changes_query_results(): void
    {
        // Create clients for each tenant
        $clientA = new Client(['name' => 'Client A', 'tenant_id' => $this->tenantA->id]);
        $clientA->saveQuietly();

        $clientB = new Client(['name' => 'Client B', 'tenant_id' => $this->tenantB->id]);
        $clientB->saveQuietly();

        // Query in tenant A
        $this->switchTenant($this->tenantA);
        $resultA = Client::first();
        $this->assertNotNull($resultA, 'Should find a client in tenant A');
        $this->assertEquals($clientA->id, $resultA->id, 'Should find the correct client in tenant A');

        // Verify context is set to tenant A
        $this->assertActiveTenant($this->tenantA);

        // Switch to tenant B
        $this->switchTenant($this->tenantB);
        $resultB = Client::first();
        $this->assertNotNull($resultB, 'Should find a client in tenant B');
        $this->assertEquals($clientB->id, $resultB->id, 'Should find the correct client in tenant B');

        // Verify context is set to tenant B
        $this->assertActiveTenant($this->tenantB);

        // Switch back to tenant A
        $this->switchTenant($this->tenantA);
        $resultA2 = Client::first();
        $this->assertEquals($clientA->id, $resultA2->id, 'Should find the correct client back in tenant A');

        // Verify context is back to tenant A
        $this->assertActiveTenant($this->tenantA);
    }

    /**
     * Test: Null context returns no data
     *
     * Validates that when there is no active tenant context,
     * queries return an empty result set (fail-closed security).
     * This is critical for preventing accidental data leakage.
     */
    public function test_null_context_returns_no_data(): void
    {
        // Create some data
        for ($i = 1; $i <= 3; $i++) {
            $client = new Client(['name' => "Client A-$i", 'tenant_id' => $this->tenantA->id]);
            $client->saveQuietly();
        }

        for ($i = 1; $i <= 2; $i++) {
            $client = new Client(['name' => "Client B-$i", 'tenant_id' => $this->tenantB->id]);
            $client->saveQuietly();
        }

        // Clear tenant context to null
        app()->forgetInstance(TenantResolver::class);

        // When there's no context, all() should return empty
        $result = Client::all();
        $this->assertEquals(0, $result->count(), 'Should return no clients when context is null');

        // count() should also be 0
        $countResult = Client::count();
        $this->assertEquals(0, $countResult, 'Count should be 0 when context is null');

        // exists() should return false
        $existsResult = Client::exists();
        $this->assertFalse($existsResult, 'Exists should return false when context is null');

        // first() should return null
        $firstResult = Client::first();
        $this->assertNull($firstResult, 'First should return null when context is null');
    }

    /**
     * Test: Invalid context returns no data
     *
     * Validates that when the tenant context is set to a non-existent tenant ID,
     * queries return no data. This ensures that even if the context is somehow
     * corrupted or set to an invalid value, we don't leak data.
     */
    public function test_invalid_context_returns_no_data(): void
    {
        // Create some data in valid tenants
        $client = new Client(['name' => 'Client A', 'tenant_id' => $this->tenantA->id]);
        $client->saveQuietly();

        $client = new Client(['name' => 'Client B', 'tenant_id' => $this->tenantB->id]);
        $client->saveQuietly();

        // Manually set context to invalid tenant ID
        $reflection = new \ReflectionClass(TenantResolver::class);
        $tenantResolver = app(TenantResolver::class);

        $tenantIdProp = $reflection->getProperty('tenantId');
        $tenantIdProp->setAccessible(true);
        $tenantIdProp->setValue($tenantResolver, 99999);

        // Query should return empty since no client has tenant_id = 99999
        $result = Client::all();
        $this->assertEquals(0, $result->count(), 'Should return no clients with invalid tenant context');

        // count() should also be 0
        $countResult = Client::count();
        $this->assertEquals(0, $countResult, 'Count should be 0 with invalid tenant context');

        // exists() should return false
        $existsResult = Client::exists();
        $this->assertFalse($existsResult, 'Exists should return false with invalid tenant context');
    }

    /**
     * Test: Sequential queries maintain isolation
     *
     * Validates that multiple queries in sequence on the same tenant
     * all return consistent, isolated results. This ensures that
     * the context is maintained properly throughout the request lifecycle.
     */
    public function test_sequential_queries_maintain_isolation(): void
    {
        // Create data for all tenants
        $clientA1 = new Client(['name' => 'Client A-1', 'tenant_id' => $this->tenantA->id]);
        $clientA1->saveQuietly();

        (new Client(['name' => 'Client A-2', 'tenant_id' => $this->tenantA->id]))->saveQuietly();

        (new Wallet(['client_id' => $clientA1->id, 'name' => 'Wallet A', 'tenant_id' => $this->tenantA->id]))->saveQuietly();

        $clientB1 = new Client(['name' => 'Client B-1', 'tenant_id' => $this->tenantB->id]);
        $clientB1->saveQuietly();

        (new Client(['name' => 'Client B-2', 'tenant_id' => $this->tenantB->id]))->saveQuietly();

        (new Wallet(['client_id' => $clientB1->id, 'name' => 'Wallet B', 'tenant_id' => $this->tenantB->id]))->saveQuietly();

        // Test sequential queries in tenant A
        $this->switchTenant($this->tenantA);

        // Query 1: Count clients
        $clientCountA = Client::count();
        $this->assertEquals(2, $clientCountA, 'First query: Tenant A has 2 clients');

        // Query 2: Get first client
        $firstClientA = Client::first();
        $this->assertNotNull($firstClientA, 'Second query: Found a client in Tenant A');
        $this->assertEquals($this->tenantA->id, $firstClientA->tenant_id, 'Client belongs to Tenant A');

        // Query 3: Count wallets
        $walletCountA = Wallet::count();
        $this->assertEquals(1, $walletCountA, 'Third query: Tenant A has 1 wallet');

        // Query 4: Verify by IDs
        $clientExists = Client::where('id', $clientA1->id)->exists();
        $this->assertTrue($clientExists, 'Fourth query: Client A1 exists in Tenant A');

        $clientBExists = Client::where('id', $clientB1->id)->exists();
        $this->assertFalse($clientBExists, 'Fifth query: Client B1 does NOT exist in Tenant A');

        // Switch to tenant B and repeat
        $this->switchTenant($this->tenantB);

        // Query 1: Count clients
        $clientCountB = Client::count();
        $this->assertEquals(2, $clientCountB, 'Sixth query: Tenant B has 2 clients');

        // Query 2: Get first client
        $firstClientB = Client::first();
        $this->assertNotNull($firstClientB, 'Seventh query: Found a client in Tenant B');
        $this->assertEquals($this->tenantB->id, $firstClientB->tenant_id, 'Client belongs to Tenant B');

        // Query 3: Count wallets
        $walletCountB = Wallet::count();
        $this->assertEquals(1, $walletCountB, 'Eighth query: Tenant B has 1 wallet');

        // Query 4: Verify by IDs
        $clientBExists = Client::where('id', $clientB1->id)->exists();
        $this->assertTrue($clientBExists, 'Ninth query: Client B1 exists in Tenant B');

        $clientAExists = Client::where('id', $clientA1->id)->exists();
        $this->assertFalse($clientAExists, 'Tenth query: Client A1 does NOT exist in Tenant B');
    }

    /**
     * Test: Context persists across multiple model queries
     *
     * Validates that when we set a tenant context, it remains active
     * for multiple consecutive queries on different models.
     */
    public function test_context_persists_across_model_queries(): void
    {
        // Setup: Create related data
        $clientA = new Client(['name' => 'Client A', 'tenant_id' => $this->tenantA->id]);
        $clientA->saveQuietly();
        $walletA = new Wallet(['client_id' => $clientA->id, 'name' => 'Wallet A', 'tenant_id' => $this->tenantA->id]);
        $walletA->saveQuietly();

        $clientB = new Client(['name' => 'Client B', 'tenant_id' => $this->tenantB->id]);
        $clientB->saveQuietly();
        $walletB = new Wallet(['client_id' => $clientB->id, 'name' => 'Wallet B', 'tenant_id' => $this->tenantB->id]);
        $walletB->saveQuietly();

        // Set context to tenant A
        $this->switchTenant($this->tenantA);
        $this->assertActiveTenant($this->tenantA);

        // Query 1: Clients
        $clientResult = Client::first();
        $this->assertEquals($clientA->id, $clientResult->id, 'Client query returns A');

        // Verify context is still A
        $this->assertActiveTenant($this->tenantA);

        // Query 2: Wallets (different model)
        $walletResult = Wallet::first();
        $this->assertEquals($walletA->id, $walletResult->id, 'Wallet query returns A');

        // Verify context is still A
        $this->assertActiveTenant($this->tenantA);

        // Query 3: Another Client query
        $clientCount = Client::count();
        $this->assertEquals(1, $clientCount, 'Client count is 1 in A');

        // Verify context is still A
        $this->assertActiveTenant($this->tenantA);

        // Now switch to B
        $this->switchTenant($this->tenantB);
        $this->assertActiveTenant($this->tenantB);

        // Query 4: Clients in B
        $clientResult = Client::first();
        $this->assertEquals($clientB->id, $clientResult->id, 'Client query returns B after switch');

        // Verify context is B
        $this->assertActiveTenant($this->tenantB);

        // Query 5: Wallets in B
        $walletResult = Wallet::first();
        $this->assertEquals($walletB->id, $walletResult->id, 'Wallet query returns B after switch');

        // Verify context is B
        $this->assertActiveTenant($this->tenantB);
    }
}
