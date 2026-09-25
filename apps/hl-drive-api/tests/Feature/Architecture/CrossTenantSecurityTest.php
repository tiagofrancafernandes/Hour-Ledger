<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use App\Models\Client;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Services\TenantResolver;
use Tests\TestCase;

class CrossTenantSecurityTest extends TestCase
{
    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $user1;
    protected User $user2;
    protected TenantResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(TenantResolver::class);

        $uid = uniqid('test-', true);
        $this->tenant1 = Tenant::factory()->create(['name' => 'Tenant One Test', 'slug' => 'tenant-one-' . $uid]);
        $this->tenant2 = Tenant::factory()->create(['name' => 'Tenant Two Test', 'slug' => 'tenant-two-' . $uid]);

        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();

        $this->user1->tenants()->attach($this->tenant1->id);
        $this->user2->tenants()->attach($this->tenant2->id);
    }

    protected function tearDown(): void
    {
        $this->resolver->clear();
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function userCannotSeeWalletFromOtherTenant()
    {
        // Dado: carteira em tenant1
        $this->resolver->setTenantId($this->tenant1->id);
        $client1 = Client::factory()->create(['tenant_id' => $this->tenant1->id]);
        $wallet1 = Wallet::factory()->create(['tenant_id' => $this->tenant1->id, 'client_id' => $client1->id]);
        $wallet1Id = $wallet1->id;

        // Quando: user2 em tenant2 tenta acessar wallet1
        $this->resolver->clear();
        $this->resolver->setTenantId($this->tenant2->id);
        $foundWallet = Wallet::find($wallet1Id);

        // Então: null (global scope protege)
        $this->assertNull($foundWallet, 'User from other tenant cannot access wallet');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function walletListRespectsTenantIsolation()
    {
        // Dado: wallets em tenant1 e tenant2
        $this->resolver->setTenantId($this->tenant1->id);
        $client1 = Client::factory()->create(['tenant_id' => $this->tenant1->id]);
        $wallet1 = Wallet::factory()->create(['tenant_id' => $this->tenant1->id, 'client_id' => $client1->id]);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->tenant2->id);
        $client2 = Client::factory()->create(['tenant_id' => $this->tenant2->id]);
        $wallet2 = Wallet::factory()->create(['tenant_id' => $this->tenant2->id, 'client_id' => $client2->id]);

        // Quando: listar wallets em tenant2
        $walletsT2 = Wallet::query()->get();

        // Então: wallet1 não aparece
        $this->assertFalse(
            $walletsT2->pluck('id')->contains($wallet1->id),
            'Wallet from tenant 1 should not appear in tenant 2'
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function rawQueryStillRespectsTenantIsolation()
    {
        // Dado: wallet em tenant1
        $this->resolver->setTenantId($this->tenant1->id);
        $client1 = Client::factory()->create(['tenant_id' => $this->tenant1->id]);
        $wallet1 = Wallet::factory()->create(['tenant_id' => $this->tenant1->id, 'client_id' => $client1->id]);
        $wallet1Id = $wallet1->id;

        // Quando: user2 tenta raw query
        $this->resolver->clear();
        $this->resolver->setTenantId($this->tenant2->id);

        // Então: global scope ainda protege
        $foundByEloquent = Wallet::whereRaw('id = ?', [$wallet1Id])->first();
        $this->assertNull($foundByEloquent);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function changingTenantContextIsolatesData()
    {
        // Dado: dados em tenant1 e tenant2
        $this->resolver->setTenantId($this->tenant1->id);
        $client1 = Client::factory()->create(['tenant_id' => $this->tenant1->id]);
        $wallet1 = Wallet::factory()->create(['tenant_id' => $this->tenant1->id, 'client_id' => $client1->id]);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->tenant2->id);
        $client2 = Client::factory()->create(['tenant_id' => $this->tenant2->id]);
        $wallet2 = Wallet::factory()->create(['tenant_id' => $this->tenant2->id, 'client_id' => $client2->id]);

        // Quando: voltar para tenant1
        $this->resolver->clear();
        $this->resolver->setTenantId($this->tenant1->id);
        $wallets = Wallet::query()->get();

        // Então: vê apenas wallet1
        $this->assertCount(1, $wallets);
        $this->assertTrue($wallets->pluck('id')->contains($wallet1->id));
        $this->assertFalse($wallets->pluck('id')->contains($wallet2->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function wrongTenantIdBlocksAccess()
    {
        // Dado: user1 em tenant1
        $user1 = User::factory()->create();
        $user1->tenants()->attach($this->tenant1->id);

        // Quando: verificar acesso a tenant2 (que user1 não tem)
        // Então: acesso negado
        $hasAccess = $user1->tenants()->where('tenant_id', $this->tenant2->id)->exists();

        $this->assertFalse($hasAccess, 'User cannot access tenant they are not member of');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function tenantIsolationPersistsAcrossConnectionChanges()
    {
        // Dado: wallets criados em tenants diferentes
        $this->resolver->setTenantId($this->tenant1->id);
        $client1 = Client::factory()->create(['tenant_id' => $this->tenant1->id]);
        $wallet1 = Wallet::factory()->create(['tenant_id' => $this->tenant1->id, 'client_id' => $client1->id]);

        $this->resolver->clear();
        $this->resolver->setTenantId($this->tenant2->id);
        $client2 = Client::factory()->create(['tenant_id' => $this->tenant2->id]);
        $wallet2 = Wallet::factory()->create(['tenant_id' => $this->tenant2->id, 'client_id' => $client2->id]);

        // Quando: contar wallets em cada tenant múltiplas vezes
        $this->resolver->clear();
        $this->resolver->setTenantId($this->tenant1->id);
        $countT1First = Wallet::query()->count();

        $this->resolver->clear();
        $this->resolver->setTenantId($this->tenant2->id);
        $countT2 = Wallet::query()->count();

        $this->resolver->clear();
        $this->resolver->setTenantId($this->tenant1->id);
        $countT1Again = Wallet::query()->count();

        // Então: isolation persiste após mudanças de contexto
        $this->assertEquals(1, $countT1First);
        $this->assertEquals(1, $countT2);
        $this->assertEquals(1, $countT1Again);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function contextSwitchIsExplicitAndSafe()
    {
        // Dado: user consultando dados
        $this->resolver->setTenantId($this->tenant1->id);
        $initialContext = $this->resolver->getTenantId();

        // Quando: testar múltiplas mudanças
        $this->resolver->clear();
        $this->resolver->setTenantId($this->tenant2->id);
        $context2 = $this->resolver->getTenantId();

        $this->resolver->clear();
        $this->resolver->setTenantId($this->tenant1->id);
        $context1Again = $this->resolver->getTenantId();

        // Então: contexto é previsível
        $this->assertEquals($this->tenant1->id, $initialContext);
        $this->assertEquals($this->tenant2->id, $context2);
        $this->assertEquals($this->tenant1->id, $context1Again);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function multipleTenantsNeverMixInSingleQuery()
    {
        // Dado: 3 tenants com dados
        $tenants = [];
        $wallets = [];
        $uid = uniqid('test-', true);

        for ($i = 1; $i <= 3; $i++) {
            $tenant = Tenant::factory()->create(['slug' => 'test-tenant-' . $i . '-' . $uid]);
            $tenants[] = $tenant;

            $this->resolver->clear();
            $this->resolver->setTenantId($tenant->id);
            $client = Client::factory()->create(['tenant_id' => $tenant->id]);
            $wallet = Wallet::factory()->create(['tenant_id' => $tenant->id, 'client_id' => $client->id]);
            $wallets[] = $wallet;
        }

        // Quando: query em cada context
        $this->resolver->clear();
        $this->resolver->setTenantId($tenants[0]->id);
        $walletsT1 = Wallet::query()->get();

        // Então: apenas wallets[0] aparece em tenant 1
        $this->assertTrue($walletsT1->pluck('id')->contains($wallets[0]->id));
        $this->assertFalse($walletsT1->pluck('id')->contains($wallets[1]->id));
        $this->assertFalse($walletsT1->pluck('id')->contains($wallets[2]->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function findByIdRespectsTenantBoundary()
    {
        // Dado: wallet em tenant1
        $this->resolver->setTenantId($this->tenant1->id);
        $client1 = Client::factory()->create(['tenant_id' => $this->tenant1->id]);
        $wallet1 = Wallet::factory()->create(['tenant_id' => $this->tenant1->id, 'client_id' => $client1->id]);

        // Quando: user de tenant2 tenta find
        $this->resolver->clear();
        $this->resolver->setTenantId($this->tenant2->id);
        $found = Wallet::find($wallet1->id);

        // Então: não encontra (find é seguro com global scope)
        $this->assertNull($found, 'Wallet::find() respects tenant isolation');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function crossTenantDirectIdQueryBlocked()
    {
        // Dado: wallet1 em tenant1, tentamos consultá-lo via raw ID
        $this->resolver->setTenantId($this->tenant1->id);
        $client1 = Client::factory()->create(['tenant_id' => $this->tenant1->id]);
        $wallet1 = Wallet::factory()->create(['tenant_id' => $this->tenant1->id, 'client_id' => $client1->id]);
        $wallet1Id = $wallet1->id;

        // Quando: outro tenant tenta buscar diretamente por ID via query builder
        $this->resolver->clear();
        $this->resolver->setTenantId($this->tenant2->id);
        $directQuery = Wallet::where('id', $wallet1Id)->first();

        // Então: global scope ainda bloqueia mesmo com query builder explícito
        $this->assertNull($directQuery, 'Direct where() query respects tenant boundary');
    }
}
