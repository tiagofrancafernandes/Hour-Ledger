# Plano: Eloquent TenantScope & BelongsToTenant Trait

**Status**: Em Execução
**Criado**: 2026-06-24

## Objetivo

Implementar trait e global scope para isolamento automático de dados por tenant em Eloquent.

## Contexto

- TenantResolver criado em tarefa anterior (Tarefa C)
- Models existentes: Client, Wallet, LedgerEntry
- Arquitetura multi-tenancy com tenant_id em tabelas
- Precisa garantir que queries sejam automaticamente filtradas por tenant

## Milestones

### 1. Criar TenantScope (Global Scope)
- [ ] Arquivo: `app/Scopes/TenantScope.php`
- [ ] Implementar `apply()` method com filtro automático
- [ ] Validar tenant_id do TenantResolver
- [ ] Comportamento: Retornar query vazia se sem tenant context

### 2. Criar BelongsToTenant Trait
- [ ] Arquivo: `app/Traits/BelongsToTenant.php`
- [ ] Boot method: Registra TenantScope
- [ ] Boot method: Registra observer TenantObserver
- [ ] Método: `getTenantId()` — Retorna tenant_id da instância
- [ ] Método: `isInTenant($tenantId)` — Verifica pertencimento ao tenant
- [ ] Validação de tenant_id obrigatório

### 3. Criar TenantObserver (Opcional mas recomendado)
- [ ] Arquivo: `app/Observers/TenantObserver.php`
- [ ] Método: `creating()` — Valida e seta tenant_id automaticamente
- [ ] Excepção: `UnauthorizedTenantOperation` se tenant mismatch

### 4. Atualizar Models com BelongsToTenant
- [ ] Client.php
- [ ] Wallet.php
- [ ] LedgerEntry.php
- [ ] Adicionar `protected $fillable` com `tenant_id`
- [ ] Adicionar cast `tenant_id` → `int`

### 5. Criar Testes
- [ ] Arquivo: `tests/Feature/TenantScopeTest.php`
- [ ] Test: Queries sem tenant_id context retornam vazio
- [ ] Test: Queries com tenant_id context retornam dados do tenant
- [ ] Test: Cross-tenant queries falham
- [ ] Test: Criação sem tenant_id falha
- [ ] Test: Update/delete respeitam tenant scope
- [ ] Test: Relationships funcionam dentro de tenant context

### 6. Commit e Finalização
- [ ] Fazer commit com mensagem descritiva
- [ ] Validar que testes passam

## Decisões Arquiteturais

1. **TenantScope como Global Scope**: Garante que TODA query é automaticamente filtrada
2. **Observer para auto-set**: Evita erro humano na criação de records
3. **Trait para reusabilidade**: Permite adicionar tenancy a qualquer model
4. **Exception para segurança**: Falha loudly se tenant mismatch

## Riscos e Mitigação

| Risco | Mitigação |
|-------|-----------|
| N+1 queries em relationships | Testes com profiling |
| Queries vazias sem tenant context | Comportamento esperado, testes validam |
| Models sem tenant_id | Observer previne, validação no boot |

## Critérios de Aceitação

- ✅ Global scope funciona automaticamente
- ✅ Trait integra bem com models existentes
- ✅ Observer valida tenant_id
- ✅ Testes cobrem casos críticos
- ✅ Sem breaking changes em models existentes
- ✅ Testes passam 100%
