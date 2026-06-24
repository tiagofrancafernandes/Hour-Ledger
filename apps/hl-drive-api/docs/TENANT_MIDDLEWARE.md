# Tenant Middleware & Resolver Documentation

This document explains the tenant resolution system for multi-tenancy in the Hour Ledger Ecosystem.

## Overview

The TenantMiddleware and TenantResolver work together to:

1. **Detect** which tenant is being accessed in each request
2. **Validate** that the tenant exists and is active
3. **Provide** access to tenant information throughout the request lifecycle
4. **Enforce** authorization rules to prevent cross-tenant access

## Architecture

### Components

#### 1. TenantResolver (Singleton Service)

Located: `app/Services/TenantResolver.php`

A singleton service that manages the active tenant context during a request.

**Key Methods:**
- `setTenantId(int $tenantId, ?int $userId = null)` — Set active tenant
- `getTenantId(): ?int` — Get current tenant ID
- `getSchema(): string` — Get database schema name (e.g., `tenant_123_prod`)
- `hasTenant(): bool` — Check if tenant is set
- `getContext(): TenantContext` — Get full tenant context object
- `getUserId(): ?int` — Get authenticated user ID
- `clear(): void` — Clear context (called automatically at end of request)

**Example:**
```php
$resolver = app(TenantResolver::class);
$resolver->setTenantId(123, auth()->id());

echo $resolver->getSchema(); // "tenant_123_prod"
echo $resolver->getTenantId(); // 123
```

#### 2. TenantMiddleware

Located: `app/Http/Middleware/TenantMiddleware.php`

HTTP middleware that resolves the active tenant for each request.

**Registration:** Automatically registered in `bootstrap/app.php` as API middleware

**Tenant Detection Order:**
1. `X-Tenant-ID` header
2. `tenant` query parameter
3. URL path (e.g., `/api/tenant/123/...`)

**Validations:**
- Tenant must exist in database
- Tenant must be in ACTIVE status
- Returns 403 Forbidden if any validation fails

**Error Responses:**
```json
{
    "message": "Tenant not found.",
    "status": "forbidden"
}
```

#### 3. TenantContext (Data Object)

Located: `app/Models/TenantContext.php`

Immutable data object representing the current tenant context.

**Properties:**
- `tenantId: int` — The tenant ID
- `schema: string` — The database schema name
- `userId: ?int` — Authenticated user ID (if available)
- `timestamp: Carbon` — When context was created

**Methods:**
- `toArray(): array` — Convert to array
- `jsonSerialize(): array` — JSON serializable
- `__toString(): string` — String representation for logging

#### 4. Exception Classes

**TenantNotFound** (`app/Exceptions/TenantNotFound.php`)
- Thrown when tenant ID doesn't exist in database
- HTTP Status: 403 Forbidden

**TenantNotActive** (`app/Exceptions/TenantNotActive.php`)
- Thrown when tenant is suspended or deleted
- HTTP Status: 403 Forbidden

**UnauthorizedTenant** (`app/Exceptions/UnauthorizedTenant.php`)
- Reserved for future use: authorization checks
- HTTP Status: 403 Forbidden

## Usage Guide

### 1. Basic Usage in Controllers

```php
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Services\TenantResolver;

class MyController extends Controller
{
    public function index(Request $request)
    {
        $resolver = app(TenantResolver::class);
        
        // Tenant ID is automatically set by middleware
        $tenantId = $resolver->getTenantId();
        $schema = $resolver->getSchema();
        
        // Use in queries
        \DB::setDatabaseName($schema);
        
        return response()->json([
            'tenant_id' => $tenantId,
            'data' => Model::all(),
        ]);
    }
}
```

### 2. Using Helper Functions

```php
<?php

use App\Helpers\TenantHelper;

// Get current tenant ID
$tenantId = TenantHelper::tenantId();

// Get schema name
$schema = TenantHelper::tenantSchema();

// Check if tenant is set
if (TenantHelper::hasTenant()) {
    $context = TenantHelper::tenantContext();
}

// Get authenticated user ID
$userId = TenantHelper::tenantUserId();
```

### 3. Via Request Attributes

The middleware automatically sets request attributes:

```php
<?php

public function index(Request $request)
{
    $tenantId = $request->attributes->get('tenant_id');
    $schema = $request->attributes->get('tenant_schema');
    $context = $request->attributes->get('tenant_context');
    
    // ...
}
```

### 4. In Services and Models

```php
<?php

namespace App\Services;

use App\Services\TenantResolver;

class MyService
{
    public function __construct(
        private TenantResolver $resolver
    ) {
    }

    public function doSomething()
    {
        $tenantId = $this->resolver->getTenantId();
        $schema = $this->resolver->getSchema();
        
        // Use tenant information...
    }
}
```

