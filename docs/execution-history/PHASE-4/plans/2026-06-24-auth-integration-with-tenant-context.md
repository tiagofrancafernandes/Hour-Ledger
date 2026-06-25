# Plano: Tarefa E - Auth Integration with Tenant Context

**Data**: 2026-06-24  
**Objetivo**: Integrar autenticação com tenant context para validar acesso cross-tenant  
**Status**: Planejamento

## Resumo Executivo

Integrar autenticação Laravel Sanctum com multi-tenancy para:
- Tokens JWT limitados a tenant específico
- Validação de acesso cross-tenant
- Policies com verificação de tenant
- Testes de isolamento

## Arquitetura de Tokens

### Fluxo Atual (sem Tenant)
```
POST /api/login
  email + password
    ↓
  create_token() → Token global
    ↓
  Return: { token, user, permissions }
```

### Fluxo Novo (com Tenant)
```
POST /api/login
  email + password + tenant_id (opcional)
    ↓
  validate user
    ↓
  IF tenant_id provided:
    ├─ verify user access to tenant
    ├─ IF NOT: return 403
    └─ create_token(tenant_id)
  ELSE:
    └─ create_token(null) — global token
    ↓
  Return: { 
    token, 
    user, 
    permissions, 
    accessible_tenants: [...]
  }
```

## Milestones

### Milestone 1: Estrutura de Dados
**Objetivo**: Criar tabelas e modelos para suportar tenant tokens

**Tarefas**:
1. Migração: Adicionar `tenant_id` a `personal_access_tokens`
2. Criar pivot table `user_tenants` (many-to-many)
3. Atualizar Model `PersonalAccessToken`
4. Atualizar Model `User` com relação `tenants()`

**Critério de Aceite**:
- Migrations executadas sem erros
- Models compilam corretamente
- Relações funcionam no Tinker

---

### Milestone 2: Autenticação com Tenant
**Objetivo**: Atualizar método `login()` do AuthController

**Tarefas**:
1. Validar `tenant_id` opcional no LoginRequest
2. Atualizar `login()` do AuthController
   - Validar acesso do user ao tenant
   - Incluir tenant_id no token se fornecido
   - Retornar lista de tenants acessíveis
3. Criar Service: `TenantValidationService`

**Critério de Aceite**:
- Login sem tenant_id funciona
- Login com tenant_id válido inclui tensor_id no token
- Login com tenant_id inválido retorna 403
- Response inclui lista de tenants

---

### Milestone 3: Validação de Tokens
**Objetivo**: Validar tokens limitados a tenant

**Tarefas**:
1. Atualizar Model `PersonalAccessToken`
   - Método: `isLimitedToTenant(): bool`
   - Método: `getTenantId(): ?int`
2. Criar Middleware: `ValidateTenantToken`
   - Validar se token é limitado
   - Validar se tenant_id do request == token.tenant_id
3. Adicionar middleware a rotas tenantizadas

**Critério de Aceite**:
- Token com tenant_id valida corretamente
- Token global permite acesso a qualquer tenant
- Cross-tenant access bloqueado

---

### Milestone 4: Policies com Tenant
**Objetivo**: Adicionar validação de tenant em policies

**Tarefas**:
1. Criar Trait: `ValidatesTenantAccess` para policies
2. Atualizar policies:
   - `WalletPolicy`
   - `ClientPolicy`
   - `LedgerEntryPolicy`
3. Implementar padrão: Validar tenant antes de permissão

**Critério de Aceite**:
- Policy rejeita cross-tenant access
- Mensagem de erro apropriada (403)
- Padrão documentado

---

### Milestone 5: Testes
**Objetivo**: Validar isolamento tenant

**Tarefas**:
1. Criar `tests/Feature/TenantAuthTest.php`
2. Testes:
   - ✅ Login sem tenant_id
   - ✅ Login com tenant_id válido
   - ✅ Login com tenant_id inválido (403)
   - ✅ Token limitado a tenant
   - ✅ Cross-tenant policy rejection
   - ✅ Trocar tenant via novo login
   - ✅ Endpoints retornam 403 para cross-tenant

**Critério de Aceite**:
- Todos os 7 testes passam
- Coverage > 80% das mudanças

---

## Arquivos a Criar/Modificar

