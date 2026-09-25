<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SubscriptionScenarioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure Super Admin Role exists
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $adminRoleApi = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);

        // 2. Create Super Admin User
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@hourledger.com'],
            [
                'name' => 'Super Administrador SaaS',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        $superAdmin->assignRole('admin');

        // 3. Create SaaS Plans
        $monthlyPlan = Plan::firstOrCreate(
            ['slug' => 'instrutor-autonomo-mensal'],
            [
                'name' => 'Instrutor Autônomo Mensal',
                'description' => 'Plano mensal com controle completo de alunos, agendamento de aulas e gestão de carteiras de horas.',
                'price' => 79.90,
                'interval_days' => 30,
                'is_active' => true,
            ]
        );

        $annualPlan = Plan::firstOrCreate(
            ['slug' => 'instrutor-pro-anual'],
            [
                'name' => 'Instrutor Pro Anual',
                'description' => 'Plano anual com desconto e relatórios avançados de horas e faturamento.',
                'price' => 790.00,
                'interval_days' => 365,
                'is_active' => true,
            ]
        );

        // 4. Cenário 1: Instrutor Ativo (Em Dia)
        $this->createScenarioActiveInstructor($monthlyPlan);

        // 5. Cenário 2: Instrutor em Atraso dentro da Carência (5 dias)
        $this->createScenarioGracePeriodInstructor($monthlyPlan);

        // 6. Cenário 3: Instrutor em Atraso com Prorrogação Manual do Admin
        $this->createScenarioExtendedInstructor($monthlyPlan);

        // 7. Cenário 4: Instrutor Bloqueado / Modo Somente Leitura (Carência Expirada)
        $this->createScenarioSuspendedInstructor($monthlyPlan);

        // 8. Cenário 5: Instrutor com Comprovante PIX Offline Sob Análise
        $this->createScenarioUnderReviewInstructor($monthlyPlan);
    }

    /**
     * Cenário 1: Instrutor em dia com renovação em 25 dias.
     */
    protected function createScenarioActiveInstructor(Plan $plan): void
    {
        $user = User::firstOrCreate(
            ['email' => 'instrutor.ativo@test.com'],
            [
                'name' => 'Instrutor Carlos (Em Dia)',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        $tenant = Tenant::firstOrCreate(
            ['slug' => 'auto-escola-ativa'],
            [
                'name' => 'Auto Escola Carlos Ativa',
                'status' => 'active',
            ]
        );

        $user->tenants()->syncWithoutDetaching([
            $tenant->id => ['role' => 'admin', 'status' => 'active'],
        ]);

        $subscription = TenantSubscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $plan->id,
                'status' => 'active',
                'current_period_start' => now()->subDays(5),
                'current_period_end' => now()->addDays(25),
                'grace_period_ends_at' => now()->addDays(30),
                'extended_until' => null,
                'price' => $plan->price,
            ]
        );

        SubscriptionPayment::firstOrCreate(
            [
                'tenant_subscription_id' => $subscription->id,
                'due_date' => now()->subDays(5)->toDateString(),
            ],
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'amount' => $plan->price,
                'payment_method' => 'pix_online',
                'status' => 'approved',
                'paid_at' => now()->subDays(5),
                'notes' => 'Pagamento aprovado via PIX Online',
            ]
        );
    }

    /**
     * Cenário 2: Instrutor em atraso há 2 dias, dentro da carência de 5 dias.
     */
    protected function createScenarioGracePeriodInstructor(Plan $plan): void
    {
        $user = User::firstOrCreate(
            ['email' => 'instrutor.carencia@test.com'],
            [
                'name' => 'Instrutora Paula (Carência Ativa)',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        $tenant = Tenant::firstOrCreate(
            ['slug' => 'auto-escola-carencia'],
            [
                'name' => 'Auto Escola Paula Carência',
                'status' => 'active',
            ]
        );

        $user->tenants()->syncWithoutDetaching([
            $tenant->id => ['role' => 'admin', 'status' => 'active'],
        ]);

        $subscription = TenantSubscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $plan->id,
                'status' => 'past_due',
                'current_period_start' => now()->subDays(32),
                'current_period_end' => now()->subDays(2),
                'grace_period_ends_at' => now()->addDays(3), // 5 dias de carência a partir do vencimento
                'extended_until' => null,
                'price' => $plan->price,
            ]
        );

        SubscriptionPayment::firstOrCreate(
            [
                'tenant_subscription_id' => $subscription->id,
                'due_date' => now()->subDays(2)->toDateString(),
            ],
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'amount' => $plan->price,
                'payment_method' => 'pix_online',
                'status' => 'pending',
                'pix_code' => '00020126580014BR.GOV.BCB.PIX0136financeiro@hourledger.com52040000530398654079.905802BR5925HOUR LEDGER6009SAO PAULO62070503***6304ABCD',
                'notes' => 'Aguardando pagamento do ciclo em atraso',
            ]
        );
    }

    /**
     * Cenário 3: Instrutor em atraso há 8 dias, com prazo prorrogado pelo admin em 7 dias.
     */
    protected function createScenarioExtendedInstructor(Plan $plan): void
    {
        $user = User::firstOrCreate(
            ['email' => 'instrutor.prorrogado@test.com'],
            [
                'name' => 'Instrutor Marcos (Prazo Prorrogado)',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        $tenant = Tenant::firstOrCreate(
            ['slug' => 'auto-escola-prorrogada'],
            [
                'name' => 'Auto Escola Marcos Prorrogada',
                'status' => 'active',
            ]
        );

        $user->tenants()->syncWithoutDetaching([
            $tenant->id => ['role' => 'admin', 'status' => 'active'],
        ]);

        $subscription = TenantSubscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $plan->id,
                'status' => 'past_due',
                'current_period_start' => now()->subDays(38),
                'current_period_end' => now()->subDays(8),
                'grace_period_ends_at' => now()->subDays(3),
                'extended_until' => now()->addDays(7), // Admin concedeu mais 7 dias de tolerância
                'price' => $plan->price,
                'notes' => 'Prazo estendido manualmente a pedido do instrutor via WhatsApp',
            ]
        );

        SubscriptionPayment::firstOrCreate(
            [
                'tenant_subscription_id' => $subscription->id,
                'due_date' => now()->subDays(8)->toDateString(),
            ],
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'amount' => $plan->price,
                'payment_method' => 'pix_online',
                'status' => 'pending',
                'pix_code' => '00020126580014BR.GOV.BCB.PIX0136financeiro@hourledger.com52040000530398654079.905802BR5925HOUR LEDGER6009SAO PAULO62070503***6304XYZW',
            ]
        );
    }

    /**
     * Cenário 4: Instrutor com carência expirada há 5 dias (Modo Somente Leitura).
     */
    protected function createScenarioSuspendedInstructor(Plan $plan): void
    {
        $user = User::firstOrCreate(
            ['email' => 'instrutor.bloqueado@test.com'],
            [
                'name' => 'Instrutor Roberto (Somente Leitura)',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        $tenant = Tenant::firstOrCreate(
            ['slug' => 'auto-escola-bloqueada'],
            [
                'name' => 'Auto Escola Roberto Bloqueada',
                'status' => 'active',
            ]
        );

        $user->tenants()->syncWithoutDetaching([
            $tenant->id => ['role' => 'admin', 'status' => 'active'],
        ]);

        $subscription = TenantSubscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $plan->id,
                'status' => 'suspended',
                'current_period_start' => now()->subDays(40),
                'current_period_end' => now()->subDays(10),
                'grace_period_ends_at' => now()->subDays(5),
                'extended_until' => null,
                'price' => $plan->price,
            ]
        );

        SubscriptionPayment::firstOrCreate(
            [
                'tenant_subscription_id' => $subscription->id,
                'due_date' => now()->subDays(10)->toDateString(),
            ],
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'amount' => $plan->price,
                'payment_method' => 'pix_online',
                'status' => 'pending',
                'notes' => 'Atrasado além da carência. Conta em modo somente leitura.',
            ]
        );
    }

    /**
     * Cenário 5: Instrutor com comprovante PIX Offline sob análise do Super Admin.
     */
    protected function createScenarioUnderReviewInstructor(Plan $plan): void
    {
        $user = User::firstOrCreate(
            ['email' => 'instrutor.analise@test.com'],
            [
                'name' => 'Instrutora Fernanda (Comprovante Sob Análise)',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );

        $tenant = Tenant::firstOrCreate(
            ['slug' => 'auto-escola-analise'],
            [
                'name' => 'Auto Escola Fernanda Em Análise',
                'status' => 'active',
            ]
        );

        $user->tenants()->syncWithoutDetaching([
            $tenant->id => ['role' => 'admin', 'status' => 'active'],
        ]);

        $subscription = TenantSubscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $plan->id,
                'status' => 'past_due',
                'current_period_start' => now()->subDays(33),
                'current_period_end' => now()->subDays(3),
                'grace_period_ends_at' => now()->addDays(2),
                'extended_until' => null,
                'price' => $plan->price,
            ]
        );

        SubscriptionPayment::firstOrCreate(
            [
                'tenant_subscription_id' => $subscription->id,
                'due_date' => now()->subDays(3)->toDateString(),
            ],
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'amount' => $plan->price,
                'payment_method' => 'pix_offline',
                'status' => 'under_review',
                'receipt_path' => 'receipts/comprovante_pix_fernanda.pdf',
                'notes' => 'Transferência PIX realizada pelo banco Inter. Aguardando aprovação.',
            ]
        );
    }
}
