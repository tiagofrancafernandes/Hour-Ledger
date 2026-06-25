# Instructor-Student Link Schema Documentation

**Date**: 2026-06-25  
**Phase**: 3 — Multi Instructor  
**Status**: ✅ COMPLETED

## Overview

This document describes the database schema, models, and infrastructure for the instructor-student linking system in Hour Ledger Ecosystem Phase 3.

## Schema Components

### 1. Enums (Type Safety)

#### InvitationStatus (`app/Enums/InvitationStatus.php`)
Represents the state of an invitation in the workflow:
- `PENDING`: Invitation sent, awaiting response
- `ACCEPTED`: Invitation accepted, link created
- `REJECTED`: Invitation rejected, no link created

Methods:
- `label()`: Human-readable label
- `isActive()`: Check if awaiting resolution
- `isResolvable()`: Check if can be resolved
- `isResolved()`: Check if already resolved

#### LinkStatus (`app/Enums/LinkStatus.php`)
Represents the state of an instructor-student link:
- `ACTIVE`: Link active, access granted
- `SUSPENDED`: Link suspended temporarily, access denied
- `REVOKED`: Link revoked permanently, access denied

Methods:
- `label()`: Human-readable label
- `isActive()`: Check if active
- `grantAccess()`: Check if access allowed
- `isSuspended()`: Check if suspended
- `isRevoked()`: Check if revoked

#### AccessLevel (`app/Enums/AccessLevel.php`)
Represents the access permission level:
- `BASIC`: Read-only access
- `FULL`: Full access
- `CUSTOM`: Custom permissions (future expansion)

Methods:
- `label()`: Human-readable label
- `allowsWrite()`: Check write permission
- `allowsRead()`: Check read permission

### 2. Database Tables

#### `invitations` Table
Stores invitations sent by instructors to students.

| Column | Type | Nullable | Notes |
|--------|------|----------|-------|
| id | unsignedBigInteger | NO | Primary Key |
| tenant_id | unsignedBigInteger | NO | FK → tenants |
| instructor_id | unsignedBigInteger | NO | FK → users |
| student_id | unsignedBigInteger | YES | FK → users (null until accepted) |
| email | string | YES | Email address (if student_id null) |
| status | string | NO | Enum: PENDING, ACCEPTED, REJECTED |
| token | string | NO | Unique invitation token (SHA-256) |
| expires_at | timestamp | NO | Token expiration (default: +7 days) |
| accepted_at | timestamp | YES | When accepted |
| rejected_at | timestamp | YES | When rejected |
| created_at | timestamp | NO | Record creation |
| updated_at | timestamp | NO | Record update |
| deleted_at | timestamp | YES | Soft delete |

**Indexes**:
- (tenant_id, instructor_id, status)
- (tenant_id, email, status)
- (expires_at)
- (token)

**Constraints**:
- Foreign key: tenant_id → tenants.id CASCADE
- Foreign key: instructor_id → users.id CASCADE
- Foreign key: student_id → users.id SET NULL
- Unique: token

#### `instructor_student_links` Table
Stores active links between instructors and students.

| Column | Type | Nullable | Notes |
|--------|------|----------|-------|
| id | unsignedBigInteger | NO | Primary Key |
| tenant_id | unsignedBigInteger | NO | FK → tenants |
| instructor_id | unsignedBigInteger | NO | FK → users |
| student_id | unsignedBigInteger | NO | FK → users |
| invitation_id | unsignedBigInteger | YES | FK → invitations |
| status | string | NO | Enum: ACTIVE, SUSPENDED, REVOKED |
| access_level | string | NO | Enum: BASIC, FULL, CUSTOM |
| revoked_at | timestamp | YES | When revoked |
| created_at | timestamp | NO | Record creation |
| updated_at | timestamp | NO | Record update |
| deleted_at | timestamp | YES | Soft delete (audit trail) |

**Indexes**:
- (tenant_id, instructor_id, student_id) UNIQUE
- (tenant_id, student_id, status)
- (tenant_id, instructor_id, status)
- (deleted_at)

**Constraints**:
- Foreign key: tenant_id → tenants.id CASCADE
- Foreign key: instructor_id → users.id CASCADE
- Foreign key: student_id → users.id CASCADE
- Foreign key: invitation_id → invitations.id SET NULL

#### `users` Table (Modified)
Added column for instructor context switching.

| Column | Type | Nullable | Notes |
|--------|------|----------|-------|
| active_instructor_id | unsignedBigInteger | YES | FK → users (self-referential) |

**Indexes**:
- (active_instructor_id)

**Constraints**:
- Foreign key: active_instructor_id → users.id SET NULL

### 3. Eloquent Models

#### Invitation (`app/Models/Invitation.php`)
Represents an invitation record.

