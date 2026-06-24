<?php

declare(strict_types=1);

namespace App\Scopes;

use App\Services\TenantResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * TenantScope is a global scope that automatically filters queries by tenant_id.
 *
 * This scope ensures that every query on a model using the BelongsToTenant trait
 * is automatically restricted to the currently active tenant. This prevents
 * accidental cross-tenant data leakage.
 *
 * Behavior:
 * - If TenantResolver has an active tenant, queries are filtered by tenant_id
 * - If TenantResolver has NO active tenant, queries return an empty result set
 *
 * This "fail-closed" approach ensures maximum security.
 */
class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * Filters the query to only include records belonging to the active tenant.
     * If no tenant is active, returns a query that produces an empty result set.
     *
     * @param Builder $builder The query builder instance
     * @param Model $model The model being queried
     *
     * @return void
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantResolver = app(TenantResolver::class);

        $activeTenantId = $tenantResolver->getTenantId();

        if ($activeTenantId === null) {
            // No tenant context: return empty result set (fail-closed security)
            $builder->whereRaw('false');

            return;
        }

        // Apply filter for active tenant
        $builder->where('tenant_id', '=', $activeTenantId);
    }
}
