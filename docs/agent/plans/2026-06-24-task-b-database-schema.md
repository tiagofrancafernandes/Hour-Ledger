# TAREFA B: Database Schema & Migrations (Phase 3)
**Data**: 2026-06-24
**Status**: Plano em execução
**Objetivo**: Implementar schema de banco de dados para Multi Instrutor

## Contexto
- Laravel 12 + PHP 8.3 + PostgreSQL
- Arquitetura em: `docs/architecture/instructor-student-link.md`
- Tipos em: `packages/backend/core/InstructorStudentTypes.php`

## Milestones

### Milestone 1: Enums e Types
**Arquivo**: `app/Enums/InstructorStudentEnums.php`
- InvitationStatus enum (PENDING, ACCEPTED, REJECTED)
- LinkStatus enum (ACTIVE, SUSPENDED, REVOKED)
- AccessLevel enum (BASIC, FULL, CUSTOM)

### Milestone 2: Migrations (3 arquivos)

#### M1: create_invitations_table
- Campos: id, tenant_id, instructor_id, email, role, status, token, expires_at, accepted_at, rejected_at
- Soft-delete e timestamps
- Índices: (tenant_id), (email), (status), (expires_at), (token)

#### M2: create_instructor_student_links_table
- Campos: id, tenant_id, instructor_id, student_id, status, invitation_id, access_level, linked_at, revoked_at
- Soft-delete e timestamps
- Índice único: (tenant_id, instructor_id, student_id) WHERE deleted_at IS NULL
- Foreign keys com CASCADE apropriado

#### M3: add_instructor_context_to_users_table
- Column: active_instructor_id (nullable, FK)
- Índice em active_instructor_id

### Milestone 3: Models (4 arquivos)
- Tenant.php (atualizar relacionamentos)
- Invitation.php (com scopes e relacionamentos)
- InstructorStudentLink.php (soft-delete)
- User.php (atualizar relacionamentos e active_instructor_id)

### Milestone 4: Seeders (4 arquivos)
- TenantSeeder: 1 tenant padrão
- UserSeeder: 3 instructors + 5 students
- InstructorStudentLinkSeeder: 3 invitations + 15 links
- DatabaseSeeder: chamar todos em ordem

### Milestone 5: Console Commands (2 arquivos)
- CreateInstructorStudentLink: criar links manualmente
- ListInstructorStudentLinks: listar com filtros

### Milestone 6: Documentação & Commit
- INSTRUCTOR_STUDENT_SCHEMA.md em apps/hl-drive-api/docs/
- Commit com mensagem descritiva

## Validações Obrigatórias
- ✅ php artisan migrate
- ✅ php artisan db:seed
- ✅ Type-safe (strict types)
- ✅ PSR-12 compliant
- ✅ Foreign key constraints validadas
- ✅ Soft-deletes funcionando
- ✅ Índices criados corretamente

## Padrões a Seguir
- UNIVERSAL-CODE-STYLE-RULES.md (obrigatório)
- Existente em app/Models/ e database/
- PSR-12 via Laravel Pint
- Guard clauses, early returns
- Sem else, sem nesting excessivo
- Blank lines separando seções lógicas
