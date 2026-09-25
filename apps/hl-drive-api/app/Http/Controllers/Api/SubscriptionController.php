<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Services\SubscriptionService;
use App\Services\TenantResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController extends Controller
{
    public function __construct(
        protected TenantResolver $tenantResolver,
        protected SubscriptionService $subscriptionService
    ) {
    }

    /**
     * Get the current tenant's subscription summary and banner state.
     */
    public function summary(Request $request): JsonResponse
    {
        $tenant = $this->tenantResolver->getTenantId()
            ? \App\Models\Tenant::find($this->tenantResolver->getTenantId())
            : null;

        if ($tenant === null) {
            return response()->json([
                'message' => 'Nenhum tenant ativo encontrado no contexto.',
            ], Response::HTTP_NOT_FOUND);
        }

        $subscription = $this->subscriptionService->getOrCreateSubscription($tenant);
        $banner = $subscription->bannerData();

        return response()->json([
            'subscription' => [
                'id' => $subscription->id,
                'status' => $subscription->status,
                'price' => (float) $subscription->price,
                'current_period_start' => $subscription->current_period_start?->format('Y-m-d H:i:s'),
                'current_period_end' => $subscription->current_period_end?->format('Y-m-d H:i:s'),
                'grace_period_ends_at' => $subscription->grace_period_ends_at?->format('Y-m-d H:i:s'),
                'extended_until' => $subscription->extended_until?->format('Y-m-d H:i:s'),
                'effective_deadline' => $subscription->effectiveGraceDeadline()?->format('Y-m-d H:i:s'),
                'is_past_due' => $subscription->isPastDue(),
                'is_in_grace_period' => $subscription->isInGracePeriod(),
                'is_manually_extended' => $subscription->isManuallyExtended(),
                'is_read_only' => $subscription->isReadOnly(),
                'plan' => $subscription->plan ? [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'slug' => $subscription->plan->slug,
                    'interval_days' => $subscription->plan->interval_days,
                ] : null,
            ],
            'banner' => $banner,
            'can_mutate' => $subscription->canPerformMutations(),
            'payment_instructions' => [
                'pix' => config('billing.pix'),
                'picpay' => config('billing.picpay'),
            ],
        ]);
    }

    /**
     * Get payment history for the tenant (latest payments first).
     */
    public function payments(Request $request): JsonResponse
    {
        $tenantId = $this->tenantResolver->getTenantId();

        if ($tenantId === null) {
            return response()->json([
                'message' => 'Nenhum tenant ativo encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        $payments = SubscriptionPayment::with(['user:id,name,email', 'reviewer:id,name'])
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate((int) $request->input('per_page', 15));

        return response()->json($payments);
    }

    /**
     * Generate a new payment intent (e.g. online PIX copy & paste).
     */
    public function pay(Request $request): JsonResponse
    {
        $tenant = $this->tenantResolver->getTenantId()
            ? \App\Models\Tenant::find($this->tenantResolver->getTenantId())
            : null;

        if ($tenant === null) {
            return response()->json([
                'message' => 'Nenhum tenant ativo encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Usuário não autenticado.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $subscription = $this->subscriptionService->getOrCreateSubscription($tenant);
        $method = (string) $request->input('payment_method', 'pix_online');

        $payment = $this->subscriptionService->createPaymentIntent($subscription, $user, $method);

        return response()->json([
            'message' => 'Intenção de pagamento gerada com sucesso.',
            'payment' => $payment,
            'pix_details' => [
                'code' => $payment->pix_code,
                'key' => config('billing.pix.key'),
                'receiver' => config('billing.pix.receiver_name'),
                'amount' => $payment->amount,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * Upload offline payment proof/receipt.
     */
    public function uploadReceipt(Request $request): JsonResponse
    {
        $tenant = $this->tenantResolver->getTenantId()
            ? \App\Models\Tenant::find($this->tenantResolver->getTenantId())
            : null;

        if ($tenant === null) {
            return response()->json([
                'message' => 'Nenhum tenant ativo encontrado.',
            ], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'payment_id' => ['nullable', 'integer', 'exists:subscription_payments,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $paymentId = $validated['payment_id'] ?? null;
        $payment = null;

        if ($paymentId !== null) {
            $payment = SubscriptionPayment::where('tenant_id', $tenant->id)->find($paymentId);
        }

        if ($payment === null) {
            $subscription = $this->subscriptionService->getOrCreateSubscription($tenant);
            $payment = $this->subscriptionService->createPaymentIntent($subscription, $user, 'pix_offline');
        }

        $file = $request->file('receipt');

        if ($file === null) {
            return response()->json(['message' => 'Arquivo do comprovante obrigatório.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $updatedPayment = $this->subscriptionService->uploadReceipt(
            $payment,
            $file,
            $validated['notes'] ?? null
        );

        return response()->json([
            'message' => 'Comprovante enviado com sucesso! Nosso time irá analisar e aprovar em breve.',
            'payment' => $updatedPayment,
        ]);
    }
}
