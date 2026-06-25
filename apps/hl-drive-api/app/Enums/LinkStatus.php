<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * InstructorStudentLink Status Enum
 *
 * Represents the status of an instructor-student link.
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

    /**
     * Get human-readable label.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Ativo',
            self::SUSPENDED => 'Suspenso',
            self::REVOKED => 'Revogado',
        };
    }

    /**
     * Check if link is in active state.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if link grants access.
     *
     * Only ACTIVE status grants access.
     * SUSPENDED and REVOKED deny access.
     *
     * @return bool
     */
    public function grantAccess(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if link is suspended.
     *
     * @return bool
     */
    public function isSuspended(): bool
    {
        return $this === self::SUSPENDED;
    }

    /**
     * Check if link is revoked.
     *
     * @return bool
     */
    public function isRevoked(): bool
    {
        return $this === self::REVOKED;
    }
}
