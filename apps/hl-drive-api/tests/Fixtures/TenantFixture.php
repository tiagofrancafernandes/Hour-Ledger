<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Enums\TenantStatus;
use App\Models\Tenant;

/**
 * TenantFixture
 *
 * Factory methods for creating test tenants with predefined configurations.
 * Simplifies creation of tenants in tests without worrying about defaults.
 *
 * Usage:
 *   $tenant = TenantFixture::createTenant(['name' => 'My Tenant']);
 *   $tenants = TenantFixture::createMultipleTenants(5);
 */
class TenantFixture
{
    /**
     * Create a single test tenant with optional attributes
     *
     * @param array<string, mixed> $attributes Override attributes
     *
     * @return Tenant The created tenant
     */
    public static function createTenant(array $attributes = []): Tenant
    {
        $defaults = [
            'name' => 'Test Tenant',
            'status' => TenantStatus::ACTIVE,
        ];

        $mergedAttributes = array_merge($defaults, $attributes);

        return Tenant::create($mergedAttributes);
    }

    /**
     * Create multiple test tenants with naming pattern
     *
     * Creates tenants named "Tenant 1", "Tenant 2", etc.
     *
     * @param int $count Number of tenants to create
     * @param array<string, mixed> $baseAttributes Attributes to apply to each tenant
     *
     * @return array<int, Tenant> Array of created tenants
     */
    public static function createMultipleTenants(int $count = 3, array $baseAttributes = []): array
    {
        $tenants = [];

        for ($i = 1; $i <= $count; ++$i) {
            $tenants[] = self::createTenant(
                array_merge(['name' => "Tenant {$i}"], $baseAttributes)
            );
        }

        return $tenants;
    }

    /**
     * Create an active tenant
     *
     * @param array<string, mixed> $attributes Override attributes
     *
     * @return Tenant
     */
    public static function createActiveTenant(array $attributes = []): Tenant
    {
        return self::createTenant(
            array_merge(['status' => TenantStatus::ACTIVE], $attributes)
        );
    }

    /**
     * Create a suspended tenant
     *
     * @param array<string, mixed> $attributes Override attributes
     *
     * @return Tenant
     */
    public static function createSuspendedTenant(array $attributes = []): Tenant
    {
        return self::createTenant(
            array_merge(['status' => TenantStatus::SUSPENDED], $attributes)
        );
    }

    /**
     * Create a deleted tenant
     *
     * @param array<string, mixed> $attributes Override attributes
     *
     * @return Tenant
     */
    public static function createDeletedTenant(array $attributes = []): Tenant
    {
        return self::createTenant(
            array_merge(['status' => TenantStatus::DELETED], $attributes)
        );
    }

    /**
     * Create a tenant with a specific slug
     *
     * @param string $slug The slug for the tenant
     * @param array<string, mixed> $attributes Override attributes
     *
     * @return Tenant
     */
    public static function createTenantWithSlug(string $slug, array $attributes = []): Tenant
    {
        return self::createTenant(
            array_merge(['slug' => $slug], $attributes)
        );
    }
}
