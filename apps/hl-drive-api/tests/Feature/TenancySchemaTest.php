<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tenancy Schema Tests.
 *
 * Tests for multi-tenancy schema functionality:
 * - Schema creation
 * - Schema isolation
 * - Data separation between tenants
 * - PostgreSQL function availability
 */
class TenancySchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that a tenant can be created with proper schema.
     */
    public function testTenantCanBeCreated(): void
    {
        // Arrange
        $tenantId = 1;
        $tenantName = 'Test Tenant';

        // Act
        $tenant = Tenant::create([
            'id' => $tenantId,
            'name' => $tenantName,
            'slug' => 'test-tenant',
            'status' => 'active',
        ]);

        // Assert
        $this->assertNotNull($tenant);
        $this->assertEquals($tenantId, $tenant->id);
        $this->assertEquals($tenantName, $tenant->name);
        $this->assertEquals('active', $tenant->status->value);
        $this->assertTrue($tenant->isActive());
    }

    /**
     * Test that tenant schema name is generated correctly.
     */
    public function testTenantSchemaNameGeneration(): void
    {
        // Arrange
        $tenant = Tenant::create([
            'id' => 42,
            'name' => 'Test Tenant',
            'slug' => 'test',
            'status' => 'active',
        ]);

        // Act & Assert
        $this->assertEquals('tenant_42_prod', $tenant->schemaName('prod'));
        $this->assertEquals('tenant_42_staging', $tenant->schemaName('staging'));
        $this->assertEquals('tenant_42_dev', $tenant->schemaName('dev'));
    }

    /**
     * Test that create_tenant_schema PostgreSQL function exists.
     */
    public function testCreateTenantSchemaFunctionExists(): void
    {
        // Act
        $functionExists = DB::selectOne(
            "SELECT EXISTS(
                SELECT 1 FROM pg_proc
                WHERE proname = 'create_tenant_schema'
                AND pronargs = 3
            ) as exists"
        );

        // Assert
        $this->assertTrue((bool) $functionExists->exists);
    }

    /**
     * Test that create_tenant_schema function can be called successfully.
     */
    public function testCreateTenantSchemaFunctionCreatesSchema(): void
    {
        // Arrange
        $tenantId = 99;
        $tenantName = 'Test Schema Tenant';
        $environment = 'dev';

        // Act
        $result = DB::selectOne(
            'SELECT * FROM create_tenant_schema(?, ?, ?)',
            [$tenantId, $tenantName, $environment]
        );

        // Assert
        $this->assertNotNull($result);
        $this->assertTrue((bool) $result->success);
        $this->assertEquals('tenant_99_dev', $result->schema_name);
        $this->assertStringContainsString('successfully', strtolower($result->message));

        // Verify schema was actually created
        $schemaExists = DB::selectOne(
            "SELECT EXISTS(
                SELECT 1 FROM information_schema.schemata
                WHERE schema_name = 'tenant_99_dev'
            ) as exists"
        );

        $this->assertTrue((bool) $schemaExists->exists);

        // Cleanup
        DB::statement('DROP SCHEMA tenant_99_dev CASCADE');
    }

    /**
     * Test that create_tenant_schema rejects duplicate schema.
     */
    public function testCreateTenantSchemaRejectsDuplicate(): void
    {
        // Arrange
        $tenantId = 100;
        $tenantName = 'Duplicate Test';

        // Create schema once
        DB::selectOne(
            'SELECT * FROM create_tenant_schema(?, ?, ?)',
            [$tenantId, $tenantName, 'dev']
        );

        // Act - Try to create same schema again
        $result = DB::selectOne(
            'SELECT * FROM create_tenant_schema(?, ?, ?)',
            [$tenantId, $tenantName, 'dev']
        );

        // Assert
        $this->assertNotNull($result);
        $this->assertFalse((bool) $result->success);
        $this->assertStringContainsString('already exists', strtolower($result->message));

        // Cleanup
        DB::statement('DROP SCHEMA tenant_100_dev CASCADE');
    }

    /**
     * Test that create_tenant_schema validates tenant_id.
     */
    public function testCreateTenantSchemaValidatesTenantId(): void
    {
        // Act - Call with invalid tenant_id (0)
        $result = DB::selectOne(
            'SELECT * FROM create_tenant_schema(?, ?, ?)',
            [0, 'Test', 'dev']
        );

        // Assert
        $this->assertNotNull($result);
        $this->assertFalse((bool) $result->success);
        $this->assertStringContainsString('invalid', strtolower($result->message));
    }

    /**
     * Test that create_tenant_schema validates tenant_name.
     */
    public function testCreateTenantSchemaValidatesTenantName(): void
    {
        // Act - Call with empty tenant_name
        $result = DB::selectOne(
            'SELECT * FROM create_tenant_schema(?, ?, ?)',
            [101, '', 'dev']
        );

        // Assert
        $this->assertNotNull($result);
        $this->assertFalse((bool) $result->success);
        $this->assertStringContainsString('cannot be empty', strtolower($result->message));
    }

    /**
     * Test that copy_table_structure function exists.
     */
    public function testCopyTableStructureFunctionExists(): void
    {
        // Act
        $functionExists = DB::selectOne(
            "SELECT EXISTS(
                SELECT 1 FROM pg_proc
                WHERE proname = 'copy_table_structure'
                AND pronargs = 3
            ) as exists"
        );

        // Assert
        $this->assertTrue((bool) $functionExists->exists);
    }

    /**
     * Test tenant model relationships and scopes.
     */
    public function testTenantActiveScope(): void
    {
        // Arrange
        Tenant::create([
            'id' => 1,
            'name' => 'Active Tenant',
            'status' => 'active',
        ]);

        Tenant::create([
            'id' => 2,
            'name' => 'Suspended Tenant',
            'status' => 'suspended',
        ]);

        // Act
        $activeTenants = Tenant::active()->get();

        // Assert
        $this->assertCount(1, $activeTenants);
        $this->assertEquals('Active Tenant', $activeTenants->first()->name);
    }

    /**
     * Test tenant accessible scope.
     */
    public function testTenantAccessibleScope(): void
    {
        // Arrange
        Tenant::create([
            'id' => 1,
            'name' => 'Accessible Tenant',
            'status' => 'active',
        ]);

        Tenant::create([
            'id' => 2,
            'name' => 'Deleted Tenant',
            'status' => 'deleted',
        ]);

        // Act
        $accessibleTenants = Tenant::accessible()->get();

        // Assert
        $this->assertCount(1, $accessibleTenants);
        $this->assertTrue($accessibleTenants->first()->allowsOperations());
    }

    /**
     * Test tenant status transitions.
     */
    public function testTenantStatusTransitions(): void
    {
        // Arrange
        $tenant = Tenant::create([
            'id' => 1,
            'name' => 'Status Test',
            'status' => 'active',
        ]);

        // Act & Assert - Active to Suspended
        $tenant->suspend();
        $this->assertTrue($tenant->isSuspended());
        $this->assertFalse($tenant->isActive());

        // Act & Assert - Suspended to Active
        $tenant->activate();
        $this->assertTrue($tenant->isActive());
        $this->assertFalse($tenant->isSuspended());

        // Act & Assert - Active to Deleted
        $tenant->softDelete();
        $this->assertTrue($tenant->isDeleted());
        $this->assertFalse($tenant->isActive());
    }

    /**
     * Test that slug is generated from name.
     */
    public function testTenantSlugUniqueness(): void
    {
        // Arrange
        Tenant::create([
            'id' => 1,
            'name' => 'First Tenant',
            'slug' => 'first-tenant',
            'status' => 'active',
        ]);

        // Act & Assert - Duplicate slug should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        Tenant::create([
            'id' => 2,
            'name' => 'Another Tenant',
            'slug' => 'first-tenant',
            'status' => 'active',
        ]);
    }

    /**
     * Test multiple tenants can coexist.
     */
    public function testMultipleTenantsCanCoexist(): void
    {
        // Arrange & Act
        $tenant1 = Tenant::create([
            'id' => 1,
            'name' => 'Tenant 1',
            'slug' => 'tenant-1',
            'status' => 'active',
        ]);

        $tenant2 = Tenant::create([
            'id' => 2,
            'name' => 'Tenant 2',
            'slug' => 'tenant-2',
            'status' => 'active',
        ]);

        // Assert
        $allTenants = Tenant::all();
        $this->assertCount(2, $allTenants);

        $this->assertNotNull(Tenant::find(1));
        $this->assertNotNull(Tenant::find(2));
        $this->assertEquals('Tenant 1', Tenant::find(1)->name);
        $this->assertEquals('Tenant 2', Tenant::find(2)->name);
    }

    /**
     * Test that tenant metadata can be stored.
     */
    public function testTenantMetadataStorage(): void
    {
        // Arrange
        $metadata = [
            'region' => 'us-east-1',
            'billing_email' => 'admin@example.com',
        ];

        // Act
        $tenant = Tenant::create([
            'id' => 1,
            'name' => 'Test Tenant',
            'slug' => 'test',
            'status' => 'active',
            'metadata' => json_encode($metadata),
        ]);

        // Assert
        $this->assertEquals($metadata, json_decode($tenant->metadata, true));
    }
}
