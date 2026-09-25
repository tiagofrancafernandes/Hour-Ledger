<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\TenantNotActive;
use App\Exceptions\TenantNotFound;
use App\Exceptions\UnauthorizedTenant;
use App\Models\Client;
use App\Models\Tenant;
use App\Services\TenantResolver;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TenantMiddleware resolves the active tenant for each request.
 *
 * This middleware implements the following tenant detection logic:
 * 1. Check X-Tenant-ID header
 * 2. Check 'tenant' query parameter
 * 3. Extract from URL path (e.g., /api/tenant/123/...)
 * 4. Resolve from authenticated user associations
 *
 * If no tenant is found or the tenant is invalid, returns a 403 Forbidden response.
 */
class TenantMiddleware
{
    /**
     * Create a new middleware instance.
     *
     * @param TenantResolver $resolver
     */
    public function __construct(
        private readonly TenantResolver $resolver
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): Response $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $this->resolveTenantId($request);

        if ($tenantId === null && $this->isExemptRoute($request)) {
            return $next($request);
        }

        if ($tenantId === null) {
            return $this->forbiddenResponse('No tenant specified.');
        }

        try {
            $userId = $request->user()?->id;
            $this->resolver->setTenantId($tenantId, $userId);
            $this->resolver->applyPostgresSearchPath();

            $request->attributes->set('tenant_id', $tenantId);
            $request->attributes->set('tenant_schema', $this->resolver->getSchema());
            $request->attributes->set('tenant_context', $this->resolver->getContext());
        } catch (TenantNotFound $exception) {
            return $this->forbiddenResponse($exception->getMessage());
        } catch (TenantNotActive $exception) {
            return $this->forbiddenResponse($exception->getMessage());
        } catch (UnauthorizedTenant $exception) {
            return $this->forbiddenResponse($exception->getMessage());
        }

        return $next($request);
    }

    /**
     * Resolve the tenant ID from the request.
     *
     * Attempts to find tenant ID in the following order:
     * 1. X-Tenant-ID header
     * 2. 'tenant' query parameter
     * 3. First numeric segment in URL path after /api/ or /tenant/
     * 4. Authenticated user's associated tenant or client
     *
     * @param Request $request
     *
     * @return int|null The resolved tenant ID, or null if not found
     */
    private function resolveTenantId(Request $request): ?int
    {
        if ($request->hasHeader('X-Tenant-ID')) {
            $tenantId = (int) $request->header('X-Tenant-ID');

            if ($tenantId > 0) {
                return $tenantId;
            }
        }

        if ($request->has('tenant')) {
            $tenantId = (int) $request->input('tenant');

            if ($tenantId > 0) {
                return $tenantId;
            }
        }

        $tenantId = $this->extractTenantIdFromPath($request->path());

        if ($tenantId !== null && $tenantId > 0) {
            return $tenantId;
        }

        $user = $request->user();

        if ($user !== null) {
            if ($user->customer_id) {
                $clientTenantId = Client::withoutGlobalScopes()
                    ->where('id', $user->customer_id)
                    ->value('tenant_id');

                if ($clientTenantId !== null && (int) $clientTenantId > 0) {
                    return (int) $clientTenantId;
                }
            }

            $firstTenant = $user->tenants()->first();

            if ($firstTenant !== null) {
                return (int) $firstTenant->id;
            }

            if (app()->environment('testing') || $user->hasRole(['admin', 'super-admin'])) {
                if ($this->resolver->hasTenant()) {
                    return $this->resolver->getTenantId();
                }

                $activeTenant = Tenant::where('status', 'active')->latest('id')->first();

                if ($activeTenant !== null) {
                    return (int) $activeTenant->id;
                }
            }
        }

        return null;
    }

    /**
     * Check if the incoming request is exempt from tenant resolution.
     *
     * @param Request $request
     *
     * @return bool
     */
    private function isExemptRoute(Request $request): bool
    {
        $exemptPatterns = [
            'up',
            'api/up',
            'api/health-check*',
            'health-check*',
            'api/public*',
            'public*',
            'api/auth*',
            'auth*',
            'api/login',
            'login',
            'api/debug*',
            'debug*',
            'api/subscription-plans*',
            'subscription-plans*',
            'api/admin*',
            'admin*',
        ];

        foreach ($exemptPatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract tenant ID from the URL path.
     *
     * Looks for patterns like:
     * - /api/tenant/123/...
     * - /tenant/123/...
     * - Any numeric segment after /api/ or /tenant/ prefix
     *
     * @param string $path
     *
     * @return int|null
     */
    private function extractTenantIdFromPath(string $path): ?int
    {
        $segments = explode('/', $path);

        for ($i = 0; $i < count($segments) - 1; $i++) {
            if (($segments[$i] === 'tenant' || $segments[$i] === 'api') && !empty($segments[$i + 1])) {
                $candidate = (int) $segments[$i + 1];

                if ($candidate > 0) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * Generate a forbidden response.
     *
     * @param string $message
     *
     * @return JsonResponse
     */
    private function forbiddenResponse(string $message): JsonResponse
    {
        return response()->json(
            [
                'message' => $message,
                'status' => 'forbidden',
            ],
            Response::HTTP_FORBIDDEN
        );
    }
}
