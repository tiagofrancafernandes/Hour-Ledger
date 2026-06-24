<?php

declare(strict_types=1);

namespace Packages\Backend\Core;

/**
 * Types and Enums for Instructor-Student Link System
 *
 * Used across backend to maintain type safety for invitation
 * and link management with context isolation.
 */

// ============================================
// ENUMS
// ============================================

/**
 * Invitation Status
 *
 * PENDING  → Convite enviado, aguardando resposta
 * ACCEPTED → Convite aceito, link criado
 * REJECTED → Convite rejeitado, não há link
 */
enum InvitationStatus: string
{
    case PENDING = 'PENDING';
    case ACCEPTED = 'ACCEPTED';
    case REJECTED = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Aguardando Resposta',
            self::ACCEPTED => 'Aceito',
            self::REJECTED => 'Rejeitado',
        };
    }

    public function isActive(): bool
    {
        return $this === self::PENDING;
    }

    public function isResolvable(): bool
    {
        return $this === self::PENDING;
    }
}

/**
 * InstructorStudentLink Status
 *
 * ACTIVE     → Vínculo ativo, acesso permitido
 * SUSPENDED  → Vínculo suspenso, acesso negado (temporário)
 * REVOKED    → Vínculo revogado, acesso negado (permanente)
 */
enum LinkStatus: string
{
    case ACTIVE = 'ACTIVE';
    case SUSPENDED = 'SUSPENDED';
    case REVOKED = 'REVOKED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Ativo',
            self::SUSPENDED => 'Suspenso',
            self::REVOKED => 'Revogado',
        };
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function grantAccess(): bool
    {
        return $this === self::ACTIVE;
    }
}

/**
 * Access Level (for future expansion)
 *
 * BASIC   → Acesso leitura apenas
 * FULL    → Acesso completo
 * CUSTOM  → Permissões granulares customizadas
 */
enum AccessLevel: string
{
    case BASIC = 'BASIC';
    case FULL = 'FULL';
    case CUSTOM = 'CUSTOM';

    public function label(): string
    {
        return match ($this) {
            self::BASIC => 'Básico (Leitura)',
            self::FULL => 'Completo',
            self::CUSTOM => 'Customizado',
        };
    }
}

/**
 * User Role in context
 *
 * STUDENT    → Usuário está como aluno
 * INSTRUCTOR → Usuário está como instrutor
 * BOTH       → Usuário tem ambos os papéis
 */
enum UserRole: string
{
    case STUDENT = 'STUDENT';
    case INSTRUCTOR = 'INSTRUCTOR';
    case BOTH = 'BOTH';

    public function isStudent(): bool
    {
        return $this === self::STUDENT || $this === self::BOTH;
    }

    public function isInstructor(): bool
    {
        return $this === self::INSTRUCTOR || $this === self::BOTH;
    }

    public function label(): string
    {
        return match ($this) {
            self::STUDENT => 'Aluno',
            self::INSTRUCTOR => 'Instrutor',
            self::BOTH => 'Aluno e Instrutor',
        };
    }
}

// ============================================
// DATA TRANSFER OBJECTS
// ============================================

/**
 * Invitation DTO
 *
 * Represents invitation state for API responses
 * and internal operations.
 */
class InvitationDTO
{
    public string $id;
    public string $instructorId;
    public ?string $studentId;
    public ?string $email;
    public InvitationStatus $status;
    public string $token;
    public \DateTime $expiresAt;
    public ?\DateTime $acceptedAt = null;
    public ?\DateTime $rejectedAt = null;
    public \DateTime $createdAt;
    public \DateTime $updatedAt;

    public function __construct(
        string $id,
        string $instructorId,
        ?string $studentId,
        ?string $email,
        InvitationStatus $status,
        string $token,
        \DateTime $expiresAt,
        \DateTime $createdAt,
        \DateTime $updatedAt,
        ?\DateTime $acceptedAt = null,
        ?\DateTime $rejectedAt = null,
    ) {
        $this->id = $id;
        $this->instructorId = $instructorId;
        $this->studentId = $studentId;
        $this->email = $email;
        $this->status = $status;
        $this->token = $token;
        $this->expiresAt = $expiresAt;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->acceptedAt = $acceptedAt;
        $this->rejectedAt = $rejectedAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTime();
    }

    public function isPending(): bool
    {
        return $this->status === InvitationStatus::PENDING;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'instructor_id' => $this->instructorId,
            'student_id' => $this->studentId,
            'email' => $this->email,
            'status' => $this->status->value,
            'expires_at' => $this->expiresAt->format('c'),
            'accepted_at' => $this->acceptedAt?->format('c'),
            'rejected_at' => $this->rejectedAt?->format('c'),
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c'),
        ];
    }
}