**Relationships**:
- `tenant()`: BelongsTo Tenant
- `instructor()`: BelongsTo User (instructor)
- `student()`: BelongsTo User (student, nullable)
- `link()`: HasOne InstructorStudentLink

**Scopes**:
- `pending()`: Only PENDING invitations
- `accepted()`: Only ACCEPTED invitations
- `rejected()`: Only REJECTED invitations
- `byInstructor(int $id)`: Filter by instructor
- `forTenant(int $id)`: Filter by tenant
- `notExpired()`: Only non-expired
- `expired()`: Only expired

**Methods**:
- `isExpired(): bool`: Check if token expired
- `isPending(): bool`: Check if still pending
- `isResolvable(): bool`: Check if can be resolved
- `accept(): bool`: Transition to ACCEPTED
- `reject(): bool`: Transition to REJECTED

#### InstructorStudentLink (`app/Models/InstructorStudentLink.php`)
Represents an active link between instructor and student.

**Relationships**:
- `tenant()`: BelongsTo Tenant
- `instructor()`: BelongsTo User (instructor)
- `student()`: BelongsTo User (student)
- `invitation()`: BelongsTo Invitation

**Scopes**:
- `active()`: Only ACTIVE links (not deleted)
- `suspended()`: Only SUSPENDED links
- `revoked()`: Only REVOKED links
- `byInstructor(int $id)`: Filter by instructor
- `byStudent(int $id)`: Filter by student
- `forTenant(int $id)`: Filter by tenant
- `between(int $iId, int $sId)`: Filter by both

**Methods**:
- `isActive(): bool`: Check if active (status + not deleted)
- `grantAccess(): bool`: Check if access allowed
- `revoke(): bool`: Revoke and soft-delete
- `suspend(): bool`: Suspend temporarily
- `reactivate(): bool`: Resume after suspension

#### User (Updated `app/Models/User.php`)
Added instructor-student linking relationships.

**New Relationships**:
- `activeInstructor()`: BelongsTo User (self-referential)
- `sentInvitations()`: HasMany Invitation (as instructor)
- `receivedInvitations()`: HasMany Invitation (as student)
- `studentLinks()`: HasMany InstructorStudentLink (as instructor)
- `instructorLinks()`: HasMany InstructorStudentLink (as student)

**New Methods**:
- `hasActiveInstructorLink(int $id): bool`
- `hasActiveStudentLink(int $id): bool`

#### Tenant (Updated `app/Models/Tenant.php`)
Added relationships for invitations and links.

**New Relationships**:
- `invitations()`: HasMany Invitation
- `instructorStudentLinks()`: HasMany InstructorStudentLink

### 4. Database Seeders

#### TenantSeeder (`database/seeders/TenantSeeder.php`)
Creates a default tenant for development/testing.

Creates:
- 1 tenant: "Default Tenant" (slug: default-tenant)

#### InstructorStudentUserSeeder (`database/seeders/InstructorStudentUserSeeder.php`)
Creates instructor and student users.

Creates:
- 3 instructors: instructor1@example.com through instructor3@example.com
- 5 students: student1@example.com through student5@example.com
- All users linked to default tenant with active status

#### InvitationAndLinkSeeder (`database/seeders/InvitationAndLinkSeeder.php`)
Creates sample invitations and links for testing.

Creates:
- 1 PENDING invitation (instructor1 to pending-student@example.com)
- 1 ACCEPTED invitation (instructor2 to student1)
- 1 REJECTED invitation (instructor3 to student2)
- 15 ACTIVE links distributed across 3 instructors and 5 students

#### DatabaseSeeder (Updated `database/seeders/DatabaseSeeder.php`)
Orchestrates seeding in proper order:
1. RolesAndPermissionsSeeder
2. AdminUserSeeder
3. TenantSeeder
4. InstructorStudentUserSeeder
5. InvitationAndLinkSeeder
6. DevDummyDataSeeder

### 5. Console Commands

#### CreateInstructorStudentLink (`app/Console/Commands/CreateInstructorStudentLink.php`)
Creates an instructor-student link manually from CLI.

Usage:
```bash
php artisan instructor:link:create \
  --instructor=1 \
  --student=2 \
  --tenant=1 \
  --access-level=FULL \
  --status=ACTIVE
```

Options:
- `--instructor`: Instructor user ID (required)
- `--student`: Student user ID (required)
- `--tenant`: Tenant ID (required)
- `--access-level`: BASIC, FULL, or CUSTOM (default: FULL)
- `--status`: ACTIVE, SUSPENDED, or REVOKED (default: ACTIVE)

Validation:
- Both users must exist
- Tenant must exist
- Instructor and student cannot be same user
- No existing ACTIVE link between same pair

#### ListInstructorStudentLinks (`app/Console/Commands/ListInstructorStudentLinks.php`)
Lists instructor-student links with optional filtering.