## Tenant Detection Examples

### Example 1: Header Detection

```bash
curl -X GET http://localhost:8000/api/wallets \
  -H "X-Tenant-ID: 123" \
  -H "Authorization: Bearer token"
```

The middleware extracts tenant ID `123` from the header.

### Example 2: Query Parameter Detection

```bash
curl -X GET "http://localhost:8000/api/wallets?tenant=123" \
  -H "Authorization: Bearer token"
```

The middleware extracts tenant ID `123` from query parameter.

### Example 3: URL Path Detection

```bash
curl -X GET "http://localhost:8000/api/tenant/123/wallets" \
  -H "Authorization: Bearer token"
```

The middleware extracts tenant ID `123` from URL path segment.

### Example 4: No Tenant Specified (Error)

```bash
curl -X GET http://localhost:8000/api/wallets \
  -H "Authorization: Bearer token"
```

Response:
```json
{
    "message": "No tenant specified.",
    "status": "forbidden"
}
```

## Request Lifecycle

### 1. Request Arrives

HTTP request comes in with tenant identification (header, query, or URL).

### 2. Middleware Processes

1. Detects tenant ID from request
2. Queries database for tenant record
3. Validates tenant is ACTIVE
4. Creates TenantResolver context
5. Sets request attributes

### 3. Controller Handles Request

1. Accesses tenant info via TenantResolver or request attributes
2. Uses schema name for database operations
3. Applies tenant-specific logic

### 4. Response Sent

Middleware/framework sends response to client.

### 5. Context Cleared

TenantResolver context is cleared (via `clear()` method) for next request.

## Schema Naming Convention

Tenant database schemas follow this pattern:

```
tenant_{tenant_id}_{environment}
```

**Example:**
- Tenant ID: 123
- Environment: prod
- Schema Name: `tenant_123_prod`

This allows multiple environments to coexist in the same database cluster.

## Error Handling

### TenantNotFound

Thrown when:
- Tenant ID doesn't exist in `tenants` table
- Trying to access schema/context without active tenant

```php
try {
    $resolver->setTenantId(99999);
} catch (TenantNotFound $e) {
    // Handle error
    return response()->json(['error' => $e->getMessage()], 403);
}
```

### TenantNotActive

Thrown when:
- Tenant status is SUSPENDED
- Tenant status is DELETED

```php
try {
    $resolver->setTenantId($suspendedTenant->id);
} catch (TenantNotActive $e) {
    // Handle error: "Tenant 123 is not active. Current status: Suspenso."
}
```

## Testing

### Running Tenant Tests

```bash
php artisan test tests/Feature/TenantResolutionTest.php
```

### Test Coverage

The `TenantResolutionTest` class covers:

- Middleware detection from header
- Middleware detection from query parameter
- Middleware detection from URL path
- 403 Forbidden responses for invalid/suspended tenants
- TenantResolver schema resolution
- TenantResolver context creation
- TenantContext serialization
- Exception handling

## Security Considerations

1. **Always validate tenant access** — The middleware ensures only active tenants are accessible
2. **User isolation** — By default, any authenticated user can access any tenant (future: add user-tenant authorization)
3. **Schema isolation** — Each tenant has its own database schema for data isolation
4. **Cross-tenant protection** — All requests must specify a tenant; no "global" access is allowed

## Future Enhancements

Planned features:
- User-Tenant authorization (user must belong to tenant)
- Tenant quota enforcement
- Audit logging of tenant access
- Tenant activity rate limiting
- Soft-deleted tenant handling

## Related Files

- `app/Services/TenantResolver.php` — Main resolver service
- `app/Http/Middleware/TenantMiddleware.php` — Middleware implementation
- `app/Models/TenantContext.php` — Context data object
- `app/Models/Tenant.php` — Tenant model
- `app/Enums/TenantStatus.php` — Tenant status enum
- `app/Exceptions/TenantNotFound.php` — Exception
- `app/Exceptions/TenantNotActive.php` — Exception
- `app/Exceptions/UnauthorizedTenant.php` — Exception
- `app/Helpers/TenantHelper.php` — Helper functions
- `tests/Feature/TenantResolutionTest.php` — Test suite
- `bootstrap/app.php` — Middleware registration

## Configuration

### Middleware Registration

The TenantMiddleware is registered in `bootstrap/app.php`:

```php
$middleware->api(
    append: [
        \App\Http\Middleware\TenantMiddleware::class,
    ]
);
```

**Important:** The middleware is appended to the API middleware group, so it runs after authentication middleware.

### Environment Configuration

The TenantResolver uses the current environment for schema naming:

```php
$resolver = app(TenantResolver::class);
$resolver->setEnvironment('prod'); // or 'staging', 'dev'
```

Default: `prod` (set in TenantResolver constructor)
