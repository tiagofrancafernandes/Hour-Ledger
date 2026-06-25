# Tarefa 001: PHASE-4 Task G - Milestone 1 (Setup & Fixtures)

**Status**: 📋 PLANEJADO  
**Prioridade**: 🔴 CRÍTICA  
**Data de início**: 2026-06-26  
**Deadline**: 2026-06-26  
**Bloqueador para**: Deploy em staging

---

## Objetivo

Configurar ambiente de testes para validação de isolamento multi-tenant com PostgreSQL schemas, criando fixtures de teste realistas e preparando a suite de testes.

---

## Contexto

**Por que é crítico**: Sem testes de isolamento, não podemos garantir que dados de um tenant não vazem para outro. Isso é bloqueador para produção.

**Dependências completadas**:
- ✅ PHASE-4 Tasks A-F (arquitetura + implementação)
- ✅ Auth integration com tenant context
- ✅ Eloquent TenantScope e BelongsToTenant

**Planos relacionados**:
- `docs/agent/plans/2026-06-24-comprehensive-tenant-isolation-security-tests.md`
- `docs/agent/plans/2026-06-24-tarefa-g-quick-reference.md`
- `docs/agent/plans/2026-06-24-tenant-tests-technical-spec.md`

---

## Escopo

### Implementar

- [ ] Classe base `TenantTestCase` com helpers
- [ ] Fixtures de tenants de teste (3+ tenants)
- [ ] Fixtures de usuários (instructors + students)
- [ ] Fixtures de data de teste (clients, wallets, ledger entries)
- [ ] TestCase setup com tenant context switching
- [ ] Helper methods para validação de isolamento
- [ ] Seeds realistas para dados de teste

### Configurar

- [ ] PHPUnit configurado para testes de tenant
- [ ] Database refresh entre testes
- [ ] Ambiente de teste com múltiplos tenants
- [ ] Logging/debugging de queries por tenant

### Documentar

- [ ] README com instruções de execução
- [ ] Padrão de escrita de testes de tenant
- [ ] Fixtures e como usá-las
- [ ] Checkpoint de Milestone 1

---

## Fora do Escopo

- ❌ Implementar testes de lógica de negócio
- ❌ Testar fluxos de aplicação
- ❌ Validação de isolamento (próximo milestone)
- ❌ Testes de performance
- ❌ Documentação de deploy

---

## Arquivos Prováveis

### Novos
- `tests/Feature/TenantTestCase.php`
- `tests/Fixtures/TenantFixture.php`
- `tests/Fixtures/UserFixture.php`
- `tests/Fixtures/DataFixture.php`
- `tests/Seeds/TenantSeeder.php`
- `tests/README.md`

### Modificados
- `phpunit.xml` (configuração)
- `tests/TestCase.php` (herança de TenantTestCase)

---

## Regras Arquiteturais

- **Multi-tenancy**: Cada tenant tem schema separado no PostgreSQL
- **Isolamento**: Queries sempre filtradas por tenant_id ou schema
- **Context**: Tenant context sempre ativo durante testes
- **Migrations**: Executar em schema de teste, não em público
- **Logs**: Auditar queries executadas

---

## Testes Esperados

### Testes de Setup (8+ testes)
- [ ] Tenants criados com schema separado
- [ ] Usuários criados no tenant correto
- [ ] Context switching entre tenants funciona
- [ ] Dados isolados por schema
- [ ] Fixtures podem ser reutilizadas
- [ ] Reset de dados entre testes
- [ ] Observer valida tenant_id em creates
- [ ] Seed realista popula dados

---

## Critérios de Aceite

- ✅ Classe TenantTestCase criada e funcional
- ✅ Fixtures de tenants, usuários e dados funcionam
- ✅ Testes podem rodar em paralelo (isolamento total)
- ✅ Database refresh entre testes funciona
- ✅ Helper methods permitem escrita fácil de testes de isolamento
- ✅ Logging mostra queries executadas e tenant context
- ✅ Documentação clara de como escrever testes
- ✅ Checkpoint gerado com progresso

---

## Checklist de Implementação

### Setup (2h)
- [ ] Criar classe TenantTestCase
- [ ] Configurar PHPUnit
- [ ] Helpers básicos (switchTenant, assertTenantIsolated)

### Fixtures (3h)
- [ ] TenantFixture (create 3 tenants)
- [ ] UserFixture (instructors + students)
- [ ] DataFixture (clients, wallets, ledger)

### Testes (2h)
- [ ] 8+ testes de setup
- [ ] Testes passam
- [ ] Coverage adequada

### Documentação (1h)
- [ ] README.md em tests/
- [ ] Padrão de testes documentado
- [ ] Checkpoint criado

---

## Dependências

**Pré-requisitos**:
- ✅ PHASE-4 Tasks A-F concluídas
- ✅ Auth integration funcional
- ✅ TenantScope implementado

**Bloqueado por**: Nada (pode começar imediatamente)

**Bloqueia**: Milestone 2 (testes de isolamento básico)

---

## Referências

- `docs/agent/plans/2026-06-24-comprehensive-tenant-isolation-security-tests.md` (plano principal)
- `docs/agent/plans/2026-06-24-tarefa-g-quick-reference.md` (quick reference)
- `docs/agent/plans/2026-06-24-tenant-tests-technical-spec.md` (especificação técnica)
- `docs/execution-history/PHASE-4/checkpoints/` (checkpoints anteriores)

---

## Próximos Passos

Após conclusão:
1. Gerar checkpoint de Milestone 1
2. Iniciar Milestone 2 (testes de isolamento básico)
3. Atualizar EXECUTION.md com progresso

---

**Tarefa criada**: 2026-06-25  
**Relacionada ao plano**: Task G (Isolamento Multi-Tenant)  
**Fase**: PHASE-4  
**Próxima tarefa**: 002-phase4-tarefa-g-milestone-2-isolamento-basico
