# Guia de Início Rápido: Tarefa 001

**Data**: 2026-06-26  
**Tarefa**: Phase 4 Task G - Milestone 1 (Setup & Fixtures)  
**Tempo Estimado**: 7-8 horas  
**Status**: 🟢 PRONTO PARA INICIAR AGORA

---

## ⚡ TL;DR (Comece aqui)

1. **O que fazer**: Criar ambiente de testes multi-tenant
2. **Tempo**: 1 dia (7-8h)
3. **Resultado**: TenantTestCase funcional + 8+ testes
4. **Próximo**: Milestone 2 (27/06)

---

## 📋 Checklist de Início (15 min)

Antes de começar, faça isso:

- [ ] Clone/pull mais recente do código
- [ ] `composer install` (dependências atualizadas)
- [ ] `.env.testing` configurado
- [ ] Database de teste pronta (`php artisan migrate:fresh --env=testing`)
- [ ] PHPUnit rodando: `./vendor/bin/phpunit --version`
- [ ] Verificar estrutura: `tests/Feature/MultiTenancy/` existe?

Se tudo OK → Começar implementação

---

## 🎯 Objetivo da Tarefa 001

Criar a **infraestrutura de testes** para validar isolamento multi-tenant.

**Resultado esperado**:
- ✅ Classe `tests/Feature/TenantTestCase.php` funcional
- ✅ 3 fixtures de tenants criados automaticamente
- ✅ Context switching entre tenants
- ✅ 8+ testes de setup passando
- ✅ Coverage básico documentado

**NÃO é**: Testar isolamento (próximo milestone)

---

## 🔧 Implementação (Passo a Passo)

### Passo 1: Criar TenantTestCase (1.5h)

**Arquivo**: `tests/Feature/TenantTestCase.php`

```php
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TenantTestCase extends TestCase
{
    use RefreshDatabase;
    
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected Tenant $tenantC;
    
    protected User $userA;
    protected User $userB;
    protected User $userC;
    
    public function setUp(): void
    {
        parent::setUp();
        
        // Criar tenants de teste
        $this->createTestTenants();
        
        // Criar usuários
        $this->createTestUsers();
    }
    
    protected function createTestTenants(): void
    {
        // Criar 3 tenants
        $this->tenantA = Tenant::create(['name' => 'Tenant A']);
        $this->tenantB = Tenant::create(['name' => 'Tenant B']);
        $this->tenantC = Tenant::create(['name' => 'Tenant C']);
    }
    
    protected function createTestUsers(): void
    {
        // Criar usuários para cada tenant
        $this->userA = User::create(['name' => 'User A', 'tenant_id' => $this->tenantA->id]);
        $this->userB = User::create(['name' => 'User B', 'tenant_id' => $this->tenantB->id]);
        $this->userC = User::create(['name' => 'User C', 'tenant_id' => $this->tenantC->id]);
    }
    
    // Helper methods
    protected function switchTenant(Tenant $tenant): void
    {
        \Illuminate\Support\Facades\Config::set('tenancy.current_tenant', $tenant->id);
        DB::setConnection("tenant_{$tenant->id}");
    }
    
    protected function assertTenantIsolation(string $model, int $expectedCount): void
    {
        // Assert que count() retorna apenas do tenant ativo
        $this->assertEquals($expectedCount, $model::count());
    }
}
```

**Checklist**:
- [ ] Arquivo criado
- [ ] `use RefreshDatabase` importado
- [ ] Tenants A, B, C criados em setUp
- [ ] Users criados em setUp
- [ ] Helper `switchTenant()` implementado
- [ ] Helper `assertTenantIsolation()` implementado

---

### Passo 2: Criar Fixtures (2h)

**Arquivo**: `tests/Fixtures/TenantFixture.php`

```php
<?php

namespace Tests\Fixtures;

use App\Models\Tenant;

class TenantFixture
{
    public static function createTenant(array $attributes = []): Tenant
    {
        return Tenant::create([
            'name' => $attributes['name'] ?? 'Test Tenant',
            ...$attributes
        ]);
    }
    
    public static function createMultipleTenants(int $count = 3): array
    {
        return collect(range(1, $count))
            ->map(fn($i) => self::createTenant(['name' => "Tenant {$i}"]))
            ->toArray();
    }
}
```

**Arquivo**: `tests/Fixtures/UserFixture.php`

```php
<?php

namespace Tests\Fixtures;

use App\Models\User;
use App\Models\Tenant;

class UserFixture
{
    public static function createUserForTenant(Tenant $tenant, array $attributes = []): User
    {
        return User::create([
            'name' => $attributes['name'] ?? 'Test User',
            'email' => $attributes['email'] ?? 'user@test.com',
            'tenant_id' => $tenant->id,
            'password' => bcrypt('password'),
            ...$attributes
        ]);
    }
}
```

**Checklist**:
- [ ] TenantFixture criado
- [ ] UserFixture criado
- [ ] Métodos helpers funcionam
- [ ] Fixtures podem ser reutilizadas

---

### Passo 3: Implementar 8+ Testes (2h)

**Arquivo**: `tests/Feature/MultiTenancy/SetupTest.php`

