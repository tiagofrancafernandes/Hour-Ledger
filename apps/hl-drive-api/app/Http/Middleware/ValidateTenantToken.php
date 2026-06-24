<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ValidateTenantToken Middleware.
 *
 * Validates that tokens limited to specific tenants can only be used
 * to access resources in that tenant.
 *
 * Checks:
 * 1. User is authenticated
 * 2. If token has tenant_id, request must include matching X-Tenant-ID
 * 3. Token's tenant_id matches request's X-Tenant-ID
 *
 * Returns 403 Forbidden if validation fails.
 *
 * Apply to routes that require tenant context.
 */
class ValidateTenantToken
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get authenticated user
        $user = $request->user();

        // If not authenticated, let auth middleware handle it
        if (!$user) {
            return $next($request);
        }

        // Get current access token
        $token = $user->currentAccessToken();

        // If no token, let auth middleware handle it
        if (!$token) {
            return $next($request);
        }

        // Check if token is limited to a specific tenant
        $tokenTenantId = $token->tenant_id;

        if ($tokenTenantId) {
            // Token is limited to a tenant
            // Request must include X-Tenant-ID header
            $requestTenantId = $request->header('X-Tenant-ID');

            if (!$requestTenantId) {
                return response()->json([
                    'message' => 'This token requires X-Tenant-ID header',
                ], 400);
            }

            // Convert to integer for comparison
            $requestTenantId = (int) $requestTenantId;

            // Verify token's tenant_id matches request's tenant_id
            if ($tokenTenantId !== $requestTenantId) {
                return response()->json([
                    'message' => 'Token is limited to a different tenant',
                ], 403);
            }
        }

        return $next($request);
    }
}
