# Backend Auth Tenant Tests Plan

**Goal:** Create comprehensive tests for AuthController login endpoint to verify tenant_id handling and multi-tenant authentication flows.

**Architecture:** Test all scenarios: global login (no tenant_id), scoped login (with valid tenant_id), invalid tenant_id, no accessible tenants, and response structure validation.

**Tech Stack:** Laravel 12, Pest/PHPUnit, PostgreSQL

## Global Constraints

- No backend changes needed — AuthController already implements tenant_id support
- Tests must follow existing Laravel test patterns in project
- Tests must use Pest or PHPUnit depending on project setup
- All tests must pass with clean database state
- Test isolation: each test uses fresh user/tenant fixtures

---

## Task 1: Verify Test Framework Setup

**Files to check:**
- `apps/hl-drive-api/phpunit.xml`
- `apps/hl-drive-api/tests/Feature/Auth/LoginTest.php` (if exists)
- `apps/hl-drive-api/tests/TestCase.php`

**Steps:**
1. Verify which test framework is configured (Pest vs PHPUnit)
2. Check existing auth tests structure
3. Understand test database setup

**Report:** Framework identified, existing patterns documented

---

## Task 2: Create AuthController Tenant Login Tests

**Files to create:**
- `apps/hl-drive-api/tests/Feature/Auth/AuthControllerTenantTest.php`

**Test Scenarios:**

1. **Login Without Tenant ID (Global Token)**
   - User logs in with email + password only
   - No tenant_id provided
   - Response includes accessible_tenants array
   - Token is created as global (no tenant_id in token)

2. **Login With Valid Tenant ID (Scoped Token)**
   - User logs in with email + password + valid tenant_id
   - User has access to that tenant
   - Response includes token
   - Token is saved with tenant_id in database

3. **Login With Invalid Tenant ID (No Access)**
   - User logs in with email + password + tenant_id where user has NO access
   - Should return 403 Forbidden or validation error
   - Token should NOT be created

4. **Login With Non-Existent Tenant ID**
   - User tries to login with tenant_id that doesn't exist
   - Should return validation error
   - Token should NOT be created

5. **Login With Valid Tenant ID (User No Accessible Tenants)**
   - User has no tenant associations
   - Login without tenant_id should succeed
   - accessible_tenants should be empty array
   - But should succeed (not error)

6. **Response Structure Validation**
   - Verify response includes: user, role, permissions, accessible_tenants, token
   - Verify accessible_tenants format: [{ id, name, role }, ...]
   - Verify user object format

**Exact Test Code Structure:**

```php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Tenant;
use App\Models\UserTenant;
use Tests\TestCase;

class AuthControllerTenantTest extends TestCase
{
    protected User $user;
    protected Tenant $tenant1;
    protected Tenant $tenant2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Create test tenants
        $this->tenant1 = Tenant::factory()->create(['name' => 'Tenant 1']);
        $this->tenant2 = Tenant::factory()->create(['name' => 'Tenant 2']);

        // Associate user with tenant1 only
        UserTenant::factory()->create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->tenant1->id,
            'role' => 'admin',
        ]);
    }

    public function test_login_without_tenant_id_returns_global_token()
    {
        // Test global login
    }

    public function test_login_with_valid_tenant_id_returns_scoped_token()
    {
        // Test scoped login
    }

    public function test_login_with_invalid_tenant_id_returns_error()
    {
        // Test forbidden access
    }

    public function test_response_includes_accessible_tenants()
    {
        // Verify response structure
    }
}
```

**Steps:**
1. Create test file with fixture setup (users, tenants, associations)
2. Implement each test scenario
3. Run tests and verify all pass
4. Check test coverage for AuthController::login()

**Report:** Test file created, all scenarios passing, coverage verified

---

## Task 3: Run Tests and Verify Coverage

**Files to modify:**
- May need to adjust `phpunit.xml` if database setup needed

**Steps:**
1. Run: `php artisan test tests/Feature/Auth/AuthControllerTenantTest.php --verbose`
2. Verify all tests pass
3. Check test output for any failures
4. Document any setup issues found

**Report:** Test results, pass/fail status, any setup issues

---

## Files Summary

**To Create:**
- `tests/Feature/Auth/AuthControllerTenantTest.php` — Main test file with 5-6 test methods

**No backend changes** — AuthController already implements everything

## Success Criteria

- ✅ All 5+ test scenarios implemented
- ✅ Tests execute without errors
- ✅ Global login (no tenant_id) works
- ✅ Scoped login (with tenant_id) works
- ✅ Invalid tenant_id returns error
- ✅ Response structure correct
- ✅ accessible_tenants populated correctly

---

**Status:** Ready for implementation via Subagent-Driven Development
