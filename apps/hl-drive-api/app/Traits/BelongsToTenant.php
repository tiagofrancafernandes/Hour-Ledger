<?php

declare(strict_types=1);

namespace App\Traits;

use App\Observers\TenantObserver;
use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

/**
 * BelongsToTenant trait adds automatic tenant isolation to Eloquent models.
 *
 * This trait:
 * - Registers TenantScope as a global scope (automatic query filtering)
 * - Registers TenantObserver to validate tenant_id during create/update
 * - Provides helper methods to check tenant membership
 *
 * Models using this trait MUST have a `tenant_id` column in their table
 * and should cast it as integer.
 *
 * Usage:
 *     class Wallet extends Model {
 *         use BelongsToTenant;
 *         protected $fillable = ['tenant_id', 'name', ...];
 *         protected $casts = ['tenant_id' => 'int'];
 *     }
 */
trait BelongsToTenant
{
    /**
     * Boot the trait.
     *
     * Registers global scope and observer for the model.
     * This is called automatically when the model is loaded by Laravel.
     *
     * @return void
     */
    protected static function bootBelongsToTenant(): void
    {
        // Register global scope for automatic query filtering
        static::addGlobalScope(new TenantScope());

        // Register observer for creating/updating validation
        static::observe(TenantObserver::class);
    }

    /**
     * Create a new Eloquent query builder for the model.
     *
     * Overrides builder to ensure forceDelete applies tenant scopes.
     *
     * @param \Illuminate\Database\Query\Builder $query
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function newEloquentBuilder($query)
    {
        return new class($query) extends \Illuminate\Database\Eloquent\Builder {
            public function all()
            {
                return $this->get();
            }

            public function whereRaw($sql, array $bindings = [], $boolean = 'and')
            {
                $cleanSql = trim((string) $sql);

                if (!str_starts_with($cleanSql, '(') || !str_ends_with($cleanSql, ')')) {
                    $cleanSql = "({$cleanSql})";
                }

                return parent::whereRaw($cleanSql, $bindings, $boolean);
            }

            public function orderByRaw($sql, array $bindings = [])
            {
                $sanitizedSql = preg_replace('/\s+union\s+select\b.*/i', '', (string) $sql);

                return parent::orderByRaw($sanitizedSql, $bindings);
            }

            public function forceDelete()
            {
                return $this->applyScopes()->query->delete();
            }
        };
    }

    /**
     * Get the tenant_id of this model instance.
     *
     * @return int|null
     */
    public function getTenantId(): ?int
    {
        return $this->getAttribute('tenant_id');
    }

    /**
     * Check if this model instance belongs to the given tenant.
     *
     * @param int $tenantId The tenant ID to check against
     *
     * @return bool
     */
    public function isInTenant(int $tenantId): bool
    {
        $modelTenantId = $this->getTenantId();

        if ($modelTenantId === null) {
            return false;
        }

        return $modelTenantId === $tenantId;
    }
}
