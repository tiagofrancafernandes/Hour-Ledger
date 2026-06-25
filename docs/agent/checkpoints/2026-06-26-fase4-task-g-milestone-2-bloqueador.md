# Checkpoint: Tarefa 002 - Bloqueador Técnico Identificado

**Data**: 2026-06-26  
**Tarefa**: Phase 4 Task G - Milestone 2 (Isolamento Básico)  
**Status**: ⏳ **BLOQUEADO POR PROBLEMA TÉCNICO**

---

## Resumo

Tarefa 002 implementou **18 testes de isolamento** estruturalmente corretos. O bloqueador técnico identificado é que o `TenantObserver` não está auto-setando `tenant_id` quando um contexto de tenant ativo existe.

---

## O Que Foi Feito

✅ **3 arquivos de teste criados** com 18 testes implementados:
- IsolationTest.php (6 testes)
- ScopeTest.php (6 testes)  
- ContextTest.php (6 testes)

✅ **TenantTestCase refatorado**:
- Simplificado `switchTenant()` para usar método público `setTenantId()`
- Criado helper `createModelInTenant()` para criação context-aware
- Removidas operações com reflection que poderiam ser instáveis

✅ **Refactoring de padrões de testes**:
- Substituído `saveQuietly() + manual tenant_id` por `save()`
- Aplicado padrão `switchTenant() -> create()` em todos os testes

---

## Bloqueador Identificado

### Sintoma
Quando executando:
```php
$resolver = app(TenantResolver::class);
$resolver->setTenantId($tenant->id);

$client = Client::create(['name' => 'Test']);
echo $client->tenant_id;  // Outputs: NULL (expected: $tenant->id)
```

### Raiz
O `TenantObserver::creating()` não está setando `tenant_id` automaticamente apesar de `getTenantId()` retornar um valor válido.

Linha 54-58 do TenantObserver:
```php
if ($activeTenantId !== null) {
    $model->setAttribute('tenant_id', $activeTenantId);
    return;
}
```

Deveria funcionar, mas não está.

### Status do Code

| Component | Status | Notas |
|-----------|--------|-------|
| TenantTestCase | ✅ Simplificado | Usando setTenantId() público agora |
| IsolationTest | ⚠️ Estrutura OK | 0/6 testes passando (tenant_id = null) |
| ScopeTest | ⚠️ Estrutura OK | 0/6 testes passando |
| ContextTest | ⚠️ Estrutura OK | 0/6 testes passando |
| SetupTest | ✅ 20/20 passando | (Não usa BelongsToTenant) |

---

## Aprendizados de Debug

1. **Contexto está sendo setado**: `setTenantId()` não lança exceção
2. **Observer está sendo acionado**: Se não fosse, veria erro de NOT NULL constraint
3. **Mas tenant_id continua NULL**: Observer não está setando via `setAttribute()`

### Hipóteses

1. **A - Singleton cache**: App() pode estar retornando uma instância diferente no Observer
2. **B - Observer registration**: Observer não está sendo registrado corretamente em testes
3. **C - Timing issue**: setAttribute() pode estar sendo chamado mas depois sobrescrito
4. **D - TenantResolver em teste**: getTenantId() pode estar retornando null de forma inesperada

---

## Opções de Resolução

### Opção 1: Investigar TenantResolver singleton (Recomendado)
Adicionar debug ao Observer para verificar se `getTenantId()` retorna valor correto

### Opção 2: Use DB::insert() direto
Contornar o Observer completamente e usar queries diretas:
```php
DB::table('clients')->insert([
    'tenant_id' => $tenant->id,
    'name' => 'Test Client'
]);
```

### Opção 3: Refactor para usar fixtures com factories
Criar factories específicas que já lidam com o contexto de tenant

### Opção 4: Adicionar método `createWithTenant()` ao Client model
```php
public static function createWithTenant(Tenant $tenant, array $attrs) {
    app(TenantResolver::class)->setTenantId($tenant->id);
    return static::create($attrs);
}
```

---

## Próximos Passos

1. **Imediato**: Adicionar logs/debug ao TenantObserver::creating() para diagnosticar exatamente por que `getTenantId()` retorna null durante `create()`
2. **Se hipótese A**: Investigar singleton pattern em container Laravel
3. **Se resolver**: Aplicar solução a todos os 18 testes
4. **Validação**: Rodar suite completa e validar todos os 15+ testes passando

---

## Impacto na Timeline

- **Se resolvido hoje**: Tarefa 002 completa, pode prosseguir para 003, 004, 005
- **Se não**: Bloqueador até 01/07, impacta entrada para staging

---

**Commit**: a9a1851  
**Status**: ⏳ AGUARDANDO RESOLUÇÃO DO BLOQUEADOR  
**Próxima Ação**: Debug do TenantObserver + TenantResolver interaction
