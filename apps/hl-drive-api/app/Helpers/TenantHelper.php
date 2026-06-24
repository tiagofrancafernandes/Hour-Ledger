<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Services\TenantResolver;

/**
 * TenantHelper provides convenient helper functions for tenant operations.
 *
 * Usage:
 *     // Get current tenant ID
 *     $tenantId = tenantId();
 *
 *     // Get current schema
 *     $schema = tenantSchema();
 *
 *     // Get tenant context
 *     $context = tenantContext();
 *
 *     // Check if tenant is set
 *     if (hasTenant()) { ... }
 */
class TenantHelper
{
    /**
     * Get the current tenant ID.
     *
     * @return int|null
     */
    public static function tenantId(): ?int
    {
        return app(TenantResolver::class)->getTenantId();
    }

    /**
     * Get the current tenant schema name.
     *
     * @return string
     * @throws \App\Exceptions\TenantNotFound
     */
    public static function tenantSchema(): string
    {
        return app(TenantResolver::class)->getSchema();
    }

    /**
     * Get the current tenant context.
     *
     * @return \App\Models\TenantContext
     * @throws \App\Exceptions\TenantNotFound
     */
    public static function tenantContext()
    {
        return app(TenantResolver::class)->getContext();
    }

    /**
     * Check if a tenant is currently set.
     *
     * @return bool
     */
    public static function hasTenant(): bool
    {
        return app(TenantResolver::class)->hasTenant();
    }

    /**
     * Get the current user ID within tenant context.
     *
     * @return int|null
     */
    public static function tenantUserId(): ?int
    {
        return app(TenantResolver::class)->getUserId();
    }
}
