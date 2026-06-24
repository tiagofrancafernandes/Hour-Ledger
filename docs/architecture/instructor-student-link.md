# Arquitetura: Vínculo Aluno × Instrutor

**Documento de Design**  
**Data**: 2026-06-24  
**Fase**: 3 — Multi Instrutor  
**Status**: ✅ Referência arquitetônica

---

## 🎯 Visão Geral

O sistema de vínculo aluno × instrutor implementa autorização explícita e contexto isolado por instrutor, permitindo que:

1. **Instrutores** convidem alunos através de email
2. **Alunos** aceitem ou rejeitem convites
3. **Alunos** acessem recursos apenas de instrutores com vínculo ativo
4. **Instrutores** gerenciem múltiplos alunos com permissões granulares
5. **Sistema** mantenha histórico após desvínculo (auditoria)

---

## 📐 Modelos de Dados

### 1. Invitation (Convite)

```
Invitations (tenant_aware)
├── id [uuid PK]
├── tenant_id [uuid FK] → tenants
├── instructor_id [uuid FK] → users
├── student_id [uuid FK] → users (nullable)
├── email [string] — email de convite (se student_id NULL)
├── status [enum: PENDING, ACCEPTED, REJECTED]
├── token [string unique] — para link de aceitar email
├── expires_at [datetime]
├── accepted_at [datetime nullable]
├── rejected_at [datetime nullable]
├── created_at [datetime]
├── updated_at [datetime]
└── indexes
    ├── (tenant_id, instructor_id, status)
    ├── (tenant_id, email, status)
    ├── (token)
    └── (expires_at)
```

**Regras**:
- Um convite por instructor_id + student_id em estado PENDING
- Email deve ser validado (filtro de whitelist ou domínios)
- Token expira em 7 dias (configurable)
- Após ACCEPTED, novo convite só pode ser criado se link foi revogado
- Status é imutável após conclusão (ACCEPTED/REJECTED)

### 2. InstructorStudentLink (Vínculo)

```
InstructorStudentLinks (tenant_aware, soft-deletable)
├── id [uuid PK]
├── tenant_id [uuid FK] → tenants
├── instructor_id [uuid FK] → users
├── student_id [uuid FK] → users
├── status [enum: ACTIVE, SUSPENDED, REVOKED]
├── invitation_id [uuid FK] → invitations
├── access_level [enum: BASIC, FULL, CUSTOM] — para future expansion
├── created_at [datetime]
├── updated_at [datetime]
├── deleted_at [datetime nullable] — soft-delete
├── revoked_at [datetime nullable]
└── indexes
    ├── (tenant_id, instructor_id, student_id) UNIQUE (when not deleted)
    ├── (tenant_id, student_id, status)
    ├── (tenant_id, instructor_id, status)
    └── (deleted_at) — para preserved history
```

**Regras**:
- Um vínculo ATIVO por instructor_id + student_id
- Soft-delete preserva histórico para auditoria
- Status REVOKED marca data explícita de revogação
- Relacionamento 1:N com Invitation (mas apenas 1 convite resulta em link)
- Permissions derivam de status (ACTIVE = acesso, SUSPENDED/REVOKED = acesso negado)

### 3. Alterações em User

```php
// Adicionado a users table:
- active_instructor_id [uuid FK] → users (nullable)
  // Qual instrutor está "ativo" para este aluno agora
  // Reduz complexidade no frontend/queries
```

---

## 🔄 Fluxo de Convite

```
┌─────────────────────────────────────────────────────────────────┐
│ ESTADO: SEM VÍNCULO                                             │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ Instrutor cria convite
                              ▼
        ┌─────────────────────────────────────────┐
        │ Invitation.PENDING                      │
        ├─────────────────────────────────────────┤
        │ • Email enviado                         │
        │ • Token gerado e válido por 7 dias      │
        │ • Aluno recebe email com link de aceite │
        └─────────────────────────────────────────┘
                      │                 │
        REJEITA ──────┘                 └────── ACEITA
           │                                      │
           ▼                                      ▼
    Invitation.REJECTED              InstructorStudentLink.ACTIVE
    + timestamp.rejected_at           + Invitation.ACCEPTED
                                      + timestamp.accepted_at
                                      + Link.created_at

┌─────────────────────────────────────────┐
│ ESTADO: VÍNCULO ATIVO                   │
├─────────────────────────────────────────┤
│ • Aluno acessa recursos do instrutor    │
│ • Instrutor acessa dados do aluno       │
│ • Contexto isolado por instructor_id    │
└─────────────────────────────────────────┘
          │
          │ Instrutor revoga / Aluno remove
          ▼
    InstructorStudentLink.REVOKED
    + timestamp.revoked_at + deleted_at
    
┌─────────────────────────────────────────┐
│ ESTADO: DESVINCULADO                    │
├─────────────────────────────────────────┤
│ • Histórico preservado                  │
│ • Acesso negado                         │
│ • Dados sensíveis ocultados              │
│ • Transações/movimentações visíveis     │
└─────────────────────────────────────────┘
```

