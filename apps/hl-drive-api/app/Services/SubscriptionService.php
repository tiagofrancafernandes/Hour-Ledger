<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class SubscriptionService
{
    /**
     * Get the active subscription for a tenant.
     */
    public function getTenantSubscription(int $tenantId): ?TenantSubscription
    {
        return TenantSubscription::with('plan')
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first();
    }

    /**
     * Get or create a default subscription for a tenant.
     */
    public function getOrCreateSubscription(Tenant $tenant): TenantSubscription
    {
        $existing = $this->getTenantSubscription($tenant->id);

        if ($existing !== null) {
            return $existing;
        }

        $defaultSlug = config('billing.default_plan_slug', 'instrutor-autonomo-mensal');
        $plan = Plan::where('slug', $defaultSlug)->first();

        if ($plan === null) {
            $plan = Plan::create([
                'name' => 'Instrutor Autônomo Mensal',
                'slug' => $defaultSlug,
                'description' => 'Plano padrão com gestão completa de alunos, aulas e carteiras de horas.',
                'price' => 79.90,
                'interval_days' => 30,
                'is_active' => true,
            ]);
        }

        $graceDays = (int) config('billing.grace_period_days', 5);

        return TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addDays($plan->interval_days),
            'grace_period_ends_at' => now()->addDays($plan->interval_days + $graceDays),
            'price' => $plan->price,
        ]);
    }

    /**
     * Create a payment intent (online PIX, PicPay or offline PIX).
     */
    public function createPaymentIntent(
        TenantSubscription $subscription,
        User $user,
        string $method = 'pix_online'
    ): SubscriptionPayment {
        $amount = (float) $subscription->price;
        $pixKey = (string) config('billing.pix.key', 'financeiro@hourledger.com');

        // Generate synthetic PIX copy & paste payload
        $pixPayload = sprintf(
            '00020126580014BR.GOV.BCB.PIX0136%s520400005303986540%0.2f5802BR5925%s6009SAO PAULO62070503***6304%s',
            $pixKey,
            $amount,
            config('billing.pix.receiver_name', 'HOUR LEDGER'),
            strtoupper(Str::random(4))
        );

        return SubscriptionPayment::create([
            'tenant_subscription_id' => $subscription->id,
            'tenant_id' => $subscription->tenant_id,
            'user_id' => $user->id,
            'amount' => $amount,
            'payment_method' => $method,
            'status' => 'pending',
            'due_date' => now()->toDateString(),
            'pix_code' => $pixPayload,
        ]);
    }

    /**
     * Upload an offline payment proof/receipt.
     */
    public function uploadReceipt(
        SubscriptionPayment $payment,
        UploadedFile $file,
        ?string $notes = null
    ): SubscriptionPayment {
        $path = $file->store('receipts', 'public');

        $payment->update([
            'payment_method' => 'pix_offline',
            'status' => 'under_review',
            'receipt_path' => $path,
            'notes' => $notes ?? $payment->notes,
        ]);

        return $payment->refresh();
    }

    /**
     * Super Admin: Approve payment and advance subscription period.
     */
    public function approvePayment(
        SubscriptionPayment $payment,
        User $reviewer,
        ?string $notes = null
    ): SubscriptionPayment {
        $subscription = $payment->subscription;
        $plan = $subscription->plan;

        $graceDays = (int) config('billing.grace_period_days', 5);
        $intervalDays = $plan ? $plan->interval_days : 30;

        // Base new start from either now or current_period_end if it's in the future
        $newStart = $subscription->current_period_end->isFuture()
            ? $subscription->current_period_end
            : now();

        $newEnd = $newStart->copy()->addDays($intervalDays);
        $newGrace = $newEnd->copy()->addDays($graceDays);

        $subscription->update([
            'status' => 'active',
            'current_period_start' => $newStart,
            'current_period_end' => $newEnd,
            'grace_period_ends_at' => $newGrace,
            'extended_until' => null,
        ]);

        $payment->update([
            'status' => 'approved',
            'paid_at' => now(),
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'notes' => $notes ?? $payment->notes,
        ]);

        return $payment->refresh();
    }

    /**
     * Super Admin: Reject payment with mandatory justification.
     */
    public function rejectPayment(
        SubscriptionPayment $payment,
        User $reviewer,
        string $reason
    ): SubscriptionPayment {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('A justificativa de rejeição é obrigatória.');
        }

        $payment->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        return $payment->refresh();
    }

    /**
     * Super Admin: Manually extend the grace period deadline for a tenant.
     */
    public function extendGracePeriod(
        TenantSubscription $subscription,
        Carbon $extendedUntil,
        ?string $notes = null
    ): TenantSubscription {
        if ($extendedUntil->isPast()) {
            throw new \InvalidArgumentException('A data limite de prorrogação deve ser no futuro.');
        }

        $subscription->update([
            'extended_until' => $extendedUntil,
            'notes' => $notes ?? $subscription->notes,
        ]);

        return $subscription->refresh();
    }
}
