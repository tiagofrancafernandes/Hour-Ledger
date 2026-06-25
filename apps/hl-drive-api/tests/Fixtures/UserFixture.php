<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use App\Models\Tenant;
use App\Models\User;

/**
 * UserFixture
 *
 * Factory methods for creating test users and assigning them to tenants.
 * Handles tenant access setup automatically.
 *
 * Usage:
 *   $user = UserFixture::createUserForTenant($tenant);
 *   $users = UserFixture::createMultipleUsersForTenant($tenant, 5);
 */
class UserFixture
{
    /**
     * Create a user and assign them to a tenant with a specific role
     *
     * @param Tenant $tenant The tenant to assign the user to
     * @param array<string, mixed> $attributes Override attributes
     * @param string $role The role to assign (admin, member, viewer)
     * @param string $status The access status (active, suspended, revoked)
     *
     * @return User The created user
     */
    public static function createUserForTenant(
        Tenant $tenant,
        array $attributes = [],
        string $role = 'member',
        string $status = 'active'
    ): User {
        $defaults = [
            'name' => 'Test User',
            'email' => 'user' . random_int(1000, 9999) . '@test.com',
            'password' => bcrypt('password123'),
        ];

        $mergedAttributes = array_merge($defaults, $attributes);
        $user = User::create($mergedAttributes);

        // Assign user to tenant
        $user->tenants()->attach(
            $tenant->id,
            ['role' => $role, 'status' => $status]
        );

        return $user;
    }

    /**
     * Create multiple users for a tenant
     *
     * @param Tenant $tenant The tenant to assign users to
     * @param int $count Number of users to create
     * @param string $role The role to assign
     * @param string $status The access status
     *
     * @return array<int, User> Array of created users
     */
    public static function createMultipleUsersForTenant(
        Tenant $tenant,
        int $count = 3,
        string $role = 'member',
        string $status = 'active'
    ): array {
        $users = [];

        for ($i = 1; $i <= $count; ++$i) {
            $users[] = self::createUserForTenant(
                $tenant,
                ['name' => "Test User {$i}"],
                $role,
                $status
            );
        }

        return $users;
    }

    /**
     * Create an admin user for a tenant
     *
     * @param Tenant $tenant The tenant to assign the user to
     * @param array<string, mixed> $attributes Override attributes
     *
     * @return User
     */
    public static function createAdminForTenant(Tenant $tenant, array $attributes = []): User
    {
        return self::createUserForTenant($tenant, $attributes, 'admin', 'active');
    }

    /**
     * Create a member user for a tenant
     *
     * @param Tenant $tenant The tenant to assign the user to
     * @param array<string, mixed> $attributes Override attributes
     *
     * @return User
     */
    public static function createMemberForTenant(Tenant $tenant, array $attributes = []): User
    {
        return self::createUserForTenant($tenant, $attributes, 'member', 'active');
    }

    /**
     * Create a viewer user for a tenant
     *
     * @param Tenant $tenant The tenant to assign the user to
     * @param array<string, mixed> $attributes Override attributes
     *
     * @return User
     */
    public static function createViewerForTenant(Tenant $tenant, array $attributes = []): User
    {
        return self::createUserForTenant($tenant, $attributes, 'viewer', 'active');
    }

    /**
     * Create a user with suspended access to a tenant
     *
     * @param Tenant $tenant The tenant to assign the user to
     * @param array<string, mixed> $attributes Override attributes
     *
     * @return User
     */
    public static function createSuspendedUserForTenant(Tenant $tenant, array $attributes = []): User
    {
        return self::createUserForTenant($tenant, $attributes, 'member', 'suspended');
    }

    /**
     * Create a user with revoked access to a tenant
     *
     * @param Tenant $tenant The tenant to assign the user to
     * @param array<string, mixed> $attributes Override attributes
     *
     * @return User
     */
    public static function createRevokedUserForTenant(Tenant $tenant, array $attributes = []): User
    {
        return self::createUserForTenant($tenant, $attributes, 'member', 'revoked');
    }

    /**
     * Create a user with access to multiple tenants
     *
     * @param array<int, Tenant> $tenants Array of tenants to assign to
     * @param array<string, mixed> $attributes Override attributes
     * @param string $role Role for all tenant assignments
     *
     * @return User
     */
    public static function createUserForMultipleTenants(
        array $tenants,
        array $attributes = [],
        string $role = 'member'
    ): User {
        $defaults = [
            'name' => 'Multi-Tenant User',
            'email' => 'multiuser' . random_int(1000, 9999) . '@test.com',
            'password' => bcrypt('password123'),
        ];

        $mergedAttributes = array_merge($defaults, $attributes);
        $user = User::create($mergedAttributes);

        // Assign user to all tenants
        foreach ($tenants as $tenant) {
            $user->tenants()->attach(
                $tenant->id,
                ['role' => $role, 'status' => 'active']
            );
        }

        return $user;
    }
}
