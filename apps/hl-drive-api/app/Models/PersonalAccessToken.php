<?php

declare(strict_types=1);

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * PersonalAccessToken Model.
 *
 * Extends Laravel Sanctum's PersonalAccessToken to add tenant scope support.
 *
 * When a token has tenant_id set:
 * - It can only be used to access resources in that specific tenant
 * - All requests must include matching X-Tenant-ID header
 * - Provides security boundary for tenant-specific operations
 *
 * When tenant_id is NULL:
 * - Token can access any tenant the user has access to
 * - Provides flexibility for admin/cross-tenant operations
 *
 * @property int $id
 * @property string $tokenable_type
 * @property int $tokenable_id
 * @property string $name
 * @property string $token (hashed)
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $tenant_id
 *
 * @mixin \Eloquent
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
        'tenant_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Check if this token is limited to a specific tenant.
     *
     * A token is tenant-limited when tenant_id is not null.
     * Such tokens can only be used for operations in that specific tenant.
     *
     * @return bool True if token has tenant_id, false if it's global
     */
    public function isLimitedToTenant(): bool
    {
        return $this->tenant_id !== null;
    }

    /**
     * Get the tenant ID this token is scoped to, if any.
     *
     * Returns null if token is global (can access any tenant).
     *
     * @return int|null The tenant_id if limited, null if global
     */
    public function getTenantId(): ?int
    {
        return $this->tenant_id;
    }

    /**
     * Check if this token can access the given tenant.
     *
     * Rules:
     * - If token is global (tenant_id = null): always allow
     * - If token is limited (tenant_id set): only allow if tenant_id matches
     *
     * @param int $tenantId The tenant to check access for
     *
     * @return bool True if token can access this tenant
     */
    public function canAccessTenant(int $tenantId): bool
    {
        // Global tokens can access any tenant
        if (!$this->isLimitedToTenant()) {
            return true;
        }

        // Limited tokens can only access their specific tenant
        return $this->tenant_id === $tenantId;
    }
}
