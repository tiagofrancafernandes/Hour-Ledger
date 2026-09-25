<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PackageTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $instructor;
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'web']);

        $this->tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $this->instructor = User::factory()->create();
        $this->instructor->assignRole('admin');
        $this->instructor->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'status' => 'active',
        ]);

        app(TenantResolver::class)->setTenantId($this->tenant->id);

        $this->headers = [
            'X-Tenant-ID' => (string) $this->tenant->id,
            'Accept' => 'application/json',
        ];
    }

    protected function tearDown(): void
    {
        app(TenantResolver::class)->clear();
        parent::tearDown();
    }

    public function testCanListPackages(): void
    {
        Package::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->getJson('/api/packages');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function testCanFilterPackagesByInstructor(): void
    {
        $otherInstructor = User::factory()->create();
        $otherInstructor->tenants()->attach($this->tenant->id, ['role' => 'instructor', 'status' => 'active']);

        Package::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'name' => 'Pacote Instrutor 1',
        ]);

        Package::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $otherInstructor->id,
            'name' => 'Pacote Instrutor 2',
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->getJson('/api/packages?instructor_id=' . $this->instructor->id);

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Pacote Instrutor 1');
    }

    public function testCanFilterPackagesByActiveStatus(): void
    {
        Package::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'active' => true,
        ]);

        Package::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'active' => false,
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->getJson('/api/packages?active=true');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function testCanCreatePackage(): void
    {
        $payload = [
            'name' => 'Pacote 10 Aulas Práticas',
            'description' => 'Pacote preparatório para exame de direção',
            'hours' => 10.00,
            'price' => 850.00,
            'currency_code' => 'BRL',
            'active' => true,
        ];

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->postJson('/api/packages', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Pacote 10 Aulas Práticas')
            ->assertJsonPath('data.hours', 10)
            ->assertJsonPath('data.price', 850)
            ->assertJsonPath('data.instructor_id', $this->instructor->id);

        $this->assertDatabaseHas('packages', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Pacote 10 Aulas Práticas',
        ]);
    }

    public function testValidateRequiredFieldsWhenCreatingPackage(): void
    {
        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->postJson('/api/packages', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'hours', 'price']);
    }

    public function testCanShowPackage(): void
    {
        $package = Package::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'name' => 'Pacote Baliza e Garagem',
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->getJson('/api/packages/' . $package->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $package->id)
            ->assertJsonPath('data.name', 'Pacote Baliza e Garagem');
    }

    public function testCanUpdatePackage(): void
    {
        $package = Package::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'name' => 'Nome Antigo',
            'price' => 500.00,
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->putJson('/api/packages/' . $package->id, [
                'name' => 'Nome Atualizado',
                'price' => 600.00,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Nome Atualizado')
            ->assertJsonPath('data.price', 600);

        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'name' => 'Nome Atualizado',
            'price' => 600.00,
        ]);
    }

    public function testCanDeletePackageSoftDelete(): void
    {
        $package = Package::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->deleteJson('/api/packages/' . $package->id);

        $response->assertOk()
            ->assertJson(['message' => 'Package deleted successfully.']);

        $this->assertSoftDeleted('packages', [
            'id' => $package->id,
        ]);
    }

    public function testUnauthenticatedUserCannotAccessPackages(): void
    {
        $response = $this->withHeaders($this->headers)
            ->getJson('/api/packages');

        $response->assertStatus(401);
    }
}
