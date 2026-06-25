# Testing Guide - Multi-Tenancy

Este guia descreve como escrever e executar testes para validação de isolamento multi-tenant.

---

## Quick Start

### Rodar todos os testes de setup
```bash
./vendor/bin/phpunit tests/Feature/MultiTenancy/SetupTest.php
```

### Rodar um teste específico
```bash
./vendor/bin/phpunit tests/Feature/MultiTenancy/SetupTest.php --filter "test_name"
```

### Rodar todos os testes de feature
```bash
./vendor/bin/phpunit tests/Feature/
```

---

## Estrutura de Testes

```
tests/
├── Feature/
│   ├── TenantTestCase.php          # Base class para testes multi-tenant
│   ├── MultiTenancy/
│   │   └── SetupTest.php           # Testes de infraestrutura
│   └── ...
├── Fixtures/
│   ├── TenantFixture.php           # Factory para tenants
│   └── UserFixture.php             # Factory para users
└── Unit/
    └── ...
```

---

## Como Escrever Testes de Multi-Tenancy

### Passo 1: Estender TenantTestCase

```php
<?php

namespace Tests\Feature\MyFeature;

use Tests\Feature\TenantTestCase;

class MyFeatureTest extends TenantTestCase
{
    public function test_something(): void
    {
        // Use $this->tenantA, $this->tenantB, $this->tenantC
        // Use $this->userA, $this->userB, $this->userC
    }
}
```

### Passo 2: Usar os dados de teste automáticos

```php
public function test_access_control(): void
{
    // Tenants já estão criados
    $this->assertNotNull($this->tenantA);
    $this->assertNotNull($this->tenantB);
    
    // Users já estão criados e assignados
    $this->assertTrue($this->userA->hasAccessToTenant($this->tenantA->id));
    $this->assertFalse($this->userA->hasAccessToTenant($this->tenantB->id));
}
```

### Passo 3: Trocar contexto de tenant

```php
public function test_tenant_isolation(): void
{
    // Mude para tenant A
    $this->switchTenant($this->tenantA);
    
    // Test code for tenant A
    
    // Mude para tenant B
    $this->switchTenant($this->tenantB);
    
    // Test code for tenant B
}
```

### Passo 4: Validar isolamento

```php
public function test_data_isolation(): void
{
    $this->assertTenantIsolation(\App\Models\Client::class, 1);
    // Verifica que cada tenant tem apenas 1 client
}
```

---

## Helpers Disponíveis

### TenantTestCase Helpers

#### `switchTenant(Tenant $tenant): void`
Muda o contexto ativo para um tenant específico.

```php
$this->switchTenant($this->tenantA);
```

#### `assertTenantIsolation(string $modelClass, int $expectedCount = 1): void`
Valida que cada tenant tem o número esperado de registros.

```php
$this->assertTenantIsolation(\App\Models\Client::class, 1);
```

#### `assertActiveTenant(Tenant $tenant): void`
Verifica que um tenant específico está ativo.

```php
$this->assertActiveTenant($this->tenantA);
```

#### `assertNoActiveTenant(): void`
Verifica que nenhum tenant está ativo.

```php
$this->assertNoActiveTenant();
```

#### `getUserForTenant(Tenant $tenant): User`
Recupera o usuário de teste para um tenant.

```php
$user = $this->getUserForTenant($this->tenantA);
```

---

## Fixtures

### TenantFixture

Cria tenants de teste com várias configurações.

#### `createTenant(array $attributes = []): Tenant`
```php
$tenant = \Tests\Fixtures\TenantFixture::createTenant([
    'name' => 'My Tenant',
    'slug' => 'my-tenant',
]);
```

#### `createMultipleTenants(int $count = 3): array`
```php
$tenants = \Tests\Fixtures\TenantFixture::createMultipleTenants(5);
```

#### `createActiveTenant(array $attributes = []): Tenant`
```php
$tenant = \Tests\Fixtures\TenantFixture::createActiveTenant();
```

#### `createSuspendedTenant(array $attributes = []): Tenant`
```php
$tenant = \Tests\Fixtures\TenantFixture::createSuspendedTenant();
```

#### `createDeletedTenant(array $attributes = []): Tenant`
```php
$tenant = \Tests\Fixtures\TenantFixture::createDeletedTenant();
```

#### `createTenantWithSlug(string $slug, array $attributes = []): Tenant`
```php
$tenant = \Tests\Fixtures\TenantFixture::createTenantWithSlug('custom-slug');
```

---

### UserFixture

Cria usuários e os atribui a tenants.

#### `createUserForTenant(Tenant $tenant, array $attributes = [], string $role = 'member', string $status = 'active'): User`
```php
$user = \Tests\Fixtures\UserFixture::createUserForTenant(
    $tenant,
    ['name' => 'John Doe'],
    'admin',
    'active'
);
```

#### `createMultipleUsersForTenant(Tenant $tenant, int $count = 3): array`
```php
$users = \Tests\Fixtures\UserFixture::createMultipleUsersForTenant($tenant, 5);
```

#### `createAdminForTenant(Tenant $tenant, array $attributes = []): User`
```php
$admin = \Tests\Fixtures\UserFixture::createAdminForTenant($tenant);
```

