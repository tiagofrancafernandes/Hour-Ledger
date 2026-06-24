<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder;

/**
 * TenantValidationService.
 *
 * Handles validation of user access to tenants and tenant operations.
 *
 * Responsibilities:
 * - Validate user can access a tenant
 * - Validate user can perform operations in a tenant
 * - Get list of accessible tenants for a user
 * - Validate tenant accessibility
 */
class TenantValidationService
{
    /**
     * Validate that a user has access to a specific tenant.
     *
     * Checks:
     * 1. User has active relationship in user_tenants table
     * 2. Tenant exists and is accessible
     * 3. Tenant allows operations
     *
     * @param User $user The user to validate
     * @param int $tenantId The tenant to check access for
     *
     * @return bool True if user can access tenant
     */
    public function userCanAccessTenant(User $user, int $tenantId): bool
    {
        return $user->hasAccessToTenant($tenantId);
    }

    /**
     * Validate that a tenant exists and is accessible.
     *
     * Checks:
     * 1. Tenant exists
     * 2. Tenant status allows access
     * 3. Tenant status allows operations
     *
     * @param int $tenantId The tenant ID to validate
     *
     * @return bool True if tenant is accessible
     */
    public function tenantIsAccessible(int $tenantId): bool
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return false;
        }

        return $tenant->allowsOperations();
    }

    /**
     * Get all tenants accessible to a user, formatted for response.
     *
     * Returns tenant data with minimal information needed for UI
     * tenant selection and context.
     *
     * @param User $user The user to get accessible tenants for
     *
     * @return array<int, array{id: int, name: string, slug: string|null, status: string}>
     */
    public function getUserAccessibleTenants(User $user): array
    {
        return $user->getAccessibleTenants()
            ->map(static function (Tenant $tenant): array {
                return [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'status' => $tenant->status->value,
                ];
            })
            ->toArray();
    }

    /**
     * Validate user can create a token scoped to a tenant.
     *
     * Ensures:
     * 1. User has access to the tenant
     * 2. Tenant is accessible and allows operations
     *
     * Used when creating tenant-limited tokens during login.
     *
     * @param User $user The user creating token
     * @param int $tenantId The tenant to scope token to
     *
     * @return bool True if token creation is allowed
     */
    public function userCanCreateTenantToken(User $user, int $tenantId): bool
    {
        // User must have access to tenant
        if (!$this->userCanAccessTenant($user, $tenantId)) {
            return false;
        }

        // Tenant must be accessible
        return $this->tenantIsAccessible($tenantId);
    }
}