---

## 🔐 Isolamento de Contexto

### Camadas de Isolamento

**1. Application Layer (Middleware)**
```php
// X-Instructor-ID header (opcional, defaults a NULL ou "none")
// Se set, valida que user tem link ativo com esse instructor
// Se NULL, user está vendo perspectiva própria
```

**2. Database Layer (Tenant + Instructor)**
```
queries filtram por:
  - tenant_id (já existente)
  - instructor_id (novo, apenas se applicable)
  - status = ACTIVE (para InstructorStudentLink)
```

**3. Model Layer (Scopes)**
```php
// Nova trait: BelongsToInstructor (similar a BelongsToTenant)
// Auto-aplica scope global a models tenantizados
User::forActiveInstructor()  // filtra recursos
```

**4. Query Layer (Hard Scope)**
```php
// Policies checam instructor_id explicitamente
// Relacionamentos são lazy-loaded apenas com scopes ativos
// Acesso sem scope adequado retorna 403
```

---

## 📊 Permissões & Access Control

### Matriz de Acesso

| Recurso | Student com Link | Student sem Link | Instructor | Admin |
|---------|------------------|------------------|-----------|-------|
| Própria Wallet | ✅ | ✅ | ❌ | ✅ |
| Instrutor - Schedule | ✅ | ❌ | ✅ | ✅ |
| Instrutor - Dados | ✅ | ❌ | ✅ | ✅ |
| Aluno - Agenda | ❌ | ❌ | ✅ (if linked) | ✅ |
| Aluno - Wallet | ❌ | ❌ | ✅ (if linked) | ✅ |
| Convites | ✅ | ✅ | ✅ | ✅ |
| Histórico | ✅ | ✅ | ✅ (deleted_at) | ✅ |

### Permission Checks

```php
// Student acessando recurso de instrutor
if (!$user->hasActiveInstructorLink($instructorId)) {
    abort(403, 'No active instructor link');
}

// Instructor acessando dados de aluno
if (!$instructor->hasActiveStudentLink($studentId)) {
    abort(403, 'No active student link');
}

// Cross-tenant acesso bloqueado
if ($user->tenant_id !== $link->tenant_id) {
    abort(403, 'Tenant mismatch');
}
```

---

## 🛠️ Tipos TypeScript

### frontend/core/types.ts

```typescript
// Invitation
export interface Invitation {
  id: string;
  instructor_id: string;
  student_id?: string;
  email?: string;
  status: 'PENDING' | 'ACCEPTED' | 'REJECTED';
  token: string;
  expires_at: string; // ISO datetime
  accepted_at?: string;
  rejected_at?: string;
  created_at: string;
  updated_at: string;
}

// InstructorStudentLink
export interface InstructorStudentLink {
  id: string;
  instructor_id: string;
  student_id: string;
  status: 'ACTIVE' | 'SUSPENDED' | 'REVOKED';
  access_level: 'BASIC' | 'FULL' | 'CUSTOM';
  created_at: string;
  updated_at: string;
  revoked_at?: string;
}

// User with instructor context
export interface UserWithInstructor {
  id: string;
  email: string;
  role: 'STUDENT' | 'INSTRUCTOR';
  active_instructor_id?: string; // Current active instructor
  active_instructor?: User;      // Populated when needed
}

// Context stack
export interface InstructorContext {
  user_id: string;
  tenant_id: string;
  active_instructor_id?: string;  // null if viewing own resources
  access_level?: string;
}
```

---

## 📡 API Endpoints

### Invitation Management

```
POST   /api/invitations
GET    /api/invitations?status=PENDING
GET    /api/invitations/{id}
POST   /api/invitations/{id}/accept
POST   /api/invitations/{id}/reject
POST   /api/invitations/{id}/resend
DELETE /api/invitations/{id}
```

### Link Management

```
GET    /api/instructor-links?role=student
GET    /api/instructor-links?role=instructor
GET    /api/instructor-links/{id}
DELETE /api/instructor-links/{id}  # revoke
POST   /api/my-instructor/{id}     # switch active
GET    /api/my-instructor          # get current
```

