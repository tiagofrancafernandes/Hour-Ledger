<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Client;
use App\Models\Lesson;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Wallet;
use App\Services\BalanceCalculatorService;
use App\Services\LedgerService;
use App\Services\TenantResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LessonTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $instructor;
    private User $student;
    private Client $client;
    private Wallet $wallet;
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $this->tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $this->instructor = User::factory()->create();
        $this->instructor->assignRole('admin');
        $this->instructor->tenants()->attach($this->tenant->id, [
            'role' => 'admin',
            'status' => 'active',
        ]);

        app(TenantResolver::class)->setTenantId($this->tenant->id, $this->instructor->id);

        $this->client = Client::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Aluno Silva',
        ]);

        $this->wallet = Wallet::factory()->create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'currency_code' => 'BRL',
            'credit_purchase_allowed' => true,
        ]);

        $this->student = User::factory()->create([
            'customer_id' => $this->client->id,
        ]);
        $this->student->assignRole('customer');
        $this->student->tenants()->attach($this->tenant->id, [
            'role' => 'customer',
            'status' => 'active',
        ]);

        // Add 10 initial hours to student wallet
        $ledgerService = app(LedgerService::class);
        $ledgerService->addCredit($this->wallet, [
            'hours' => 10.00,
            'title' => 'Carga inicial de pacote',
            'reference_date' => now()->toIso8601String(),
        ]);

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

    public function testCanListLessons(): void
    {
        Lesson::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'wallet_id' => $this->wallet->id,
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->getJson('/api/lessons');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function testCanFilterLessonsByStudent(): void
    {
        $otherStudent = User::factory()->create();

        Lesson::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'wallet_id' => $this->wallet->id,
        ]);

        Lesson::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $otherStudent->id,
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->getJson('/api/students/' . $this->student->id . '/lessons');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function testCanFilterLessonsByInstructor(): void
    {
        $otherInstructor = User::factory()->create();

        Lesson::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
        ]);

        Lesson::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $otherInstructor->id,
            'student_id' => $this->student->id,
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->getJson('/api/instructors/' . $this->instructor->id . '/lessons');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function testCanScheduleLesson(): void
    {
        $scheduledTime = now()->addDays(3)->setHour(10)->setMinute(0)->setSecond(0);

        $payload = [
            'student_id' => $this->student->id,
            'wallet_id' => $this->wallet->id,
            'scheduled_at' => $scheduledTime->toIso8601String(),
            'duration_minutes' => 50,
            'notes' => 'Primeira aula de baliza',
        ];

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->postJson('/api/lessons', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.student_id', $this->student->id)
            ->assertJsonPath('data.duration_minutes', 50)
            ->assertJsonPath('data.notes', 'Primeira aula de baliza');

        $this->assertDatabaseHas('lessons', [
            'tenant_id' => $this->tenant->id,
            'student_id' => $this->student->id,
            'status' => 'scheduled',
        ]);
    }

    public function testValidatesRequiredFieldsWhenSchedulingLesson(): void
    {
        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->postJson('/api/lessons', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['student_id', 'scheduled_at']);
    }

    public function testCanShowLesson(): void
    {
        $lesson = Lesson::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'wallet_id' => $this->wallet->id,
            'notes' => 'Instrução em ladeira',
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->getJson('/api/lessons/' . $lesson->id);

        $response->assertOk()
            ->assertJsonPath('data.id', $lesson->id)
            ->assertJsonPath('data.notes', 'Instrução em ladeira');
    }

    public function testCanUpdateScheduledLesson(): void
    {
        $lesson = Lesson::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'duration_minutes' => 50,
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->putJson('/api/lessons/' . $lesson->id, [
                'duration_minutes' => 100,
                'notes' => 'Aula estendida',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.duration_minutes', 100)
            ->assertJsonPath('data.notes', 'Aula estendida');

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'duration_minutes' => 100,
        ]);
    }

    public function testCannotUpdateCompletedLesson(): void
    {
        $lesson = Lesson::factory()->completed()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->putJson('/api/lessons/' . $lesson->id, [
                'duration_minutes' => 100,
            ]);

        $response->assertStatus(422);
    }

    public function testCanCompleteLessonAndAutoDebitWalletViaLedger(): void
    {
        $lesson = Lesson::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'wallet_id' => $this->wallet->id,
            'duration_minutes' => 50,
        ]);

        $balanceBefore = app(BalanceCalculatorService::class)->getWalletBalance($this->wallet);
        $this->assertEquals(10.0, (float) $balanceBefore);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->postJson('/api/lessons/' . $lesson->id . '/complete', [
                'hours_consumed' => 1.00,
                'notes' => 'Aula finalizada, excelente desempenho.',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.notes', 'Aula finalizada, excelente desempenho.');

        $this->assertEquals(1.00, (float) $response->json('data.hours_consumed'));
        $this->assertNotNull($response->json('data.ledger_entry_id'));

        // Check wallet balance was deducted by 1 hour
        $balanceAfter = app(BalanceCalculatorService::class)->getWalletBalance($this->wallet);
        $this->assertEquals(9.0, (float) $balanceAfter);

        // Check ledger entry exists
        $this->assertDatabaseHas('ledger_entries', [
            'id' => $response->json('data.ledger_entry_id'),
            'wallet_id' => $this->wallet->id,
            'hours' => -1.00,
        ]);
    }

    public function testCanCancelLesson(): void
    {
        $lesson = Lesson::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->postJson('/api/lessons/' . $lesson->id . '/cancel');

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('lessons', [
            'id' => $lesson->id,
            'status' => 'cancelled',
        ]);
    }

    public function testCanDeleteLessonSoftDelete(): void
    {
        $lesson = Lesson::factory()->create([
            'tenant_id' => $this->tenant->id,
            'instructor_id' => $this->instructor->id,
            'student_id' => $this->student->id,
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->deleteJson('/api/lessons/' . $lesson->id);

        $response->assertOk()
            ->assertJson(['message' => 'Lesson deleted successfully.']);

        $this->assertSoftDeleted('lessons', [
            'id' => $lesson->id,
        ]);
    }

    public function testUnauthenticatedUserCannotAccessLessons(): void
    {
        $response = $this->withHeaders($this->headers)
            ->getJson('/api/lessons');

        $response->assertStatus(401);
    }
}
