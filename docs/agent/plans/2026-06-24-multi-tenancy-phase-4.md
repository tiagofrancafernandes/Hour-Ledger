# Plano de Execução — Fase 4: Multi Tenancy

**Data**: 2026-06-24  
**Status**: 🟡 90% COMPLETO (Tasks A-F completas, Task G pendente)  
**Última Atualização**: 2026-06-25  
**Objetivo**: Implementar isolamento de dados por tenant com PostgreSQL schemas

**Progress**:
- ✅ Task A: Architecture Design
- ✅ Task B: Database Schema
- ✅ Task C: Eloquent TenantScope & BelongsToTenant
- ✅ Task D: Auth Integration with Tenant Context
- ✅ Task E: Frontend Tenant Context UI
- ✅ Task F: Integration Testing
- 📋 Task G: Comprehensive Tenant Isolation Tests (PENDING)

---

## Visão Geral

A Fase 4 implementa isolamento completo de dados entre tenants (clientes) usando:
- Identidade global única (usuários compartilhados)
- Dados tenantizados (schemas PostgreSQL separados)
- Tenant resolver (middleware para detectar tenant ativo)
- Isolamento validado em testes

---

## Arquitetura Proposta

```
┌─────────────────────────────────────────┐
│      Identidade Global (PostgreSQL)     │
│  - users                                │
│  - roles_and_permissions                │
│  - password_resets                      │
│  - personal_access_tokens               │
└─────────────────────────────────────────┘
                    ↓
          TenantMiddleware
     (Resolve tenant ativo)
                    ↓
┌─────────────────────────────────────────┐
│  Dados Tenantizados (Schemas)           │
│  Schema: tenant_1_prod                  │
│  - clients                              │
│  - wallets                              │
│  - ledger_entries                       │
│  - ... (outras tabelas)                 │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  Schema: tenant_2_prod                  │
│  - clients                              │
│  - wallets                              │
│  - ledger_entries                       │
└─────────────────────────────────────────┘
```

---

## Tarefas (Divisão para Paralelização)

### 🔴 Tarefa A — Arquitetura & Design (BLOQUEADOR)
**Dependências**: Nenhuma  
**Tempo**: 1-2 horas  
**Paralelo**: Não (deve ser feita primeiro)

#### Objetivos:
1. Desenhar esquema de multi-tenancy
2. Decidir convenção de naming para schemas
3. Definir estrutura de dados global vs tenantizado
4. Criar tipos/enums necessários
5. Documentar decisões arquiteturais

#### Entregas:
- [ ] `docs/architecture/multi-tenancy.md` (documentação técnica)
- [ ] `docs/architecture/tenant-schema-strategy.md` (estratégia de schemas)
- [ ] Definição de constantes/enums em código
- [ ] Aprovação arquitetural

---

### 🟡 Tarefa B — Database Schema & Migrations (Após A)
**Dependências**: Tarefa A  
**Tempo**: 1-2 horas  
**Paralelo**: Não (depende de decisões de A)

#### Objetivos:
1. Criar migrations para separar dados
2. Criar função de criação de tenant schema
3. Configurar PostgreSQL role/permissions por schema
4. Testar isolamento em nível de banco

#### Entregas:
- [ ] `database/migrations/2026_06_24_create_tenant_infrastructure.php`
- [ ] Script: `apps/hl-drive-api/scripts/create-tenant-schema.php`
- [ ] Testes de isolamento de banco

---

### 🟢 Tarefa C — Backend: TenantMiddleware & Resolver (Após A)
**Dependências**: Tarefa A  
**Tempo**: 1-2 horas  
**Paralelo**: Sim (com D, E)

#### Objetivos:
1. Criar `TenantMiddleware` que detecta tenant ativo
2. Criar `TenantResolver` (singleton)
3. Implementar `TenantContext` para armazenar estado
4. Integrar com Laravel di container

#### Entregas:
- [ ] `app/Http/Middleware/TenantMiddleware.php`
- [ ] `app/Services/TenantResolver.php`
- [ ] `app/Models/TenantContext.php`
- [ ] Registrar middleware em `app/Http/Kernel.php`

---

### 🟢 Tarefa D — Backend: Eloquent TenantScope (Após A)
**Dependências**: Tarefa A  
**Tempo**: 1-2 horas  
**Paralelo**: Sim (com C, E)

#### Objetivos:
1. Criar `TenantScope` (global scope em Eloquent)
2. Adicionar trait `BelongsToTenant` em models
3. Converter models existentes para usar trait
4. Testar que queries são automaticamente filtradas por tenant

#### Entregas:
- [ ] `app/Traits/BelongsToTenant.php`
- [ ] `app/Scopes/TenantScope.php`
- [ ] Atualizar todos os models: Client, Wallet, LedgerEntry, etc.

---

### 🟢 Tarefa E — Backend: Auth Integration (Após A)
**Dependências**: Tarefa A  
**Tempo**: 1-2 horas  
**Paralelo**: Sim (com C, D)

