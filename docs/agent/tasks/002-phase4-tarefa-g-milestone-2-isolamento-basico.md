# Tarefa 002: PHASE-4 Task G - Milestone 2 (Isolamento Básico)

**Status**: 📋 PLANEJADO  
**Prioridade**: 🔴 CRÍTICA  
**Data de início**: 2026-06-27  
**Deadline**: 2026-06-27  
**Dependência**: Tarefa 001 (Milestone 1) concluída

---

## Objetivo

Implementar e validar testes de isolamento básico: garantir que queries em um tenant não retornam dados de outros tenants.

---

## Contexto

**Por que é crítico**: Violação de isolamento básico = data leak crítica entre instrutores.

**Escopo**: Validar que os mecanismos de isolamento (schema + TenantScope) funcionam corretamente.

**Milestones seguintes**: Milestone 3 (prevention), Milestone 4 (bypass attempts)

---

## Escopo

### Implementar Testes (15+)

**Isolamento de Models (6 testes)**:
- [ ] Client::all() retorna apenas clientes do tenant
- [ ] Wallet::all() retorna apenas carteiras do tenant
- [ ] LedgerEntry::all() retorna apenas movimentações do tenant
- [ ] User::all() retorna apenas usuários do tenant
- [ ] Link::all() retorna apenas links do tenant
- [ ] Preference::all() retorna apenas preferências do tenant

**Isolamento de Queries (5 testes)**:
- [ ] where() respeitam TenantScope
- [ ] join() não vazam dados entre tenants
- [ ] relations() não retornam dados de outro tenant
- [ ] count() reflete apenas dados do tenant
- [ ] exists() verifica apenas no tenant

**Isolamento de Contexto (4 testes)**:
- [ ] Mudar tenant muda resultados de queries
- [ ] Context nulo não retorna dados
- [ ] Context inválido não retorna dados
- [ ] Múltiplas queries em sequência mantêm isolamento

### Validações

- [ ] Todas as migrations rodam em schema de tenant
- [ ] TenantScope aplicado automaticamente
- [ ] Observer valida tenant_id em creates
- [ ] Queries mostram schema correto

---

## Fora do Escopo

- ❌ Testes de data leakage específica (próx milestone)
- ❌ Testes de bypass (próx milestone)
- ❌ Testes de performance
- ❌ Testes de autorização

---

## Arquivos Prováveis

### Novos
- `tests/Feature/MultiTenancy/IsolationTest.php`
- `tests/Feature/MultiTenancy/ScopeTest.php`
- `tests/Feature/MultiTenancy/ContextTest.php`

### Modificados
- Nenhum (apenas testes)

---

## Regras Arquiteturais

- **TenantScope**: Deve ser aplicado automaticamente em todos os models
- **Schema**: Queries devem executar no schema do tenant, não em público
- **Context**: Sempre deve estar presente (nunca null em queries)
- **Observer**: Deve validar tenant_id em create/update

---

## Testes a Implementar

```php
// IsolationTest.php (6 testes)
public function test_client_all_returns_only_tenant_clients()
public function test_wallet_all_returns_only_tenant_wallets()
public function test_ledger_entry_all_returns_only_tenant_entries()
public function test_user_all_returns_only_tenant_users()
public function test_link_all_returns_only_tenant_links()
public function test_preference_all_returns_only_tenant_preferences()

// ScopeTest.php (5 testes)
public function test_where_clause_respects_tenant_scope()
public function test_join_does_not_leak_data()
public function test_relations_do_not_leak_data()
public function test_count_reflects_only_tenant_data()
public function test_exists_checks_only_in_tenant()

// ContextTest.php (4 testes)
public function test_changing_tenant_changes_query_results()
public function test_null_context_returns_no_data()
public function test_invalid_context_returns_no_data()
public function test_sequential_queries_maintain_isolation()
```

---

## Critérios de Aceite

- ✅ 15+ testes de isolamento básico implementados
- ✅ Todos os testes passam
- ✅ Testes cobrem todos os models principais
- ✅ Testes cobrem tipos de queries comuns
- ✅ Context switching não causa leaks
- ✅ Coverage > 80%
- ✅ Checkpoint gerado

---

## Checklist de Implementação

### Testes de Isolation (3h)
- [ ] Criar IsolationTest.php
- [ ] Implementar 6 testes de models
- [ ] Validar passam

### Testes de Scope (3h)
- [ ] Criar ScopeTest.php
- [ ] Implementar 5 testes de queries
- [ ] Validar passam

### Testes de Context (2h)
- [ ] Criar ContextTest.php
- [ ] Implementar 4 testes de context
- [ ] Validar passam

### Análise & Checkpoint (1h)
- [ ] Revisar cobertura
- [ ] Gerar checkpoint
- [ ] Documentar achados

---

## Dependências

**Pré-requisitos**:
- ✅ Tarefa 001 (Milestone 1) concluída
- ✅ Fixtures funcionando
- ✅ TenantScope implementado

**Bloqueia**: Tarefa 003 (Milestone 3 - Data Leakage Prevention)

---

## Referências

- `docs/agent/plans/2026-06-24-comprehensive-tenant-isolation-security-tests.md` (Milestone 2 section)
- `docs/agent/plans/2026-06-24-tenant-tests-technical-spec.md` (Padrões de teste)

---

**Tarefa criada**: 2026-06-25  
**Predecessor**: 001-phase4-tarefa-g-milestone-1-setup-testes  
**Sucessor**: 003-phase4-tarefa-g-milestone-3-data-leakage