#### `createMemberForTenant(Tenant $tenant, array $attributes = []): User`
```php
$member = \Tests\Fixtures\UserFixture::createMemberForTenant($tenant);
```

#### `createViewerForTenant(Tenant $tenant, array $attributes = []): User`
```php
$viewer = \Tests\Fixtures\UserFixture::createViewerForTenant($tenant);
```

#### `createSuspendedUserForTenant(Tenant $tenant, array $attributes = []): User`
```php
$suspended = \Tests\Fixtures\UserFixture::createSuspendedUserForTenant($tenant);
```

#### `createRevokedUserForTenant(Tenant $tenant, array $attributes = []): User`
```php
$revoked = \Tests\Fixtures\UserFixture::createRevokedUserForTenant($tenant);
```

#### `createUserForMultipleTenants(array $tenants, array $attributes = []): User`
```php
$user = \Tests\Fixtures\UserFixture::createUserForMultipleTenants([
    $tenantA,
    $tenantB,
    $tenantC,
]);
```

---

## Exemplo Completo

```php
<?php

namespace Tests\Feature\MultiTenancy;

use Tests\Feature\TenantTestCase;
use Tests\Fixtures\TenantFixture;
use Tests\Fixtures\UserFixture;

class DataIsolationTest extends TenantTestCase
{
    public function test_clients_isolated_by_tenant(): void
    {
        // Setup: Create clients in each tenant
        $this->switchTenant($this->tenantA);
        \App\Models\Client::create([
            'name' => 'Client A1',
            'tenant_id' => $this->tenantA->id,
        ]);

        $this->switchTenant($this->tenantB);
        \App\Models\Client::create([
            'name' => 'Client B1',
            'tenant_id' => $this->tenantB->id,
        ]);

        // Test: Verify isolation
        $this->assertTenantIsolation(\App\Models\Client::class, 1);
    }

    public function test_user_roles(): void
    {
        // Setup: Create users with different roles
        $admin = UserFixture::createAdminForTenant($this->tenantA);
        $member = UserFixture::createMemberForTenant($this->tenantA);
        $viewer = UserFixture::createViewerForTenant($this->tenantA);

        // Test: Verify roles
        $this->assertEquals('admin', $admin->tenants()->first()->pivot->role);
        $this->assertEquals('member', $member->tenants()->first()->pivot->role);
        $this->assertEquals('viewer', $viewer->tenants()->first()->pivot->role);
    }

    public function test_multi_tenant_user(): void
    {
        // Setup: Create user with access to multiple tenants
        $user = UserFixture::createUserForMultipleTenants([
            $this->tenantA,
            $this->tenantB,
            $this->tenantC,
        ]);

        // Test: Verify access to all tenants
        $this->assertTrue($user->hasAccessToTenant($this->tenantA->id));
        $this->assertTrue($user->hasAccessToTenant($this->tenantB->id));
        $this->assertTrue($user->hasAccessToTenant($this->tenantC->id));
    }
}
```

---

## Boas Práticas

### 1. Use os dados automáticos do TenantTestCase

✅ **Bom**:
```php
public function test_something(): void {
    // Use os dados já criados
    $this->switchTenant($this->tenantA);
}
```

❌ **Ruim**:
```php
public function test_something(): void {
    // Evite criar novos tenants se não for necessário
    $tenant = Tenant::factory()->create();
}
```

### 2. Teste uma coisa por vez

✅ **Bom**:
```php
public function test_client_creation(): void {
    // Apenas testa criação
}

public function test_client_isolation(): void {
    // Apenas testa isolamento
}
```

❌ **Ruim**:
```php
public function test_clients(): void {
    // Testa criação E isolamento no mesmo teste
}
```

### 3. Use nomes descritivos

✅ **Bom**:
```php
public function test_user_cannot_access_other_tenant_clients(): void { }
```

❌ **Ruim**:
```php
public function test_access(): void { }
```

### 4. Isole o comportamento esperado

✅ **Bom**:
```php
$this->switchTenant($this->tenantA);
$this->assertTenantIsolation(\App\Models\Client::class, 1);
```

❌ **Ruim**:
```php
$this->switchTenant($this->tenantA);
// Sem assertions claras
```

---

## Troubleshooting

### Erro: "Database doesn't exist"
Solução: Rode migrations para testes
```bash
php artisan migrate:fresh --env=testing
```

### Erro: "Class TenantTestCase not found"
Solução: Verifi que o namespace está correto
```bash
composer dump-autoload
```

### Erro: "Undefined method setTenant"
Solução: Use `switchTenant()` em vez de `setTenant()`
```php
// ✅ Correto
$this->switchTenant($tenant);

// ❌ Errado
$this->setTenant($tenant);
```

### Teste lento
Solução: Evite múltiplas queries desnecessárias
```php
// Use assertions assertTenantIsolation em vez de queries manuais
$this->assertTenantIsolation(Client::class, 1);
```

---

## Recursos Adicionais

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Multi-Tenancy Architecture](../../architecture/)
- [Tenant Isolation Tests Plan](../plans/2026-06-24-comprehensive-tenant-isolation-security-tests.md)

---

**Last Updated**: 2026-06-25  
**Milestone**: Phase 4 Task G - Milestone 1
