<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * PostgreSQL Schema Isolation Tests.
 *
 * Verifies and guarantees PostgreSQL schema-level multi-tenancy:
 * 1. Global central schema (`public`) stores shared entities (users, tenants).
 * 2. Dedicated PostgreSQL schemas for tenants (`tenant_{id}_{env}`).
 * 3. Dynamic search_path switching isolates queries between schemas.
 * 4. Data created within one tenant schema is strictly invisible to another.
 * 5. Dropping or altering a tenant schema does not affect central data.
 */
class PostgresSchemaIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant1;

    private Tenant $tenant2;

    private string $schema1;

    private string $schema2;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('This test suite requires a PostgreSQL connection.');
        }

        $this->tenant1 = Tenant::factory()->create(['name' => 'Acme Corp', 'status' => 'active']);
        $this->tenant2 = Tenant::factory()->create(['name' => 'Beta Industries', 'status' => 'active']);

        $this->schema1 = "tenant_{$this->tenant1->id}_test";
        $this->schema2 = "tenant_{$this->tenant2->id}_test";

        DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$this->schema1}\"");
        DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$this->schema2}\"");

        DB::statement("CREATE TABLE IF NOT EXISTS \"{$this->schema1}\".tenant_notes (
            id SERIAL PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            created_at TIMESTAMP DEFAULT NOW()
        )");

        DB::statement("CREATE TABLE IF NOT EXISTS \"{$this->schema2}\".tenant_notes (
            id SERIAL PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            created_at TIMESTAMP DEFAULT NOW()
        )");
    }

    protected function tearDown(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('SET search_path TO "public"');
            DB::statement("DROP SCHEMA IF EXISTS \"{$this->schema1}\" CASCADE");
            DB::statement("DROP SCHEMA IF EXISTS \"{$this->schema2}\" CASCADE");
        }

        app(TenantResolver::class)->clear();

        parent::tearDown();
    }

    /**
     * Test that setting tenant context switches PostgreSQL search_path.
     */
    public function testTenantResolverSwitchesSearchPath(): void
    {
        $resolver = app(TenantResolver::class);
        $resolver->setEnvironment('test');

        $resolver->setTenantId($this->tenant1->id);
        $resolver->applyPostgresSearchPath();

        $currentSearchPath = DB::selectOne('SHOW search_path');
        $searchPathValue = (string) ($currentSearchPath->search_path ?? '');

        $this->assertStringContainsString($this->schema1, $searchPathValue);
        $this->assertStringContainsString('public', $searchPathValue);

        $resolver->clear(resetSearchPath: true);

        $clearedSearchPath = DB::selectOne('SHOW search_path');
        $clearedPathValue = (string) ($clearedSearchPath->search_path ?? '');

        $this->assertStringNotContainsString($this->schema1, $clearedPathValue);
        $this->assertStringContainsString('public', $clearedPathValue);
    }

    /**
     * Test physical data isolation between tenant schemas.
     */
    public function testDataIsolationBetweenTenantSchemas(): void
    {
        // 1. Switch to Tenant 1 schema and insert note
        DB::statement("SET search_path TO \"{$this->schema1}\", \"public\"");
        DB::table('tenant_notes')->insert([
            'title' => 'Acme Secret Strategy',
            'content' => 'Top secret business plan for Acme Corp',
        ]);

        $acmeNotes = DB::table('tenant_notes')->get();
        $this->assertCount(1, $acmeNotes);
        $this->assertEquals('Acme Secret Strategy', $acmeNotes->first()->title);

        // 2. Switch to Tenant 2 schema and verify it sees 0 notes
        DB::statement("SET search_path TO \"{$this->schema2}\", \"public\"");
        $betaNotesEmpty = DB::table('tenant_notes')->get();
        $this->assertCount(0, $betaNotesEmpty);

        // 3. Insert note in Tenant 2 schema
        DB::table('tenant_notes')->insert([
            'title' => 'Beta Product Roadmap',
            'content' => 'Beta product development milestones',
        ]);

        $betaNotes = DB::table('tenant_notes')->get();
        $this->assertCount(1, $betaNotes);
        $this->assertEquals('Beta Product Roadmap', $betaNotes->first()->title);

        // 4. Switch back to Tenant 1 and confirm Tenant 2's note is NOT visible
        DB::statement("SET search_path TO \"{$this->schema1}\", \"public\"");
        $acmeNotesCheck = DB::table('tenant_notes')->get();
        $this->assertCount(1, $acmeNotesCheck);
        $this->assertEquals('Acme Secret Strategy', $acmeNotesCheck->first()->title);
    }

    /**
     * Test that global central tables (public schema) are accessible from any tenant schema.
     */
    public function testGlobalCentralTablesAreAccessibleFromTenantSchema(): void
    {
        User::factory()->create(['email' => 'global_user@example.com']);

        // Set search_path to tenant schema with fallback to public
        DB::statement("SET search_path TO \"{$this->schema1}\", \"public\"");

        // Querying global central tables should work via search_path resolution
        $usersCount = DB::table('users')->where('email', 'global_user@example.com')->count();
        $this->assertEquals(1, $usersCount);

        $tenantsCount = DB::table('tenants')->count();
        $this->assertGreaterThanOrEqual(2, $tenantsCount);
    }

    /**
     * Test dropping a tenant schema deletes tenant data while preserving global data.
     */
    public function testDroppingTenantSchemaPreservesGlobalData(): void
    {
        DB::statement("SET search_path TO \"{$this->schema1}\", \"public\"");
        DB::table('tenant_notes')->insert(['title' => 'Disposable Note']);

        // Drop tenant 1 schema
        DB::statement('SET search_path TO "public"');
        DB::statement("DROP SCHEMA \"{$this->schema1}\" CASCADE");

        // Verify public tables (tenants and users) are intact
        $this->assertNotNull(Tenant::find($this->tenant1->id));
        $this->assertNotNull(Tenant::find($this->tenant2->id));

        // Recreate schema 1 so tearDown does not error
        DB::statement("CREATE SCHEMA IF NOT EXISTS \"{$this->schema1}\"");
    }
}