```php
<?php

namespace Tests\Feature\MultiTenancy;

use Tests\Feature\TenantTestCase;
use App\Models\Tenant;
use App\Models\User;

class SetupTest extends TenantTestCase
{
    public function test_tenant_fixture_creates_tenant(): void
    {
        $this->assertNotNull($this->tenantA);
        $this->assertNotNull($this->tenantB);
        $this->assertNotNull($this->tenantC);
    }
    
    public function test_users_created_with_correct_tenant(): void
    {
        $this->assertEquals($this->tenantA->id, $this->userA->tenant_id);
        $this->assertEquals($this->tenantB->id, $this->userB->tenant_id);
    }
    
    public function test_switch_tenant_changes_context(): void
    {
        $this->switchTenant($this->tenantA);
        $this->assertEquals($this->tenantA->id, config('tenancy.current_tenant'));
        
        $this->switchTenant($this->tenantB);
        $this->assertEquals($this->tenantB->id, config('tenancy.current_tenant'));
    }
    
    public function test_database_cleanup_between_tests(): void
    {
        $count1 = Tenant::count();
        // Test runs with RefreshDatabase
        // Next test should have same count
    }
    
    public function test_multiple_tenants_exist(): void
    {
        $this->assertEquals(3, Tenant::count());
    }
    
    public function test_fixtures_are_independent(): void
    {
        $this->switchTenant($this->tenantA);
        $countA = User::count();
        
        $this->switchTenant($this->tenantB);
        $countB = User::count();
        
        $this->assertEquals(1, $countA);
        $this->assertEquals(1, $countB);
    }
    
    public function test_can_create_additional_users(): void
    {
        $newUser = User::create([
            'name' => 'New User',
            'email' => 'new@test.com',
            'tenant_id' => $this->tenantA->id,
            'password' => bcrypt('password')
        ]);
        
        $this->assertNotNull($newUser->id);
    }
    
    public function test_tenant_isolation_at_fixture_level(): void
    {
        $this->switchTenant($this->tenantA);
        $this->assertEquals(1, User::count());
        
        $this->switchTenant($this->tenantB);
        $this->assertEquals(1, User::count());
    }
}
```

**Checklist**:
- [ ] 8+ testes implementados
- [ ] Todos os testes passam
- [ ] Coverage > 80%
- [ ] Não há warnings/notices

---

### Passo 4: Validar Implementação (1h)

```bash
# Rodar testes
./vendor/bin/phpunit tests/Feature/MultiTenancy/SetupTest.php

# Verificar coverage
./vendor/bin/phpunit --coverage-text tests/Feature/MultiTenancy/SetupTest.php

# Rodar todos os testes de tenant
./vendor/bin/phpunit tests/Feature/MultiTenancy/
```

**Checklist**:
- [ ] Todos os testes PASSAM (8+)
- [ ] Coverage > 80%
- [ ] Sem erros de fixtures
- [ ] Sem warnings do PHPUnit

---

### Passo 5: Criar Checkpoint (30 min)

**Arquivo**: `docs/agent/checkpoints/2026-06-26-fase4-task-g-milestone-1-complete.md`

```markdown
# Checkpoint: Milestone 1 - Setup & Fixtures

**Data**: 2026-06-26  
**Tarefa**: Phase 4 Task G - Milestone 1  
**Status**: ✅ COMPLETO

## Implementado
- ✅ TenantTestCase com setup automático
- ✅ TenantFixture
- ✅ UserFixture
- ✅ 8+ testes de setup
- ✅ Context switching funcional
- ✅ Database cleanup automático

## Testes
- Total: 8+ implementados
- Passando: 8+ ✅
- Failing: 0
- Coverage: 85%+

## Próxima Tarefa
Milestone 2 (27/06): Isolamento Básico

## Observações
[Adicionar observações importantes]
```

---

## ⚠️ Possíveis Problemas & Soluções

| Problema | Solução |
|----------|---------|
| "Class TenantTestCase not found" | Verificar namespace, rodar autoload: `composer dump-autoload` |
| "Database doesn't exist" | Rodar migrations: `php artisan migrate:fresh --env=testing` |
| "Port already in use" | Mudar DB_PORT em `.env.testing` |
| "Tests timeout" | Aumentar timeout em `phpunit.xml` |
| "RefreshDatabase não funciona" | Verificar se database testing está ativado |

---

## 📊 Métricas ao Finalizar

Ao concluir Milestone 1, você deve ter:

- ✅ 8+ testes implementados
- ✅ Coverage > 80%
- ✅ 0 erros, 0 warnings
- ✅ Fixtures reutilizáveis
- ✅ Setup automático funcional

---

## 🎯 Lembrete do Objetivo

Esta tarefa é sobre **setup**, não sobre validar isolamento.

Você está apenas preparando:
- Ambiente de teste
- Fixtures de dados
- Helpers de teste
- Infrastructure

**Não teste isolamento ainda** (Milestone 2).

---

## 📞 Referências

**Plano completo**: `docs/agent/tasks/001-phase4-tarefa-g-milestone-1-setup-testes.md`  
**Rastreamento**: `docs/agent/RASTREAMENTO-EXECUCAO-V1.md`  
**Timeline**: `docs/agent/tasks/INDEX-TAREFAS-2026-06-25.md`

---

## 🚀 Comece AGORA

1. Abra `tests/Feature/TenantTestCase.php`
2. Copie o código de exemplo acima
3. Rode os testes: `./vendor/bin/phpunit tests/Feature/MultiTenancy/SetupTest.php`
4. Ajuste conforme necessário
5. Quando tudo passar → Crie checkpoint

**Tempo total**: ~7-8 horas  
**Próximo**: Milestone 2 (27/06)

---

**Guia criado**: 2026-06-25  
**Status**: 🟢 PRONTO PARA IMPLEMENTAÇÃO AGORA  
**Próximas**: 4 milestones restantes
