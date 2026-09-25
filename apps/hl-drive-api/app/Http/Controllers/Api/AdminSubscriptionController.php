<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use App\Models\TenantSubscription;
use App\Services\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminSubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {
    }

    /**
     * List all tenant subscriptions with filters.
     */
    public function indexSubscriptions(Request $request): JsonResponse
    {
        $query = TenantSubscription::with(['tenant:id,name,slug,status', 'plan:id,name,price,interval_days']);

        $status = $request->input('status');

        if ($status !== null && $status !== '') {
            $query->where('status', (string) $status);
        }

        $subscriptions = $query->orderBy('id', 'desc')
            ->paginate((int) $request->input('per_page', 20));

        // Augment items with runtime calculated status
        $subscriptions->getCollection()->transform(fn (TenantSubscription $sub): array => [
            'id' => $sub->id,
            'tenant' => $sub->tenant,
            'plan' => $sub->plan,
            'status' => $sub->status,
            'price' => (float) $sub->price,
            'current_period_start' => $sub->current_period_start?->format('Y-m-d H:i:s'),
            'current_period_end' => $sub->current_period_end?->format('Y-m-d H:i:s'),
            'grace_period_ends_at' => $sub->grace_period_ends_at?->format('Y-m-d H:i:s'),
            'extended_until' => $sub->extended_until?->format('Y-m-d H:i:s'),
            'effective_deadline' => $sub->effectiveGraceDeadline()?->format('Y-m-d H:i:s'),
            'is_past_due' => $sub->isPastDue(),
            'is_in_grace_period' => $sub->isInGracePeriod(),
            'is_manually_extended' => $sub->isManuallyExtended(),
            'is_read_only' => $sub->isReadOnly(),
            'banner_data' => $sub->bannerData(),
        ]);

        return response()->json($subscriptions);
    }

    /**
     * List all payments for admin review/moderation (latest first).
     */
    public function indexPayments(Request $request): JsonResponse
    {
        $query = SubscriptionPayment::with([
            'tenant:id,name,slug',
            'user:id,name,email',
            'reviewer:id,name,email',
            'subscription.plan:id,name',
        ]);

        $status = $request->input('status');

        if ($status !== null && $status !== '') {
            $query->where('status', (string) $status);
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json($payments);
    }

    /**
     * Approve a subscription payment (advances the cycle and clears grace).
     */
    public function approvePayment(Request $request, int $id): JsonResponse
    {
        $payment = SubscriptionPayment::with('subscription.plan')->find($id);

        if ($payment === null) {
            return response()->json(['message' => 'Pagamento não encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $notes = $request->input('notes');

        $approvedPayment = $this->subscriptionService->approvePayment($payment, $user, $notes);

        return response()->json([
            'message' => 'Pagamento aprovado com sucesso! Assinatura ativada e renovada.',
            'payment' => $approvedPayment,
            'subscription' => $approvedPayment->subscription,
        ]);
    }

    /**
     * Reject a subscription payment (with mandatory or optional reason).
     */
    public function rejectPayment(Request $request, int $id): JsonResponse
    {
        $payment = SubscriptionPayment::find($id);

        if ($payment === null) {
            return response()->json(['message' => 'Pagamento não encontrado.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $user = $request->user();

        if ($user === null) {
            return response()->json(['message' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        $rejectedPayment = $this->subscriptionService->rejectPayment(
            $payment,
            $user,
            $validated['reason']
        );

        return response()->json([
            'message' => 'Pagamento rejeitado com sucesso.',
            'payment' => $rejectedPayment,
        ]);
    }

    /**
     * Manually extend the grace period deadline for a tenant subscription.
     */
    public function extendGracePeriod(Request $request, int $id): JsonResponse
    {
        $subscription = TenantSubscription::find($id);

        if ($subscription === null) {
            return response()->json(['message' => 'Assinatura não encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'extended_until' => ['required', 'date', 'after:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $extendedDate = Carbon::parse($validated['extended_until']);

        $updatedSubscription = $this->subscriptionService->extendGracePeriod(
            $subscription,
            $extendedDate,
            $validated['notes'] ?? null
        );

        return response()->json([
            'message' => 'Prazo de tolerância prorrogado com sucesso pelo administrador.',
            'subscription' => $updatedSubscription,
            'banner_data' => $updatedSubscription->bannerData(),
        ]);
    }
}