### Criar
```
database/migrations/2026_06_24_000001_create_user_tenants_table.php
database/migrations/2026_06_24_000002_add_tenant_to_personal_access_tokens.php
app/Models/PersonalAccessToken.php (novo)
app/Services/TenantValidationService.php
app/Http/Middleware/ValidateTenantToken.php
app/Traits/ValidatesTenantAccess.php
tests/Feature/TenantAuthTest.php
```

### Modificar
```
app/Models/User.php
  + hasAccessToTenant($tenantId): bool
  + getAccessibleTenants(): Collection
  + tenants() relation
  
app/Http/Requests/Auth/LoginRequest.php
  + tenant_id validation
  
app/Http/Controllers/Api/AuthController.php
  + login() com tenant_id support
  
app/Policies/WalletPolicy.php
  + tenant validation
  
app/Policies/ClientPolicy.php
  + tenant validation
  
app/Policies/LedgerEntryPolicy.php
  + tenant validation
  
routes/api.php
  + ValidateTenantToken middleware
```

## Dependências Entre Milestones

```
Milestone 1 (Dados)
    ↓
Milestone 2 (Login)
    ↓
Milestone 3 (Tokens)
    ↓
Milestone 4 (Policies)
    ↓
Milestone 5 (Testes)
```

Milestone 1 e 2 podem ser parcialmente paralelas.
Milestone 3, 4, 5 devem ser sequenciais.

## Decisões Arquiteturais

### 1. Pivot Table vs Usuário Direto
**Decisão**: Usar pivot table `user_tenants`

**Motivo**:
- Suporta múltiplos tenants por usuário
- Permite metadados por relação (role, permissions)
- Permite soft delete de acesso
- Escala melhor que relação direta

### 2. Token Global vs Tenant-Specific
**Decisão**: Permitir ambos

**Motivo**:
- Frontend pode escolher tenant depois do login
- Permite trocar tenant sem re-autenticar
- Suporta admin com acesso a múltiplos tenants

### 3. Validação em Middleware vs Policy
**Decisão**: Ambos

**Motivo**:
- Middleware: Validação rápida no início
- Policy: Validação granular por recurso
- Defesa em profundidade (defense in depth)

### 4. Armazenar tenant_id no Token
**Decisão**: Sim, em `personal_access_tokens` table

**Motivo**:
- Permite auditoria de qual tenant foi acessado
- Facilita revogação de tokens por tenant
- Mantém histórico de acesso

## Riscos e Mitigações

| Risco | Probabilidade | Severidade | Mitigação |
|-------|---------------|-----------|-----------|
| Cross-tenant data leak | Alta | Crítica | Validação em múltiplos pontos |
| Token revocation não funciona | Média | Alta | Testes de revogação |
| Performance de pivots | Baixa | Média | Índices em user_tenants |
| Usuário sem acesso a nenhum tenant | Média | Média | Validação no login |

## Validações Esperadas ao Finalizar

- [x] Migrations executadas
- [x] Models compilam
- [x] Login sem tenant_id funciona
- [x] Login com tenant_id válido funciona
- [x] Login com tenant_id inválido retorna 403
- [x] Token com tenant_id é persistido
- [x] Middleware valida tokens limitados
- [x] Policies rejeitam cross-tenant
- [x] Testes passam 100%
- [x] Commit com mensagem descritiva

## Pré-requisitos

1. ✅ Tarefa A: Multi-tenancy Architecture (CONCLUÍDA)
2. ✅ Tarefa B: Middleware e Context (DEVE SER CONCLUÍDA)
3. ✅ Tarefa C: TenantResolver e TenantMiddleware (DEVE SER CONCLUÍDA)
4. ✅ Tarefa D: Models com BelongsToTenant trait (DEVE SER CONCLUÍDA)

## Próximas Etapas

Após Tarefa E:
- Tarefa F: API Endpoints tenantizados
- Tarefa G: Frontend tenant switching
- Tarefa H: Testes E2E
- Tarefa I: Documentação de operações

---

## Notas Importantes

1. **Security First**: Validar tenant em múltiplos pontos
2. **Backwards Compatible**: Manter login global funcionando
3. **Testing**: Coverage completo de casos de acesso
4. **Auditoria**: Log de trocar tenant
5. **Operações**: Documentar como revogar tokens por tenant