---

## 🧪 Cenários de Teste Críticos

### Fluxo de Convite
- ✅ Criar convite (instructor → aluno existente)
- ✅ Criar convite com email (instructor → novo aluno)
- ✅ Enviar email com token
- ✅ Aceitar convite (cria link)
- ✅ Rejeitar convite
- ✅ Convite expirado após 7 dias
- ✅ Re-enviar convite

### Isolamento de Contexto
- ✅ Student vê apenas schedule do instructor com link ativo
- ✅ Student sem link não vê schedule de ninguém
- ✅ Instructor vê apenas alunos com link ativo
- ✅ Trocar instructor_id ativo filtra recursos
- ✅ Cross-tenant links bloqueados

### Revogação & Histórico
- ✅ Revogar link: student perde acesso imediatamente
- ✅ Histórico preservado: transações ainda visíveis
- ✅ Soft-delete: dados não aparecem em queries normais
- ✅ Admin pode ver deleted_at histórico
- ✅ Re-criar link após revogação funciona

### Segurança
- ✅ Token válido é verificado
- ✅ Email é validado (whitelist)
- ✅ SQL injection não vaza dados
- ✅ Cross-instructor access bloqueado
- ✅ Cross-tenant access bloqueado
- ✅ Permissions validadas em policies

---

## 🔄 Integração com Tenant Context

```php
// Middleware stack agora é:
TenantMiddleware (resolve tenant)
  ↓
InstructorContextMiddleware (resolve instructor_id, optional)
  ↓
Request (can use both TenantResolver::get() e InstructorResolver::get())

// Queries:
Model::where('tenant_id', TenantResolver::getTenantId())
      ->where('instructor_id', InstructorResolver::getInstructorId())
      // Auto-applied via scopes
```

---

## 📋 Checklist de Implementação

### Backend
- [ ] Migration: `create_invitations_table`
- [ ] Migration: `create_instructor_student_links_table`
- [ ] Migration: `add_active_instructor_to_users`
- [ ] Model: `Invitation`
- [ ] Model: `InstructorStudentLink`
- [ ] Trait: `HasInstructorContext` em `User`
- [ ] Trait: `BelongsToInstructor` para models
- [ ] Scope: `InstructorScope`
- [ ] Middleware: `InstructorContextMiddleware`
- [ ] Service: `InstructorResolver` (singleton)
- [ ] Controllers: `InvitationController`, `InstructorStudentLinkController`
- [ ] Mail: `SendInvitationMail`
- [ ] Policies: Updated com instructor checks
- [ ] Console: Commands para criar/listar links

### Frontend
- [ ] Store: `src/stores/instructor.ts`
- [ ] Component: `InstructorSelector.vue`
- [ ] Component: `InvitationList.vue`
- [ ] Composable: `useInstructor.ts`
- [ ] Composable: `useInstructorContext.ts`
- [ ] Update: `AppHeader.vue`
- [ ] Update: Relevant views

### Testes
- [ ] InvitationFlowTest (10+ testes)
- [ ] InstructorStudentLinkTest (12+ testes)
- [ ] InstructorContextTest (8+ testes)
- [ ] InstructorContextSecurityTest (10+ testes)
- [ ] Coverage: >85%

---

## 🚀 Notas Implementação

1. **Tenant-aware**: Todos os modelos herdam `BelongsToTenant` + novo `BelongsToInstructor`
2. **Instructor opcional**: Um user pode ser `student` (tem instructor_id) ou `instructor` (tem students) ou ambos
3. **Context stack**: Frontend mantem `activeTenant` + `activeInstructor` no Pinia
4. **Headers**: `X-Tenant-ID` + novo `X-Instructor-ID` (opcional)
5. **Lazy-loaded**: `active_instructor` no User é nullable, carregado apenas quando necessário
6. **Soft-delete**: Preserva auditoria mas remove de queries normais
7. **Email validation**: Implementar whitelist de domínios para convites

---

## 📚 Referências

- `docs/domain/drive/instructor-student-link.md` — Requirements
- `docs/architecture/multi-tenancy.md` — Tenant isolation (aplicar padrão ao instructor)
- `docs/architecture/tenant-schema-strategy.md` — Schema strategies
- `AGENTS.md` — Code style e convenções

---

**Status**: ✅ Arquitetura pronta para implementação  
**Próximo**: Disparar Tarefas B-E em paralelo após aprovação
