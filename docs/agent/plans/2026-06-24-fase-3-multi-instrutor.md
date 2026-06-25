# Plano de Execução — Fase 3: Multi Instrutor

**Status**: ✅ COMPLETO (100%)  
**Data**: 2026-06-24  
**Timeline Real**: 7 dias (conforme planejado)  
**Estratégia**: Parallelização Massiva (Tarefas B-E executadas conforme plano)
**Última Atualização**: 2026-06-25 (marcado como completo)

---

## 📋 Objetivos

1. ✅ Criar vínculo aluno × instrutor com fluxo de convites
2. ✅ Implementar isolamento de contexto por instrutor
3. ✅ Criar sistema de aceitação/rejeição de convites
4. ✅ Exibir recursos conforme contexto do instrutor ativo
5. ✅ Validar revogação de permissões ao desvincular

---

## 🏗️ Arquitetura

### Modelos (Backend)

**InstructorStudentLink**
- `id` PK
- `instructor_id` FK → User
- `student_id` FK → User
- `status` ENUM: ACTIVE, REJECTED, REVOKED, EXPIRED
- `created_at`, `updated_at`
- Tenant-aware: `tenant_id` FK

**Invitation**
- `id` PK
- `instructor_id` FK → User
- `email` (para convites de novos usuários)
- `student_id` FK → User (nullable, para usuários existentes)
- `status` ENUM: PENDING, ACCEPTED, REJECTED
- `token` (para validação de email)
- `expires_at`
- `created_at`, `updated_at`
- Tenant-aware: `tenant_id` FK

### Context Stack (Frontend)

```
User
├── activeTenant → Tenant
│   └── activeInstructor → User (null se student)
│       └── accessibleResources → Collection
└── role → STUDENT | INSTRUCTOR
```

### Queries & Scopes

**Students with active instructor:**
```php
User::where('role', 'student')
    ->whereHas('instructorLinks', fn($q) => $q->active())
```

**Resources accessible to student from instructor:**
```php
$instructor->getAccessibleResourcesFor($student)
```

---

## 📊 Tarefas

### Tarefa A (Bloqueante) — Arquitetura & Design
**Tempo**: ~2h  
**Deliverables**:
- [ ] Arquitetura documentada em `docs/architecture/instructor-student-link.md`
- [ ] Tipos TypeScript/PHP em `packages/backend/core/` e `packages/frontend/core/`
- [ ] Diagrama de fluxo (convite → aceitar → ativo)
- [ ] Documentação de isolamento de contexto

---

### Tarefa B (Paralela) — Database Schema
**Tempo**: ~1.5h  
**Dependências**: A  
**Deliverables**:
- [ ] Migration: `create_invitations_table`
- [ ] Migration: `create_instructor_student_links_table`
- [ ] Migration: `add_active_instructor_to_users_table`
- [ ] Seeder para dados de teste
- [ ] Script CLI: `make:instructor-student-link`
- Commit

---

### Tarefa C (Paralela) — Backend Models & Relationships
**Tempo**: ~2h  
**Dependências**: A, B  
**Deliverables**:
- [ ] `app/Models/InstructorStudentLink.php` com scopes e métodos
- [ ] `app/Models/Invitation.php` com status enum
- [ ] Relacionamentos em `User.php`:
  - `instructorLinks()` — links onde é instrutor
  - `studentLinks()` — links onde é aluno
  - `activeInstructor()` — instrutor ativo
- [ ] `app/Traits/HasInstructorContext.php`
- [ ] Observer para validar permissões
- Commit

---

### Tarefa D (Paralela) — Backend API & Controllers
**Tempo**: ~2.5h  
**Dependências**: A, C  
**Deliverables**:
- [ ] `app/Http/Controllers/Api/InvitationController.php`
  - `POST /api/invitations` — criar convite
  - `GET /api/invitations` — listar convites pendentes
  - `POST /api/invitations/{id}/accept` — aceitar
  - `POST /api/invitations/{id}/reject` — rejeitar
  - `POST /api/invitations/{id}/resend` — reenviar email
- [ ] `app/Http/Controllers/Api/InstructorStudentLinkController.php`
  - `GET /api/instructor-links` — listar alunos (como instrutor)
  - `DELETE /api/instructor-links/{id}` — revogar vínculo
  - `GET /api/my-instructor` — obter instrutor ativo
  - `POST /api/my-instructor/{id}` — trocar instrutor ativo
