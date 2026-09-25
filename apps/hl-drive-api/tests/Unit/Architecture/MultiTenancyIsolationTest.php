<?php

namespace Tests\Unit\Architecture;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\Wallet;
use App\Services\TenantResolver;
use Tests\TestCase;

class MultiTenancyIsolationTest extends TestCase
{
    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected Client $client1;
    protected Client $client2;
    protected TenantResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(TenantResolver::class);

        // Criar dois tenants separados
        $this->tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $this->tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        // Clients em tenants diferentes
        $this->resolver->setTenantId($this->tenant1->id);
        $this->client1 = Client::factory()->create();

        $this->resolver->setTenantId($this->tenant2->id);
        $this->client2 = Client::factory()->create();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function walletsInTenant1AreNotVisibleInTenant2()
    {
        // Dado: wallets em tenants diferentes
        $this->resolver->setTenantId($this->tenant1->id);
        $wallet1 = Wallet::factory()->create(['client_id' => $this->client1->id]);

        $this->resolver->setTenantId($this->tenant2->id);
        $wallet2 = Wallet::factory()->create(['client_id' => $this->client2->id]);

        // Quando: listamos wallets em tenant_1
        $this->resolver->setTenantId($this->tenant1->id);
        $walletsInT1 = Wallet::query()->get();

        // Então: wallet2 não aparece, apenas wallet1
        $this->assertTrue(
            $walletsInT1->pluck('id')->contains($wallet1->id),
            'Wallet from tenant 1 should be visible'
        );
        $this->assertFalse(
            $walletsInT1->pluck('id')->contains($wallet2->id),
            'Wallet from tenant 2 should not be visible in tenant 1'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function schemaIsSeparateForEachTenant()
    {
        // Dado: dois tenants com contextos definidos
        $this->resolver->setTenantId($this->tenant1->id);
        $schema1 = $this->resolver->getSchema();

        $this->resolver->setTenantId($this->tenant2->id);
        $schema2 = $this->resolver->getSchema();

        // Quando: verificamos que são diferentes
        // Então: schemas são isolados e diferentes
        $this->assertNotEquals(
            $schema1,
            $schema2,
            'Tenant schemas should be different'
        );
        $this->assertTrue(
            str_contains($schema1, (string) $this->tenant1->id),
            'Schema 1 should contain tenant 1 ID'
        );
        $this->assertTrue(
            str_contains($schema2, (string) $this->tenant2->id),
            'Schema 2 should contain tenant 2 ID'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function modelGlobalScopePreventsCrossTenantAccess()
    {
        // Dado: wallet em tenant_1
        $this->resolver->setTenantId($this->tenant1->id);
        $wallet1 = Wallet::factory()->for($this->client1, 'client')->create();
        $wallet1Id = $wallet1->id;

        // Quando: tentamos acessar em tenant_2
        $this->resolver->setTenantId($this->tenant2->id);
        $walletTryingToAccess = Wallet::find($wallet1Id);

        // Então: não encontra a wallet (isolamento funciona via global scope)
        $this->assertNull(
            $walletTryingToAccess,
            'Global scope should prevent cross-tenant access'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function tenantContextIsRequestIsolated()
    {
        // Dado: tenant_1 no contexto
        $this->resolver->setTenantId($this->tenant1->id);
        $contextId1 = $this->resolver->getTenantId();

        // Quando: mudamos para tenant_2
        $this->resolver->setTenantId($this->tenant2->id);
        $contextId2 = $this->resolver->getTenantId();

        // Então: contexto é isolado e correto para cada tenant
        $this->assertNotEquals($contextId1, $contextId2);
        $this->assertEquals($this->tenant1->id, $contextId1);
        $this->assertEquals($this->tenant2->id, $contextId2);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function resolvingMissingTenantReturnsNull()
    {
        // Dado: sem contexto de tenant
        $this->resolver->clear();

        // Quando: tentamos obter o ID do tenant
        $tenantId = $this->resolver->getTenantId();

        // Então: retorna null quando nenhum tenant está definido
        $this->assertNull($tenantId, 'Tenant context should be empty after clear');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function walletQueriesInDifferentContextsAreIsolated()
    {
        // Dado: wallet em tenant_1
        $this->resolver->setTenantId($this->tenant1->id);
        $wallet1 = Wallet::factory()->for($this->client1, 'client')->create();
        $wallet1Id = $wallet1->id;

        // Quando: query em tenant_2
        $this->resolver->setTenantId($this->tenant2->id);
        $notFound = Wallet::find($wallet1Id);

        // Então: não encontra (isolamento funciona)
        $this->assertNull($notFound, 'Tenant isolation prevents cross-tenant queries');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function clientsAreIsolatedByTenant()
    {
        // Dado: clients adicionais em tenants diferentes
        $this->resolver->setTenantId($this->tenant1->id);
        $client1InT1 = Client::factory()->create();

        $this->resolver->setTenantId($this->tenant2->id);
        $client1InT2 = Client::factory()->create();

        // Quando: listamos clients em tenant_1
        $this->resolver->setTenantId($this->tenant1->id);
        $clientsInT1 = Client::query()->get();

        // Então: client from T2 não aparece
        $this->assertTrue(
            $clientsInT1->pluck('id')->contains($client1InT1->id),
            'Client from tenant 1 should be visible'
        );
        $this->assertFalse(
            $clientsInT1->pluck('id')->contains($client1InT2->id),
            'Client from tenant 2 should not be visible in tenant 1'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function resolverHasTenantReturnsCorrectState()
    {
        // Dado: resolver sem tenant
        $this->resolver->clear();
        $hasNoTenant = $this->resolver->hasTenant();

        // Quando: definimos um tenant
        $this->resolver->setTenantId($this->tenant1->id);
        $hasTenant = $this->resolver->hasTenant();

        // Então: estado correto
        $this->assertFalse($hasNoTenant, 'hasTenant should be false when no tenant is set');
        $this->assertTrue($hasTenant, 'hasTenant should be true when tenant is set');
    }
}
