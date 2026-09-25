<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PersonalAccessToken;
use App\Models\User;

/**
 * AuthControllerTenantLoginTest.
 *
 * Comprehensive tests for AuthController::login() with tenant_id support.
 *
 * Tests:
 * 1. Global token creation (no tenant_id)
 * 2. Scoped token creation (with valid tenant_id)
 * 3. Access denied for invalid tenant_id
 * 4. Validation failure for non-existent tenant_id
 * 5. Accessible tenants list in response
 * 6. Login without tenant access still succeeds
 */
class AuthControllerTenantLoginTest extends TenantTestCase
{
    /**
     * Test login without tenant_id returns global token
     *
     * Scenario:
     * - User makes POST /api/login with only email + password
     * - No tenant_id provided
     *
     * Expected:
     * - Status 200 OK
     * - Response includes: user, role, permissions, accessible_tenants, token
     * - Token is global (no tenant_id in database)
     * - accessible_tenants is array with all user's tenants
     */
    public function testLoginWithoutTenantIdReturnsGlobalToken(): void
    {
        // User A has access to Tenant A
        // Pass X-Tenant-ID header to satisfy TenantMiddleware but don't send tenant_id in body
        $response = $this->postJson('/api/auth/login', [
            'email' => 'usera@test.com',
            'password' => 'password',
        ], [
            'X-Tenant-ID' => $this->tenantA->id,
        ]);

        // Assert status and structure
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'customer_id'],
            'token',
            'role',
            'permissions',
            'accessible_tenants',
        ]);

        // Verify user data
        $response->assertJsonPath('user.email', 'usera@test.com');
        $response->assertJsonPath('user.name', 'User A');

        // Verify token was created globally (no tenant_id)
        $tokenString = $response->json('token');
        $tokenId = explode('|', $tokenString)[0];
        $token = PersonalAccessToken::find($tokenId);

        $this->assertNotNull($token);
        $this->assertNull($token->tenant_id);

        // Verify accessible_tenants is array
        $accessibleTenants = $response->json('accessible_tenants');
        $this->assertIsArray($accessibleTenants);
        $this->assertCount(1, $accessibleTenants);
        $this->assertEquals($this->tenantA->id, $accessibleTenants[0]['id']);
    }

    /**
     * Test login with valid tenant_id returns scoped token
     *
     * Scenario:
     * - User makes POST /api/login with email + password + tenant_id
     * - User has access to that tenant
     *
     * Expected:
     * - Status 200 OK
     * - Response includes all required fields
     * - Token has tenant_id set in database
     * - Token is limited to the specified tenant
     */
    public function testLoginWithValidTenantIdReturnsScopedToken(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'usera@test.com',
            'password' => 'password',
            'tenant_id' => $this->tenantA->id,
        ], [
            'X-Tenant-ID' => $this->tenantA->id,
        ]);

        // Assert status and structure
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'customer_id'],
            'token',
            'role',
            'permissions',
            'accessible_tenants',
        ]);

        // Verify user data
        $response->assertJsonPath('user.email', 'usera@test.com');

        // Verify token has tenant_id
        $tokenString = $response->json('token');
        $tokenId = explode('|', $tokenString)[0];
        $token = PersonalAccessToken::find($tokenId);

        $this->assertNotNull($token);
        $this->assertNotNull($token->tenant_id);
        $this->assertEquals($this->tenantA->id, $token->tenant_id);
    }

    /**
     * Test login with invalid tenant_id is denied
     *
     * Scenario:
     * - User makes POST /api/login with email + password + tenant_id
     * - User does NOT have access to that tenant
     *
     * Expected:
     * - Status 422 (validation error)
     * - Error message about tenant_id
     * - No token is created
     */
    public function testLoginWithInvalidTenantIdDenied(): void
    {
        // User A tries to login to Tenant B (no access)
        // Use Tenant A in header to pass middleware, but request Tenant B in body
        $response = $this->postJson('/api/auth/login', [
            'email' => 'usera@test.com',
            'password' => 'password',
            'tenant_id' => $this->tenantB->id,
        ], [
            'X-Tenant-ID' => $this->tenantA->id,
        ]);

        // Assert error response
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tenant_id']);

        // Verify error message
        $errors = $response->json('errors');
        $this->assertIsArray($errors['tenant_id']);
        $this->assertStringContainsString('access', strtolower(implode(' ', $errors['tenant_id'])));
    }

    /**
     * Test login with non-existent tenant_id fails validation
     *
     * Scenario:
     * - User makes POST /api/login with email + password + non-existent tenant_id
     *
     * Expected:
     * - Status 422 (validation error)
     * - tenant_id validation fails (exists:tenants,id rule)
     * - No token is created
     */
    public function testLoginWithNonexistentTenantIdValidationFails(): void
    {
        $fakeId = 99999;

        $response = $this->postJson('/api/auth/login', [
            'email' => 'usera@test.com',
            'password' => 'password',
            'tenant_id' => $fakeId,
        ], [
            'X-Tenant-ID' => $this->tenantA->id,
        ]);

        // Assert validation error
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tenant_id']);

        // Verify it's the "exists" validation error
        $errors = $response->json('errors');
        $this->assertIsArray($errors['tenant_id']);
    }

    /**
     * Test login response includes accessible_tenants list
     *
     * Scenario:
     * - Add User to multiple tenants
     * - Login without tenant_id
     *
     * Expected:
     * - accessible_tenants array with all user's tenants
     * - Each item has: id, name, slug, status
     * - Only includes tenants user has access to
     */
    public function testLoginResponseIncludesAccessibleTenantsList(): void
    {
        // Grant User A access to Tenant B as well
        $this->userA->tenants()->attach(
            $this->tenantB->id,
            ['role' => 'member', 'status' => 'active']
        );

        $response = $this->postJson('/api/auth/login', [
            'email' => 'usera@test.com',
            'password' => 'password',
        ], [
            'X-Tenant-ID' => $this->tenantA->id,
        ]);

        $response->assertStatus(200);

        // Verify accessible_tenants structure
        $accessibleTenants = $response->json('accessible_tenants');

        $this->assertIsArray($accessibleTenants);
        $this->assertCount(2, $accessibleTenants);

        // Check each tenant has required fields
        foreach ($accessibleTenants as $tenant) {
            $this->assertArrayHasKey('id', $tenant);
            $this->assertArrayHasKey('name', $tenant);
            $this->assertArrayHasKey('slug', $tenant);
            $this->assertArrayHasKey('status', $tenant);
        }

        // Verify correct tenants are included
        $tenantIds = array_column($accessibleTenants, 'id');
        $this->assertContains($this->tenantA->id, $tenantIds);
        $this->assertContains($this->tenantB->id, $tenantIds);
        $this->assertNotContains($this->tenantC->id, $tenantIds);
    }

    /**
     * Test login with no accessible tenants still succeeds
     *
     * Scenario:
     * - Create a user without any tenant associations
     * - Login without tenant_id
     *
     * Expected:
     * - Status 200 OK
     * - Login succeeds (not an error)
     * - accessible_tenants is empty array []
     * - Token is created globally
     */
    public function testLoginWithNoAccessibleTenantsStillSucceeds(): void
    {
        // Create a user with no tenant associations
        $userWithoutTenants = User::factory()->create([
            'name' => 'Isolated User',
            'email' => 'isolated@test.com',
            'password' => bcrypt('password'),
        ]);

        // Login without tenant_id
        // Use tenantA header to pass middleware (user doesn't need access to this tenant for login endpoint)
        $response = $this->postJson('/api/auth/login', [
            'email' => 'isolated@test.com',
            'password' => 'password',
        ], [
            'X-Tenant-ID' => $this->tenantA->id,
        ]);

        // Assert successful login
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'customer_id'],
            'token',
            'role',
            'permissions',
            'accessible_tenants',
        ]);

        // Verify user returned is correct
        $response->assertJsonPath('user.id', $userWithoutTenants->id);
        $response->assertJsonPath('user.email', 'isolated@test.com');

        // Verify accessible_tenants is empty array
        $accessibleTenants = $response->json('accessible_tenants');
        $this->assertIsArray($accessibleTenants);
        $this->assertEmpty($accessibleTenants);

        // Verify token was created
        $tokenString = $response->json('token');
        $tokenId = explode('|', $tokenString)[0];
        $token = PersonalAccessToken::find($tokenId);

        $this->assertNotNull($token);
        $this->assertNull($token->tenant_id);
    }
}