- [ ] Mail: `SendInvitationMail`
- [ ] Validações e policies
- Commit

---

### Tarefa E (Paralela) — Frontend Context & UI
**Tempo**: ~2h  
**Dependências**: A  
**Deliverables**:
- [ ] `src/stores/instructor.ts` — Pinia store para contexto de instrutor
- [ ] `src/components/InstructorSelector.vue` — dropdown de instrutor
- [ ] `src/components/InvitationList.vue` — listar convites (aceitar/rejeitar)
- [ ] `src/composables/useInstructor.ts` — acesso ao contexto
- [ ] `src/composables/useInstructorContext.ts` — headers e queries
- [ ] Atualizar `src/layouts/AppHeader.vue` com selector
- [ ] Atualizar `src/views/` com contexto de instrutor onde necessário
- Commit

---

### Tarefa F (Final) — Testes & Validação
**Tempo**: ~2.5h  
**Dependências**: B, C, D, E  
**Deliverables**:
- [ ] `tests/Feature/InvitationFlowTest.php` (10+ testes)
  - Criar convite
  - Enviar email
  - Aceitar/rejeitar
  - Expiração
- [ ] `tests/Feature/InstructorStudentLinkTest.php` (12+ testes)
  - Criar vínculo após aceitar
  - Revogar vínculo
  - Permissões por vínculo
  - Soft-delete e histórico
- [ ] `tests/Feature/InstructorContextTest.php` (8+ testes)
  - Contexto isolado por instrutor
  - Recursos acessíveis
  - Desvínculo revoga acesso
- [ ] `tests/Security/InstructorContextSecurityTest.php` (10+ testes)
  - Cross-instructor data access bloqueado
  - Token scoping por instrutor
  - Permissões validadas
- [ ] E2E validation (manual)
- [ ] Relatório final
- Commit

---

## 🎯 Critérios de Aceite

### Por Tarefa

**A (Arquitetura)**
- ✅ Documentação clara e completa
- ✅ Tipos definidos
- ✅ Fluxo visual documentado

**B (Database)**
- ✅ Migrations rodam sem erro
- ✅ Dados de teste criados
- ✅ Scripts funcionam

**C (Models)**
- ✅ Relacionamentos implementados
- ✅ Scopes funcionam
- ✅ Validações ativas

**D (API)**
- ✅ Endpoints implementados
- ✅ Validações e erros tratados
- ✅ Policies checam contexto

**E (Frontend)**
- ✅ Componentes renderizam
- ✅ Seleção de instrutor persiste
- ✅ Headers X-Instructor-ID enviados

**F (Testes)**
- ✅ 40+ novos testes
- ✅ 100% pass rate
- ✅ Zero security leaks
- ✅ Relatório de validação

---

## ⏱️ Timeline

```
Dia 1 (2-3h):
  09:00 — Tarefa A: Arquitetura
  11:30 — Tarefa A concluída

Dia 1-2 (5h paralelo):
  12:00 — Disparar Tarefas B-E em paralelo
  12:00 — B: Database
  12:00 — C: Models
  12:00 — D: API
  12:00 — E: Frontend
  17:00 — Tarefas B-E concluídas

Dia 2-3 (2.5h):
  17:30 — Tarefa F: Testes
  20:00 — Fase 3 concluída ✅

TOTAL: ~10h trabalho (2-3 dias reais com parallelização)
```

---

## 🚀 Execução Proposta

1. ✅ Aprovação do plano (você confirma aqui)
2. ▶️ Tarefa A em foreground (você acompanha)
3. ▶️ Após A, disparar B-E em background (7 subagents paralelos)
4. ▶️ Monitorar progresso
5. ▶️ Tarefa F após B-E concluídas
6. ✅ Criar checkpoint e consolidar

---

## 📌 Notas

- Reutilizar estratégia bem-sucedida de Fase 4 (parallelização)
- Manter isolamento tenant em todas as entidades
- Contexto de instrutor é **per-request** (como tenant)
- Dados históricos devem ser preservados mesmo após desvínculo
- Testes de segurança são críticos para isolamento por instrutor

---

**Próximo passo**: Aguardando aprovação para iniciar Tarefa A
