<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TenantStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Tenant Model.
 *
 * Represents a tenant in the multi-tenancy system.
 * Global entity stored in public schema.
 *
 * @property int $id
 * @property string $name
 * @property string|null $slug
 * @property TenantStatus $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant accessible()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tenant whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Tenant extends Model
{
    /**
     * Use HasFactory to support model factories.
     *
     * @use HasFactory<\Database\Factories\TenantFactory>
     */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * Stored in global public schema.
     *
     * @var string
     */
    protected $table = 'tenants';

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'pgsql';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get the schema name for this tenant.
     *
     * Naming convention: tenant_{id}_{environment}
     *
     * @param string $environment Environment (dev, staging, prod)
     *
     * @return string Schema name
     */
    public function schemaName(string $environment = 'prod'): string
    {
        return sprintf('tenant_%d_%s', $this->id, $environment);
    }

    /**
     * Check if tenant is active and can be accessed.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === TenantStatus::ACTIVE;
    }

    /**
     * Check if tenant allows data operations.
     *
     * @return bool
     */
    public function allowsOperations(): bool
    {
        return $this->status->allowsOperations();
    }

    /**
     * Check if tenant is suspended.
     *
     * @return bool
     */
    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::SUSPENDED;
    }

    /**
     * Check if tenant is deleted (soft delete).
     *
     * @return bool
     */
    public function isDeleted(): bool
    {
        return $this->status === TenantStatus::DELETED;
    }

    /**
     * Activate tenant.
     *
     * Transitions from suspended or other state to active.
     * Allows data operations and user access.
     *
     * @return bool
     */
    public function activate(): bool
    {
        return $this->update(['status' => TenantStatus::ACTIVE]);
    }

    /**
     * Suspend tenant.
     *
     * Transitions to suspended state.
     * Prevents data operations but preserves schema.
     *
     * @return bool
     */
    public function suspend(): bool
    {
        return $this->update(['status' => TenantStatus::SUSPENDED]);
    }

    /**
     * Soft delete tenant.
     *
     * Transitions to deleted state.
     * Schema preserved for retention period, then can be archived.
     *
     * @return bool
     */
    public function softDelete(): bool
    {
        return $this->update(['status' => TenantStatus::DELETED]);
    }

    /**
     * Scope to active tenants.
     *
     * Only includes tenants with ACTIVE status.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereIn('status', TenantStatus::active());
    }

    /**
     * Scope to accessible tenants.
     *
     * Only includes tenants that allow user access.
     *
     * @param \Illuminate\Database\Eloquent\Builder<static> $query
     *
     * @return \Illuminate\Database\Eloquent\Builder<static>
     */
    public function scopeAccessible(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereIn('status', TenantStatus::accessible());
    }
}
