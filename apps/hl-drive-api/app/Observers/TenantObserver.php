<?php

declare(strict_types=1);

namespace App\Observers;

use App\Exceptions\UnauthorizedTenant;
use App\Services\TenantResolver;
use Illuminate\Database\Eloquent\Model;

/**
 * TenantObserver validates and enforces tenant_id constraints during model operations.
 *
 * This observer:
 * - Automatically sets tenant_id during creation if not provided
 * - Validates that the model's tenant_id matches the active tenant
 * - Prevents cross-tenant data modifications
 *
 * Registered automatically on models using the BelongsToTenant trait.
 */
class TenantObserver
{
    /**
     * Validate and set tenant_id before creating a model instance.
     *
     * If tenant_id is not provided and TenantResolver has an active tenant,
     * automatically set the tenant_id. If tenant_id is provided but does not
     * match the active tenant, throw UnauthorizedTenant exception.
     *
     * @param Model $model The model being created
     *
     * @throws UnauthorizedTenant If tenant_id mismatch occurs
     *
     * @return void
     */
    public function creating(Model $model): void
    {
        $tenantResolver = app(TenantResolver::class);
        $activeTenantId = $tenantResolver->getTenantId();

        $modelTenantId = $model->getAttribute('tenant_id');

        if ($modelTenantId !== null) {
            if ($modelTenantId !== $activeTenantId) {
                throw new UnauthorizedTenant($modelTenantId);
            }

            return;
        }

        if ($activeTenantId !== null) {
            $model->setAttribute('tenant_id', $activeTenantId);

            return;
        }
    }
}
