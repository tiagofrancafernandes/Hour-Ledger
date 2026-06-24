<?php

declare(strict_types=1);

namespace App\Enums;

enum TenantStatus: string
{
    /**
     * Tenant is active and operational.
     * Users can access all features and data.
     */
    case ACTIVE = 'active';

    /**
     * Tenant is suspended.
     * Users cannot access data or features, but schema is preserved.
     * Useful for payment issues, abuse, or maintenance.
     */
    case SUSPENDED = 'suspended';

    /**
     * Tenant is deleted.
     * Soft delete - schema may be archived or recovered.
     * Hard delete happens after retention period.
     */
    case DELETED = 'deleted';

    /**
     * Get label for display in UI.
     */
    public function label(): string
    {
        return match($this) {
            self::ACTIVE => 'Ativo',
            self::SUSPENDED => 'Suspenso',
            self::DELETED => 'Deletado',
        };
    }

    /**
     * Get description for documentation/help.
     */
    public function description(): string
    {
        return match($this) {
            self::ACTIVE => 'Tenant operacional e acessível',
            self::SUSPENDED => 'Tenant suspenso, sem acesso a dados',
            self::DELETED => 'Tenant deletado, em período de retenção',
        };
    }

    /**
     * Check if tenant is active and can be accessed.
     */
    public function isAccessible(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if tenant allows data operations.
     */
    public function allowsOperations(): bool
    {
        return $this === self::ACTIVE;
    }

    /**
     * Check if tenant is deleted (soft delete).
     */
    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }

    /**
     * Get all active statuses.
     *
     * @return array<int, TenantStatus>
     */
    public static function active(): array
    {
        return [self::ACTIVE];
    }

    /**
     * Get all accessible statuses.
     *
     * @return array<int, TenantStatus>
     */
    public static function accessible(): array
    {
        return [self::ACTIVE];
    }

    /**
     * Get all inactive statuses.
     *
     * @return array<int, TenantStatus>
     */
    public static function inactive(): array
    {
        return [self::SUSPENDED, self::DELETED];
    }
}
