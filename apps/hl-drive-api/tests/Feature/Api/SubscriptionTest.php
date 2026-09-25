<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Services\TenantResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $instructor;

    private User $admin;

    private Plan $plan;

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

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->plan = Plan::firstOrCreate(
            ['slug' => 'instrutor-autonomo-mensal'],
            [
                'name' => 'Plano Instrutor Autônomo Mensal',
                'description' => 'Acesso completo ao ecossistema HL Drive',
                'price' => 59.90,
                'interval_days' => 30,
                'features' => ['unlimited_students' => true],
                'is_active' => true,
            ]
        );

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

    public function testInstructorCanViewActiveSubscriptionSummary(): void
    {
        TenantSubscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'price' => $this->plan->price,
            'current_period_start' => now()->subDays(5),
            'current_period_end' => now()->addDays(25),
            'grace_period_ends_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->getJson('/api/subscription');

        $response->assertOk()
            ->assertJsonPath('subscription.status', 'active')
            ->assertJsonPath('subscription.is_past_due', false)
            ->assertJsonPath('subscription.is_in_grace_period', false)
            ->assertJsonPath('subscription.is_read_only', false)
            ->assertJsonPath('can_mutate', true)
            ->assertJsonPath('banner.show_banner', false);
    }

    public function testInstructorSeesWarningBannerWhenInGracePeriod(): void
    {
        TenantSubscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'past_due',
            'price' => $this->plan->price,
            'current_period_start' => now()->subDays(32),
            'current_period_end' => now()->subDays(2),
            'grace_period_ends_at' => now()->addDays(3),
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->getJson('/api/subscription');

        $response->assertOk()
            ->assertJsonPath('subscription.status', 'past_due')
            ->assertJsonPath('subscription.is_past_due', true)
            ->assertJsonPath('subscription.is_in_grace_period', true)
            ->assertJsonPath('subscription.is_read_only', false)
            ->assertJsonPath('can_mutate', true)
            ->assertJsonPath('banner.show_banner', true)
            ->assertJsonPath('banner.type', 'warning');
    }

    public function testInstructorSeesDangerBannerAndReadOnlyWhenPastGracePeriod(): void
    {
        TenantSubscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'past_due',
            'price' => $this->plan->price,
            'current_period_start' => now()->subDays(40),
            'current_period_end' => now()->subDays(10),
            'grace_period_ends_at' => now()->subDays(5),
            'extended_until' => null,
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->getJson('/api/subscription');

        $response->assertOk()
            ->assertJsonPath('subscription.is_read_only', true)
            ->assertJsonPath('can_mutate', false)
            ->assertJsonPath('banner.show_banner', true)
            ->assertJsonPath('banner.type', 'danger');
    }

    public function testAdminManualExtensionAllowsMutationsAndShowsExtendedBanner(): void
    {
        TenantSubscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'past_due',
            'price' => $this->plan->price,
            'current_period_start' => now()->subDays(40),
            'current_period_end' => now()->subDays(10),
            'grace_period_ends_at' => now()->subDays(5),
            'extended_until' => now()->addDays(5),
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->getJson('/api/subscription');

        $response->assertOk()
            ->assertJsonPath('subscription.is_manually_extended', true)
            ->assertJsonPath('subscription.is_read_only', false)
            ->assertJsonPath('can_mutate', true)
            ->assertJsonPath('banner.show_banner', true)
            ->assertJsonPath('banner.type', 'warning');
    }

    public function testInstructorCanListPaymentsOrderedLatestFirst(): void
    {
        $sub = TenantSubscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'price' => $this->plan->price,
            'current_period_start' => now()->subDays(10),
            'current_period_end' => now()->addDays(20),
        ]);

        SubscriptionPayment::create([
            'tenant_subscription_id' => $sub->id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->instructor->id,
            'amount' => 59.90,
            'payment_method' => 'pix_online',
            'status' => 'approved',
            'due_date' => now()->subDays(30)->toDateString(),
            'created_at' => now()->subDays(30),
        ]);

        SubscriptionPayment::create([
            'tenant_subscription_id' => $sub->id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->instructor->id,
            'amount' => 59.90,
            'payment_method' => 'pix_offline',
            'status' => 'under_review',
            'due_date' => now()->subDays(5)->toDateString(),
            'created_at' => now()->subDays(5),
        ]);

        $latestPayment = SubscriptionPayment::create([
            'tenant_subscription_id' => $sub->id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->instructor->id,
            'amount' => 59.90,
            'payment_method' => 'pix_online',
            'status' => 'pending',
            'due_date' => now()->toDateString(),
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->getJson('/api/subscription/payments');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.id', $latestPayment->id);
    }

    public function testInstructorCanCreatePaymentIntentForOnlinePix(): void
    {
        TenantSubscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'price' => $this->plan->price,
            'current_period_start' => now()->subDays(10),
            'current_period_end' => now()->addDays(20),
        ]);

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->postJson('/api/subscription/pay', [
                'payment_method' => 'pix_online',
            ]);

        $response->assertCreated()
            ->assertJsonPath('payment.status', 'pending')
            ->assertJsonPath('payment.amount', '59.90')
            ->assertJsonStructure([
                'payment' => ['id', 'status', 'amount', 'pix_code'],
                'pix_details' => ['code', 'key', 'receiver', 'amount'],
            ]);

        $this->assertDatabaseHas('subscription_payments', [
            'tenant_id' => $this->tenant->id,
            'payment_method' => 'pix_online',
            'status' => 'pending',
        ]);
    }

    public function testInstructorCanUploadOfflinePixReceipt(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('comprovante_pix.jpg', 300, 'image/jpeg');

        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->postJson('/api/subscription/upload-receipt', [
                'receipt' => $file,
                'notes' => 'Comprovante de transferência bancária',
            ]);

        $response->assertOk()
            ->assertJsonPath('payment.status', 'under_review')
            ->assertJsonPath('payment.notes', 'Comprovante de transferência bancária');

        $this->assertDatabaseHas('subscription_payments', [
            'tenant_id' => $this->tenant->id,
            'payment_method' => 'pix_offline',
            'status' => 'under_review',
        ]);

        $payment = SubscriptionPayment::where('tenant_id', $this->tenant->id)->first();
        $this->assertNotNull($payment->receipt_path);
        Storage::disk('public')->assertExists($payment->receipt_path);
    }

    public function testReadOnlyModeBlocksMutationsWith402PaymentRequired(): void
    {
        TenantSubscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'past_due',
            'price' => $this->plan->price,
            'current_period_start' => now()->subDays(40),
            'current_period_end' => now()->subDays(10),
            'grace_period_ends_at' => now()->subDays(5),
            'extended_until' => null,
        ]);

        // Attempting to create a package should be blocked by EnforceSubscriptionStatusMiddleware
        $response = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->postJson('/api/packages', [
                'name' => 'Pacote 10 Horas',
                'description' => '10 horas de aula prática',
                'price' => 1200.00,
                'hours' => 10,
                'instructor_id' => $this->instructor->id,
            ]);

        $response->assertStatus(402)
            ->assertJsonPath('error', 'subscription_suspended')
            ->assertJsonStructure(['error', 'message', 'deadline', 'payment_url']);
    }

    public function testReadOnlyModeAllowsSafeReadsAndBillingRoutes(): void
    {
        TenantSubscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'past_due',
            'price' => $this->plan->price,
            'current_period_start' => now()->subDays(40),
            'current_period_end' => now()->subDays(10),
            'grace_period_ends_at' => now()->subDays(5),
            'extended_until' => null,
        ]);

        // GET requests are allowed
        $readResponse = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->getJson('/api/packages');

        $readResponse->assertOk();

        // Billing pay intent is exempt from block
        $payResponse = $this->actingAs($this->instructor)
            ->withHeaders($this->headers)
            ->postJson('/api/subscription/pay', [
                'payment_method' => 'pix_online',
            ]);

        $payResponse->assertCreated();
    }

    public function testSuperAdminCanListAllSubscriptionsAndPayments(): void
    {
        TenantSubscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'active',
            'price' => $this->plan->price,
            'current_period_start' => now()->subDays(5),
            'current_period_end' => now()->addDays(25),
        ]);

        $subResponse = $this->actingAs($this->admin)
            ->getJson('/api/admin/subscriptions');

        $subResponse->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);

        $payResponse = $this->actingAs($this->admin)
            ->getJson('/api/admin/payments');

        $payResponse->assertOk()
            ->assertJsonStructure(['data', 'current_page', 'total']);
    }

    public function testSuperAdminCanApprovePaymentAdvancingCycleAndReactivatingSubscription(): void
    {
        $subscription = TenantSubscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'past_due',
            'price' => $this->plan->price,
            'current_period_start' => now()->subDays(35),
            'current_period_end' => now()->subDays(5),
            'grace_period_ends_at' => now()->subDays(1),
        ]);

        $payment = SubscriptionPayment::create([
            'tenant_subscription_id' => $subscription->id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->instructor->id,
            'amount' => 59.90,
            'payment_method' => 'pix_offline',
            'status' => 'under_review',
            'due_date' => now()->toDateString(),
            'receipt_path' => 'receipts/test.jpg',
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/payments/{$payment->id}/approve", [
                'notes' => 'Comprovante verificado no extrato',
            ]);

        $response->assertOk()
            ->assertJsonPath('payment.status', 'approved');

        $this->assertDatabaseHas('subscription_payments', [
            'id' => $payment->id,
            'status' => 'approved',
            'reviewed_by' => $this->admin->id,
        ]);

        $subscription->refresh();
        $this->assertSame('active', $subscription->status);
        $this->assertTrue($subscription->current_period_end->isFuture());
    }

    public function testSuperAdminCanRejectPaymentWithReason(): void
    {
        $subscription = TenantSubscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'past_due',
            'price' => $this->plan->price,
            'current_period_start' => now()->subDays(35),
            'current_period_end' => now()->subDays(5),
        ]);

        $payment = SubscriptionPayment::create([
            'tenant_subscription_id' => $subscription->id,
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->instructor->id,
            'amount' => 59.90,
            'payment_method' => 'pix_offline',
            'status' => 'under_review',
            'due_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/payments/{$payment->id}/reject", [
                'reason' => 'Comprovante ilegível ou fraudulento',
            ]);

        $response->assertOk()
            ->assertJsonPath('payment.status', 'rejected');

        $this->assertDatabaseHas('subscription_payments', [
            'id' => $payment->id,
            'status' => 'rejected',
            'reviewed_by' => $this->admin->id,
        ]);
    }

    public function testSuperAdminCanManuallyExtendGracePeriod(): void
    {
        $subscription = TenantSubscription::create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $this->plan->id,
            'status' => 'past_due',
            'price' => $this->plan->price,
            'current_period_start' => now()->subDays(40),
            'current_period_end' => now()->subDays(10),
            'grace_period_ends_at' => now()->subDays(5),
            'extended_until' => null,
        ]);

        $this->assertTrue($subscription->isReadOnly());

        $newDeadline = Carbon::now()->addDays(7)->toDateString();

        $response = $this->actingAs($this->admin)
            ->postJson("/api/admin/subscriptions/{$subscription->id}/extend", [
                'extended_until' => $newDeadline,
                'notes' => 'Tolerância adicional acordada por telefone',
            ]);

        $response->assertOk()
            ->assertJsonPath('subscription.is_manually_extended', true)
            ->assertJsonPath('subscription.is_read_only', false);

        $subscription->refresh();
        $this->assertNotNull($subscription->extended_until);
        $this->assertFalse($subscription->isReadOnly());
    }
}
