<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TenantStatus;
use App\Exceptions\TenantNotActive;
use App\Exceptions\TenantNotFound;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;

/**
 * TenantResolutionTest validates tenant resolution and middleware behavior.
 *
 * Tests cover:
 * - Tenant detection from headers, query parameters, and URL paths
 * - Tenant validation (exists, active, user authorized)
 * - TenantResolver singleton behavior
 * - Error handling and appropriate HTTP status codes
 */
class TenantResolutionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Active tenant for testing.
     *
     * @var Tenant
     */
    private Tenant $activeTenant;

    /**
     * Suspended tenant for testing.
     *
     * @var Tenant
     */
    private Tenant $suspendedTenant;

    /**
     * User for testing.
     *
     * @var User
     */
    private User $user;

    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->activeTenant = Tenant::factory()->create([
            'status' => TenantStatus::ACTIVE,
        ]);

        $this->suspendedTenant = Tenant::factory()->create([
            'status' => TenantStatus::SUSPENDED,
        ]);

        $this->user = User::factory()->create();
    }

    /**
     * Test middleware detects tenant from X-Tenant-ID header.
     *
     * @test
     */
    public function testMiddlewareDetectsTenantFromHeader(): void
    {
        $response = $this->withHeaders([
            'X-Tenant-ID' => $this->activeTenant->id,
        ])->get('/api/test-tenant-endpoint');

        // The middleware should set the tenant on the request
        $this->assertEquals(
            $this->activeTenant->id,
            $response->getOriginalRequest()?->attributes->get('tenant_id')
        );
    }

    /**
     * Test middleware detects tenant from query parameter.
     *
     * @test
     */
    public function testMiddlewareDetectsTenantFromQueryParameter(): void
    {
        $response = $this->get('/api/test-tenant-endpoint?tenant=' . $this->activeTenant->id);

        $this->assertEquals(
            $this->activeTenant->id,
            $response->getOriginalRequest()?->attributes->get('tenant_id')
        );
    }

    /**
     * Test middleware returns 403 when no tenant specified.
     *
     * @test
     */
    public function testMiddlewareReturns403WhenNoTenantSpecified(): void
    {
        $response = $this->get('/api/test-tenant-endpoint');

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->status());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('No tenant specified', $response->getContent());
    }

    /**
     * Test middleware returns 403 for invalid tenant ID.
     *
     * @test
     */
    public function testMiddlewareReturns403ForInvalidTenantId(): void
    {
        $response = $this->withHeaders([
            'X-Tenant-ID' => 99999,
        ])->get('/api/test-tenant-endpoint');

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->status());
        $this->assertStringContainsString('not found', $response->getContent());
    }

    /**
     * Test middleware returns 403 for suspended tenant.
     *
     * @test
     */
    public function testMiddlewareReturns403ForSuspendedTenant(): void
    {
        $response = $this->withHeaders([
            'X-Tenant-ID' => $this->suspendedTenant->id,
        ])->get('/api/test-tenant-endpoint');

        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->status());
        $this->assertStringContainsString('not active', $response->getContent());
    }

    /**
     * Test TenantResolver resolves schema correctly.
     *
     * @test
     */
    public function testTenantResolverResolvesSchemaCorrectly(): void
    {
        $resolver = app(TenantResolver::class);
        $resolver->setTenantId($this->activeTenant->id);

        $expectedSchema = sprintf(
            'tenant_%d_prod',
            $this->activeTenant->id
        );

        $this->assertEquals($expectedSchema, $resolver->getSchema());
    }

    /**
     * Test TenantResolver hasTenant returns true when tenant is set.
     *
     * @test
     */
    public function testTenantResolverHasTenantReturnsTrueWhenSet(): void
    {
        $resolver = app(TenantResolver::class);

        $this->assertFalse($resolver->hasTenant());

        $resolver->setTenantId($this->activeTenant->id);

        $this->assertTrue($resolver->hasTenant());
    }

    /**
     * Test TenantResolver throws exception for non-existent tenant.
     *
     * @test
     */
    public function testTenantResolverThrowsExceptionForNonExistentTenant(): void
    {
        $resolver = app(TenantResolver::class);

        $this->expectException(TenantNotFound::class);

        $resolver->setTenantId(99999);
    }

    /**
     * Test TenantResolver throws exception for inactive tenant.
     *
     * @test
     */
    public function testTenantResolverThrowsExceptionForInactiveTenant(): void
    {
        $resolver = app(TenantResolver::class);

        $this->expectException(TenantNotActive::class);

        $resolver->setTenantId($this->suspendedTenant->id);
    }

    /**
     * Test TenantResolver context is created correctly.
     *
     * @test
     */
    public function testTenantResolverContextIsCreatedCorrectly(): void
    {
        $resolver = app(TenantResolver::class);
        $resolver->setTenantId($this->activeTenant->id, $this->user->id);

        $context = $resolver->getContext();

        $this->assertEquals($this->activeTenant->id, $context->getTenantId());
        $this->assertEquals($this->user->id, $context->getUserId());
        $this->assertStringStartsWith('tenant_', $context->getSchema());
    }

    /**
     * Test TenantResolver clear removes context.
     *
     * @test
     */
    public function testTenantResolverClearRemovesContext(): void
    {
        $resolver = app(TenantResolver::class);
        $resolver->setTenantId($this->activeTenant->id);

        $this->assertTrue($resolver->hasTenant());

        $resolver->clear();

        $this->assertFalse($resolver->hasTenant());
        $this->assertNull($resolver->getTenantId());
    }

    /**
     * Test TenantResolver throws exception when getting schema without tenant.
     *
     * @test
     */
    public function testTenantResolverThrowsExceptionWhenGettingSchemaWithoutTenant(): void
    {
        $resolver = app(TenantResolver::class);

        $this->expectException(TenantNotFound::class);

        $resolver->getSchema();
    }

    /**
     * Test TenantResolver getTenantId returns correct ID.
     *
     * @test
     */
    public function testTenantResolverGetTenantIdReturnsCorrectId(): void
    {
        $resolver = app(TenantResolver::class);
        $resolver->setTenantId($this->activeTenant->id);

        $this->assertEquals($this->activeTenant->id, $resolver->getTenantId());
    }

    /**
     * Test TenantResolver getTenantId returns null before tenant is set.
     *
     * @test
     */
    public function testTenantResolverGetTenantIdReturnsNullBeforeTenantIsSet(): void
    {
        $resolver = app(TenantResolver::class);

        $this->assertNull($resolver->getTenantId());
    }

    /**
     * Test tenant context contains user ID.
     *
     * @test
     */
    public function testTenantContextContainsUserId(): void
    {
        $resolver = app(TenantResolver::class);
        $resolver->setTenantId($this->activeTenant->id, $this->user->id);

        $context = $resolver->getContext();

        $this->assertEquals($this->user->id, $context->getUserId());
    }

    /**
     * Test tenant context is serializable to array.
     *
     * @test
     */
    public function testTenantContextIsSerializableToArray(): void
    {
        $resolver = app(TenantResolver::class);
        $resolver->setTenantId($this->activeTenant->id, $this->user->id);

        $context = $resolver->getContext();
        $array = $context->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('tenantId', $array);
        $this->assertArrayHasKey('schema', $array);
        $this->assertArrayHasKey('userId', $array);
        $this->assertArrayHasKey('timestamp', $array);
    }

    /**
     * Test tenant context is JSON serializable.
     *
     * @test
     */
    public function testTenantContextIsJsonSerializable(): void
    {
        $resolver = app(TenantResolver::class);
        $resolver->setTenantId($this->activeTenant->id, $this->user->id);

        $context = $resolver->getContext();

        $json = json_encode($context);

        $this->assertIsString($json);
        $this->assertStringContainsString('tenantId', $json);
        $this->assertStringContainsString('schema', $json);
    }

    /**
     * Test middleware extracts tenant ID from URL path.
     *
     * @test
     */
    public function testMiddlewareExtractsTenantIdFromUrlPath(): void
    {
        $response = $this->get("/api/tenant/{$this->activeTenant->id}/data");

        $this->assertEquals(
            $this->activeTenant->id,
            $response->getOriginalRequest()?->attributes->get('tenant_id')
        );
    }

    /**
     * Test TenantResolver stores user ID correctly.
     *
     * @test
     */
    public function testTenantResolverStoresUserIdCorrectly(): void
    {
        $resolver = app(TenantResolver::class);
        $resolver->setTenantId($this->activeTenant->id, $this->user->id);

        $this->assertEquals($this->user->id, $resolver->getUserId());
    }

    /**
     * Test TenantResolver getUserId returns null when no user set.
     *
     * @test
     */
    public function testTenantResolverGetUserIdReturnsNullWhenNotSet(): void
    {
        $resolver = app(TenantResolver::class);
        $resolver->setTenantId($this->activeTenant->id);

        $this->assertNull($resolver->getUserId());
    }
}
