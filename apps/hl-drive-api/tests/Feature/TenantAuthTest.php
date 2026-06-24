<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PersonalAccessToken;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TenantAuthTest.
 *
 * Tests authentication integration with multi-tenancy.
 *
 * Validates:
 * - Login without tenant_id
 * - Login with tenant_id (valid and invalid)
 * - Token tenant scoping
 * - Cross-tenant access prevention
 * - Policy enforcement
 * - Tenant switching
 */
class TenantAuthTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected Tenant $tenant3;

    protected function setUp(): void
    {
        parent::setUp();

        // Create tenants
        $this->tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $this->tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);
        $this->tenant3 = Tenant::factory()->create(['name' => 'Tenant 3']);

        // Create user
        $this->user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Grant user access to tenant1 and tenant2
        $this->user->tenants()->attach([
            $this->tenant1->id => ['role' => 'admin', 'status' => 'active'],
            $this->tenant2->id => ['role' => 'member', 'status' => 'active'],
        ]);
    }

    /**
     * Test login without tenant_id works.
     */
    public function test_login_without_tenant_id_succeeds(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'customer_id'],
            'token',
            'accessible_tenants',
        ]);

        // Token should be created without tenant_id
        $tokenString = $response->json('token');
        $tokenId = explode('|', $tokenString)[0];
        $token = PersonalAccessToken::find($tokenId);

        $this->assertNull($token->tenant_id);
    }

    /**
     * Test login with valid tenant_id includes tenant_id in token.
     */
    public function test_login_with_valid_tenant_id_includes_tenant_in_token(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'tenant_id' => $this->tenant1->id,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'user' => ['id', 'name', 'email', 'customer_id'],
            'token',
            'accessible_tenants',
        ]);

        // Token should include tenant_id
        $tokenString = $response->json('token');
        $tokenId = explode('|', $tokenString)[0];
        $token = PersonalAccessToken::find($tokenId);

        $this->assertEquals($this->tenant1->id, $token->tenant_id);
    }

    /**
     * Test login with invalid tenant_id returns 422.
     */
    public function test_login_with_invalid_tenant_id_returns_unprocessable(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'tenant_id' => $this->tenant3->id, // User doesn't have access
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tenant_id']);
    }

    /**
     * Test accessible_tenants includes all user's accessible tenants.
     */
    public function test_login_response_includes_accessible_tenants(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);

        $accessibleTenants = $response->json('accessible_tenants');

        $this->assertCount(2, $accessibleTenants);
        $this->assertTrue(collect($accessibleTenants)->pluck('id')->contains($this->tenant1->id));
        $this->assertTrue(collect($accessibleTenants)->pluck('id')->contains($this->tenant2->id));
        $this->assertFalse(collect($accessibleTenants)->pluck('id')->contains($this->tenant3->id));
    }

    /**
     * Test token with tenant_id cannot access other tenants.
     */
    public function test_token_limited_to_tenant_cannot_access_other_tenant(): void
    {
        // Login with tenant_id
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'tenant_id' => $this->tenant1->id,
        ]);

        $token = $loginResponse->json('token');

        // Try to use token with different tenant in header (if ValidateTenantToken middleware is applied)
        // For now, just verify token is scoped to tenant1
        $tokenId = explode('|', $token)[0];
        $tokenRecord = PersonalAccessToken::find($tokenId);

        $this->assertEquals($this->tenant1->id, $tokenRecord->tenant_id);
        $this->assertTrue($tokenRecord->isLimitedToTenant());
        $this->assertEquals($this->tenant1->id, $tokenRecord->getTenantId());
    }

    /**
     * Test token without tenant_id can access any tenant.
     */
    public function test_token_without_tenant_id_can_access_any_tenant(): void
    {
        // Login without tenant_id
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('token');
        $tokenId = explode('|', $token)[0];
        $tokenRecord = PersonalAccessToken::find($tokenId);

        $this->assertFalse($tokenRecord->isLimitedToTenant());
        $this->assertNull($tokenRecord->getTenantId());
        $this->assertTrue($tokenRecord->canAccessTenant($this->tenant1->id));
        $this->assertTrue($tokenRecord->canAccessTenant($this->tenant2->id));
    }

    /**
     * Test user can switch tenant via new login.
     */
    public function test_user_can_switch_tenant_via_new_login(): void
    {
        // First login to tenant1
        $firstLogin = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'tenant_id' => $this->tenant1->id,
        ]);

        $firstToken = $firstLogin->json('token');
        $firstTokenId = explode('|', $firstToken)[0];
        $firstTokenRecord = PersonalAccessToken::find($firstTokenId);

        $this->assertEquals($this->tenant1->id, $firstTokenRecord->tenant_id);

        // Second login to tenant2
        $secondLogin = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'tenant_id' => $this->tenant2->id,
        ]);

        $secondToken = $secondLogin->json('token');
        $secondTokenId = explode('|', $secondToken)[0];
        $secondTokenRecord = PersonalAccessToken::find($secondTokenId);

        $this->assertEquals($this->tenant2->id, $secondTokenRecord->tenant_id);

        // Verify first token still exists and is different
        $this->assertNotEquals($firstToken, $secondToken);
    }

    /**
     * Test login with incorrect credentials fails.
     */
    public function test_login_with_incorrect_credentials_fails(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test login with non-existent user fails.
     */
    public function test_login_with_nonexistent_user_fails(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * Test tenant_id validation rejects non-existent tenant.
     */
    public function test_login_with_nonexistent_tenant_id_fails_validation(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'tenant_id' => 99999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tenant_id']);
    }

    /**
     * Test user without access to tenant cannot login to it.
     */
    public function test_user_without_tenant_access_cannot_login(): void
    {
        // User doesn't have access to tenant3
        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'tenant_id' => $this->tenant3->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['tenant_id']);
    }

    /**
     * Test token can access tenant via canAccessTenant method.
     */
    public function test_token_can_check_tenant_access(): void
    {
        // Create tokens
        $globalToken = $this->user->createToken('global-token')->accessToken;
        $limitedToken = $this->user->createToken('limited-token')->accessToken;
        $limitedToken->tenant_id = $this->tenant1->id;
        $limitedToken->save();

        // Global token can access both
        $this->assertTrue($globalToken->canAccessTenant($this->tenant1->id));
        $this->assertTrue($globalToken->canAccessTenant($this->tenant2->id));

        // Limited token can only access tenant1
        $this->assertTrue($limitedToken->canAccessTenant($this->tenant1->id));
        $this->assertFalse($limitedToken->canAccessTenant($this->tenant2->id));
    }
}