#### Objetivos:
1. Integrar TenantContext com autenticação
2. Adicionar tenant_id em PersonalAccessToken
3. Validar que user pode acessar tenant
4. Testar login por tenant

#### Entregas:
- [ ] Atualizar `PersonalAccessToken` model
- [ ] Atualizar `AuthController` para passar tenant_id
- [ ] Validações em policies

---

### 🟢 Tarefa F — Frontend: Tenant Context (Após A)
**Dependências**: Tarefa A  
**Tempo**: 1 hora  
**Paralelo**: Sim (com C, D, E)

#### Objetivos:
1. Criar store Pinia para tenant ativo
2. Adicionar tenant selector UI
3. Integrar com API calls (header X-Tenant-ID)

#### Entregas:
- [ ] `src/stores/tenant.ts`
- [ ] Componente: `TenantSelector.vue`

---

### 🔵 Tarefa G — Testes de Isolamento (Após C, D, E, B)
**Dependências**: Tarefas B, C, D, E  
**Tempo**: 2-3 horas  
**Paralelo**: Não (deve ser feito no final)

#### Objetivos:
1. Testes de isolamento de dados entre tenants
2. Testes de cross-tenant access prevention
3. Testes de perda de contexto
4. Load tests com múltiplos tenants

#### Entregas:
- [ ] `tests/Feature/TenancyTest.php`
- [ ] `tests/Feature/TenantIsolationTest.php`
- [ ] Documento de validação de isolamento

---

## Dependências (Diagrama)

```
A (Arquitetura)
├─> B (Database)
├─> C (Middleware)
├─> D (TenantScope)
├─> E (Auth)
└─> F (Frontend)
     └─> G (Testes)
```

**Paralelo possível**:
- C, D, E, F podem rodar em paralelo (após A)
- B deve rodar após A (mas pode começar enquanto C, D, E, F rodam)
- G deve rodar depois de todos

---

## Milestones

### Milestone 1: Planejamento Aprovado ✅
- [x] Arquitetura definida
- [x] Tarefas identificadas
- [x] Dependências mapeadas
- [x] Plano aprovado

### Milestone 2: Infraestrutura (Tarefas A + B)
- [ ] Arquitetura documentada
- [ ] Migrations criadas
- [ ] Tenant resolver testado

### Milestone 3: Backend Core (Tarefas C + D + E)
- [ ] Middleware implementado
- [ ] Tenant scope funcionando
- [ ] Auth integrada

### Milestone 4: Frontend (Tarefa F)
- [ ] Tenant selector criado
- [ ] Headers X-Tenant-ID funcionando

### Milestone 5: Validação (Tarefa G)
- [ ] Testes de isolamento passando
- [ ] Zero cross-tenant leaks
- [ ] Documentação finalizada

---

## Riscos Identificados

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---|---|---|
| Complexidade SQL/Eloquent | Alta | Alto | Começar com schema simples, iterar |
| Performance com múltiplos schemas | Média | Médio | Bench tests de query performance |
| Migração de dados existentes | Alta | Médio | Scripts de migração com rollback |
| Quebra de compatibilidade API | Média | Alto | Testes abrangentes, versionamento |

---

## Critérios de Aceite

- ✅ User pode logar em múltiplos tenants (com tokens diferentes)
- ✅ Dados de tenant A não aparecem em tenant B (validado em testes)
- ✅ Queries sem X-Tenant-ID retornam erro 401/403
- ✅ Migrations rodam sem erros
- ✅ Frontend selector funciona
- ✅ Testes de isolamento com 100% pass rate
- ✅ Zero SQL errors em logs
- ✅ Documentação atualizada

---

## Timeline Estimada

| Fase | Duração | Quando |
|------|---------|--------|
| A: Arquitetura | 2h | Imediatamente |
| B: Database | 2h | Após A (paralelo com C-F) |
| C-F: Backend/Frontend | 5h | Paralelo (após A) |
| G: Testes | 3h | Após C-F-B |
| **Total** | **12h** | **1-2 dias** |

---

## Próximos Passos

1. ✅ **AGORA**: Executar Tarefa A (Arquitetura) — 2h
2. ✅ **DEPOIS**: Executar Tarefas B, C, D, E, F em paralelo — 5h
3. ✅ **FINAL**: Executar Tarefa G (Testes) — 3h
4. ✅ **VALIDAR**: Testar em staging com dados reais

---

## Notas Importantes

- **Não mudar schema public** — Identidade global continua em public
- **Nomeação de schemas**: `tenant_{id}_{environment}` (ex: `tenant_1_prod`)
- **Rollback**: Preparar scripts de rollback para cada migration
- **Documentação**: Manter documentação de tenant setup atualizada
- **NEVER**: Fazer queries sem respeitar tenant context (risco de data leak)

---

**Status**: 📋 Pronto para execução  
**Aprovação**: ⏳ Aguardando aprovação (ou prossiga com `/prossiga`)
