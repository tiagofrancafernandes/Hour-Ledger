<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\Tenant;
use App\Services\TenantValidationService;
use Illuminate\Database\Eloquent\Model;

/**
 * ValidatesTenantAccess Trait.
 *
 * Provides helper methods for policies to validate tenant access.
 *
 * Usage in a Policy:
 * ```php
 * public function view(User $user, Wallet $wallet): bool
 * {
 *     if (!$this->userCanAccessTenantResource($user, $wallet)) {
 *         return false;
 *     }
 *
 *     return $user->can('wallet.view');
 * }
 * ```
 *
 * Assumes:
 * - User model has hasAccessToTenant() method
 * - Resource model has tenant_id property or relationship
 * - TenantValidationService is available via service container
 */
trait ValidatesTenantAccess
{
    /**
     * Validate that user has access to the tenant of a resource.
     *
     * Checks if:
     * 1. Resource has tenant_id
     * 2. User can access that tenant
     *
     * This should be called first in any policy method that checks resource access.
     *
     * @param \Illuminate\Foundation\Auth\User $user The user to check
     * @param Model $resource The resource to check tenant access for
     *
     * @return bool True if user can access resource's tenant
     */
    protected function userCanAccessTenantResource($user, Model $resource): bool
    {
        // Check if resource has tenant_id attribute or relationship
        if (!$this->resourceHasTenantId($resource)) {
            // Resource is not tenant-scoped, allow to pass through
            return true;
        }

        // Get tenant_id from resource
        $tenantId = $this->getTenantIdFromResource($resource);

        // Validate user has access to this tenant
        return $user->hasAccessToTenant($tenantId);
    }

    /**
     * Check if a resource has a tenant_id.
     *
     * Checks both as direct attribute and as relationship.
     *
     * @param Model $resource The resource to check
     *
     * @return bool True if resource has tenant_id
     */
    protected function resourceHasTenantId(Model $resource): bool
    {
        // Check as direct attribute
        if ($resource->getAttribute('tenant_id') !== null) {
            return true;
        }

        // Check if has tenant() relationship
        if (method_exists($resource, 'tenant')) {
            try {
                $tenant = $resource->tenant;

                return $tenant !== null;
            } catch (\Exception) {
                return false;
            }
        }

        return false;
    }

    /**
     * Extract tenant_id from a resource.
     *
     * Looks for:
     * 1. Direct tenant_id attribute
     * 2. tenant() relationship
     *
     * @param Model $resource The resource to extract tenant_id from
     *
     * @return int|null The tenant_id, or null if not found
     */
    protected function getTenantIdFromResource(Model $resource): ?int
    {
        // Try direct attribute first
        if ($resource->getAttribute('tenant_id') !== null) {
            return (int) $resource->getAttribute('tenant_id');
        }

        // Try tenant relationship
        if (method_exists($resource, 'tenant')) {
            try {
                $tenant = $resource->tenant;

                if ($tenant instanceof Tenant) {
                    return $tenant->id;
                }
            } catch (\Exception) {
                // Relationship failed, continue
            }
        }

        return null;
    }
}
