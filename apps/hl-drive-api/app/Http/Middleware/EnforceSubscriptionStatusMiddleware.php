<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use App\Services\TenantResolver;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceSubscriptionStatusMiddleware
{
    public function __construct(
        protected TenantResolver $tenantResolver,
        protected SubscriptionService $subscriptionService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * In read-only mode (overdue beyond grace period without manual extension),
     * only read operations (GET, HEAD, OPTIONS) and billing/auth routes are permitted.
     * Any mutating request (POST, PUT, PATCH, DELETE) is rejected with 402 Payment Required.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Safe read methods are always permitted (Read-Only Mode)
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        // Exempt routes (authentication, payments, upload receipts)
        if ($this->isExemptRoute($request)) {
            return $next($request);
        }

        $tenantId = $this->tenantResolver->getTenantId();

        if ($tenantId === null) {
            return $next($request);
        }

        $subscription = $this->subscriptionService->getTenantSubscription($tenantId);

        if ($subscription === null) {
            return $next($request);
        }

        if ($subscription->isReadOnly()) {
            $deadline = $subscription->effectiveGraceDeadline();

            return new JsonResponse([
                'error' => 'subscription_suspended',
                'message' => config(
                    'billing.messages.suspended',
                    'Seu plano está suspenso por falta de pagamento. A conta está em modo somente leitura até a regularização.'
                ),
                'deadline' => $deadline ? $deadline->format('Y-m-d') : null,
                'payment_url' => '/billing/payment',
            ], Response::HTTP_PAYMENT_REQUIRED);
        }

        return $next($request);
    }

    /**
     * Determine if the request route is exempt from subscription write blocks.
     */
    protected function isExemptRoute(Request $request): bool
    {
        $path = $request->path();

        $exemptPatterns = [
            'api/auth*',
            'api/login*',
            'api/logout*',
            'api/subscription/pay*',
            'api/subscription/upload-receipt*',
            'api/admin*',
            'api/health-check*',
            'up',
        ];

        foreach ($exemptPatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