Usage:
```bash
# List all active links
php artisan instructor:link:list

# Filter by tenant
php artisan instructor:link:list --tenant=1

# Filter by instructor
php artisan instructor:link:list --instructor=1

# Filter by student
php artisan instructor:link:list --student=1

# Filter by status
php artisan instructor:link:list --status=ACTIVE

# Include soft-deleted links
php artisan instructor:link:list --include-deleted
```

Options:
- `--tenant`: Filter by tenant ID
- `--instructor`: Filter by instructor user ID
- `--student`: Filter by student user ID
- `--status`: Filter by status (ACTIVE, SUSPENDED, REVOKED)
- `--include-deleted`: Include soft-deleted links

Output: Formatted table with ID, tenant, names, status, access level, and creation date.

## Migration Process

### Running Migrations

```bash
# Run all pending migrations
php artisan migrate

# Rollback last batch
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset

# Refresh (rollback + run)
php artisan migrate:refresh
```

Migrations created:
1. `2026_06_24_211000_create_invitations_table.php`
2. `2026_06_24_211100_create_instructor_student_links_table.php`
3. `2026_06_24_211200_add_instructor_context_to_users_table.php`

### Seeding Database

```bash
# Run all seeders (via DatabaseSeeder)
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=InvitationAndLinkSeeder

# Fresh migration + seed
php artisan migrate:refresh --seed
```

## Code Style Compliance

All code follows:
- **PSR-12**: PHP standard code style
- **Strict Types**: `declare(strict_types=1)` in all files
- **Type Hints**: All parameters and returns typed
- **Guard Clauses**: Early returns, no deep nesting
- **Else-less Pattern**: Prefer early returns over else blocks
- **Blank Lines**: Logical sections separated by blank lines
- **UNIVERSAL-CODE-STYLE-RULES.md**: All rules enforced

## Validation & Testing

### Migrations
✅ All 3 migrations execute without error  
✅ Foreign key constraints validated  
✅ Soft-deletes working correctly  
✅ Indexes created as specified  
✅ PostgreSQL compatibility verified  

### Models
✅ All relationships defined correctly  
✅ Scopes tested and working  
✅ Helper methods functional  
✅ Enum casting verified  
✅ Type hints complete  

### Seeders
✅ TenantSeeder: Creates 1 default tenant  
✅ InstructorStudentUserSeeder: Creates 3+5 users  
✅ InvitationAndLinkSeeder: Creates sample data  
✅ DatabaseSeeder: Orchestrates all in order  
✅ No foreign key conflicts  

### Commands
✅ CreateInstructorStudentLink: Creates links correctly  
✅ ListInstructorStudentLinks: Displays data properly  
✅ Filtering works as expected  
✅ Validation prevents invalid states  
✅ Help text clear and informative  

## Security Considerations

1. **Access Control**: Links checked before granting access
2. **Tenant Isolation**: All queries filtered by tenant_id
3. **Soft Delete Audit**: Deleted links preserved for audit trail
4. **Email Validation**: Invitation emails should be validated
5. **Token Security**: SHA-256 hashed tokens with expiration
6. **Type Safety**: Enums prevent invalid status values

## Performance Notes

1. **Indexes**: Composite indexes on common query patterns
2. **Soft Delete**: Partial index on active links recommended for large datasets
3. **Eager Loading**: Use `with()` to prevent N+1 queries
4. **Scopes**: Automatically filter deleted records in most queries

Example:
```php
InstructorStudentLink::with(['instructor', 'student'])
    ->active()
    ->forTenant($tenantId)
    ->get();
```

## Integration Points

### With Existing Systems
- **Tenancy**: Uses existing tenant_id column for isolation
- **Authentication**: Uses existing User model
- **Authorization**: Integrates with Spatie Laravel Permission
- **Database**: Uses existing PostgreSQL connection

### Future Expansion
1. **Policies**: Authorization policies for link management
2. **Events**: Events for invitation and link lifecycle
3. **Notifications**: Email notifications for invitations
4. **Audit Logging**: Log all link state changes
5. **API Endpoints**: REST endpoints for frontend integration

## Related Documentation

- Architecture: `docs/architecture/instructor-student-link.md`
- Types: `packages/backend/core/InstructorStudentTypes.php`
- Code Standards: `UNIVERSAL-CODE-STYLE-RULES.md`
- Laravel Guide: `CLAUDE.md`

## Completion Checklist

- [x] 3 Migrations created and tested
- [x] 4 Models created/updated with relationships
- [x] 4 Seeders created with sample data
- [x] 2 Console Commands created and functional
- [x] Database verified with proper constraints
- [x] Type safety (strict types, enums)
- [x] PSR-12 code style compliant
- [x] Documentation complete
- [x] All validations passing
- [x] Code review ready

---

**Status**: ✅ TASK B COMPLETE  
**Next**: Proceed to TASK C (Controllers & API Endpoints)
