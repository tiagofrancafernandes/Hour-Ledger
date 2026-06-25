<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Invitation Status Enum
 *
 * Represents the status of an invitation in the instructor-student
 * linking workflow.
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

    /**
     * Get human-readable label.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Aguardando Resposta',
            self::ACCEPTED => 'Aceito',
            self::REJECTED => 'Rejeitado',
        };
    }

    /**
     * Check if invitation is in active state (awaiting resolution).
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if invitation can be resolved (accepted or rejected).
     *
     * @return bool
     */
    public function isResolvable(): bool
    {
        return $this === self::PENDING;
    }

    /**
     * Check if invitation is resolved.
     *
     * @return bool
     */
    public function isResolved(): bool
    {
        return $this === self::ACCEPTED || $this === self::REJECTED;
    }
}
