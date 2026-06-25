<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Access Level Enum
 *
 * Represents the access level granted to a student
 * through an instructor-student link.
 *
 * Future expansion for granular permission control.
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

    /**
     * Get human-readable label.
     *
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::BASIC => 'Básico (Leitura)',
            self::FULL => 'Completo',
            self::CUSTOM => 'Customizado',
        };
    }

    /**
     * Check if access level allows write operations.
     *
     * BASIC allows read-only, FULL and CUSTOM allow writes.
     *
     * @return bool
     */
    public function allowsWrite(): bool
    {
        return $this !== self::BASIC;
    }

    /**
     * Check if access level allows read operations.
     *
     * All levels allow read operations.
     *
     * @return bool
     */
    public function allowsRead(): bool
    {
        return true;
    }
}