/**
 * InstructorStudentLink DTO
 *
 * Represents instructor-student link state for API
 * responses and internal operations.
 */
class InstructorStudentLinkDTO
{
    public string $id;
    public string $instructorId;
    public string $studentId;
    public LinkStatus $status;
    public AccessLevel $accessLevel;
    public string $invitationId;
    public \DateTime $createdAt;
    public \DateTime $updatedAt;
    public ?\DateTime $revokedAt = null;
    public ?\DateTime $deletedAt = null;

    public function __construct(
        string $id,
        string $instructorId,
        string $studentId,
        LinkStatus $status,
        AccessLevel $accessLevel,
        string $invitationId,
        \DateTime $createdAt,
        \DateTime $updatedAt,
        ?\DateTime $revokedAt = null,
        ?\DateTime $deletedAt = null,
    ) {
        $this->id = $id;
        $this->instructorId = $instructorId;
        $this->studentId = $studentId;
        $this->status = $status;
        $this->accessLevel = $accessLevel;
        $this->invitationId = $invitationId;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->revokedAt = $revokedAt;
        $this->deletedAt = $deletedAt;
    }

    public function isActive(): bool
    {
        return $this->status->isActive() && $this->deletedAt === null;
    }

    public function grantAccess(): bool
    {
        return $this->status->grantAccess() && $this->deletedAt === null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'instructor_id' => $this->instructorId,
            'student_id' => $this->studentId,
            'status' => $this->status->value,
            'access_level' => $this->accessLevel->value,
            'invitation_id' => $this->invitationId,
            'created_at' => $this->createdAt->format('c'),
            'updated_at' => $this->updatedAt->format('c'),
            'revoked_at' => $this->revokedAt?->format('c'),
        ];
    }
}

/**
 * InstructorContext DTO
 *
 * Represents current instructor context in request
 * lifecycle, similar to TenantContext.
 */
class InstructorContextDTO
{
    public string $userId;
    public string $tenantId;
    public ?string $activeInstructorId;

    public function __construct(
        string $userId,
        string $tenantId,
        ?string $activeInstructorId = null,
    ) {
        $this->userId = $userId;
        $this->tenantId = $tenantId;
        $this->activeInstructorId = $activeInstructorId;
    }

    public function hasInstructorContext(): bool
    {
        return $this->activeInstructorId !== null;
    }

    public function isInstructorView(): bool
    {
        return $this->activeInstructorId === $this->userId;
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'tenant_id' => $this->tenantId,
            'active_instructor_id' => $this->activeInstructorId,
        ];
    }
}

// ============================================
// EXCEPTIONS
// ============================================

class InvalidInvitationException extends \Exception
{
    public static function notFound(string $id): self
    {
        return new self("Invitation '{$id}' not found");
    }

    public static function alreadyResolved(string $id, InvitationStatus $status): self
    {
        return new self("Invitation '{$id}' already {$status->value}");
    }

    public static function expired(string $id): self
    {
        return new self("Invitation '{$id}' has expired");
    }

    public static function invalidToken(string $token): self
    {
        return new self("Invalid or expired invitation token: {$token}");
    }

    public static function duplicatePending(string $instructorId, string $studentId): self
    {
        return new self("Pending invitation already exists for instructor {$instructorId} and student {$studentId}");
    }
}

class InvalidLinkException extends \Exception
{
    public static function notFound(string $id): self
    {
        return new self("InstructorStudentLink '{$id}' not found");
    }

    public static function alreadyActive(string $instructorId, string $studentId): self
    {
        return new self("Active link already exists between instructor {$instructorId} and student {$studentId}");
    }

    public static function accessDenied(string $studentId, string $instructorId): self
    {
        return new self("Access denied: Student {$studentId} has no active link with instructor {$instructorId}");
    }

    public static function instructorContextMissing(): self
    {
        return new self("Instructor context required but not available in request");
    }
}

class InvalidInstructorContextException extends \Exception
{
    public static function missingInstructor(): self
    {
        return new self("Instructor context is required but not set");
    }

    public static function tenantMismatch(string $contextTenant, string $resourceTenant): self
    {
        return new self("Tenant mismatch: context tenant {$contextTenant} != resource tenant {$resourceTenant}");
    }

    public static function instructorMismatch(string $contextInstructor, string $resourceInstructor): self
    {
        return new self("Instructor mismatch: context instructor {$contextInstructor} != resource instructor {$resourceInstructor}");
    }
}
