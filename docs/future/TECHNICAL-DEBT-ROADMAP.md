# Technical Debt Roadmap
**Date**: 2026-06-24  
**Project Phase**: 70% Complete (Phases 1-4 Done)  
**Production Readiness**: 70%

---

## Executive Summary

Hour Ledger has **~15 identified technical debts**, categorized by severity and timeline. None block staging deployment, but **3 are critical for production**. Total remediation: **4-6 weeks**.

### By the Numbers
- 🔴 **Critical** (must fix before production): 2
- 🟠 **High** (fix in 2 weeks): 5
- 🟡 **Medium** (fix in 1 month): 5
- 🟢 **Low** (nice-to-have): 3

---

## 1. CRITICAL DEBTS (Block Production)

### 1.1 No Rate Limiting on Public Endpoints
**Severity**: 🔴 CRITICAL  
**Impact**: Brute force attacks on login/register possible  
**Timeline**: Must fix before production  
**Effort**: 2-3 hours  
**Cost**: Low (just middleware)

**Problem**:
```
POST /auth/login
POST /auth/register
POST /auth/password-recovery/request
```
These endpoints accept unlimited requests. Attacker can:
- Brute force credentials
- DOS register endpoint
- DOS password recovery (spam emails)

**Solution**:
```php
// Add middleware: app/Http/Middleware/RateLimitPublic.php
protected $rateLimiters = [
    'login' => '5,1',           // 5 attempts per minute
    'register' => '3,1',        // 3 per minute
    'password-recovery' => '3,1' // 3 per minute
];
```

**Implementation Steps**:
1. Create middleware in `app/Http/Middleware/RateLimitPublic.php`
2. Register in `app/Http/Kernel.php`
3. Apply to routes in `routes/api.php`
4. Test with load testing tool (Apache Bench)
5. Add tests in `tests/Feature/RateLimitingTest.php`

**Acceptance Criteria**:
- [ ] 6th login attempt returns 429 Too Many Requests
- [ ] Lockout expires after 1 minute
- [ ] Tests passing (5+ test cases)
- [ ] Documentation in API docs

**Priority**: 🔴 **FIX IMMEDIATELY** (before staging)

---

### 1.2 No Comprehensive Deploy Documentation
**Severity**: 🔴 CRITICAL  
**Impact**: First deployment will be ad-hoc, risky, possibly broken  
**Timeline**: Must have before staging  
**Effort**: 8-10 hours  
**Cost**: Low (documentation only)

**Problem**:
- No step-by-step deploy guide
- No environment variable documentation
- No database migration strategy
- No rollback procedure
- No monitoring setup guide
- No incident response playbook

**Solution**: Create `/docs/operations/DEPLOYMENT.md` with:

**File Structure**:
```
docs/operations/
├── DEPLOYMENT.md          # Main deployment guide
├── TROUBLESHOOTING.md     # Common issues + fixes
├── MONITORING.md          # Monitoring setup
├── BACKUPS.md            # Backup strategy
├── INCIDENT_RESPONSE.md  # Incident playbook
└── ENVIRONMENT.md        # Environment variables guide
```

**Content Outline (DEPLOYMENT.md)**:
```markdown
# Deployment Guide

## Prerequisites
- Vercel CLI installed
- PostgreSQL 16 access
- Environment variables prepared

## Staging Deployment (3 days)
1. Setup database
2. Run migrations
3. Deploy backend
4. Deploy frontend
5. Verification checklist

## Production Deployment (1 day)
1. Pre-flight checks
2. Database backup
3. Deploy backend (with monitoring)
4. Deploy frontend
5. Smoke tests
6. Monitoring dashboard

## Rollback Procedure
## Common Errors & Fixes
## Support Contacts
```

**Implementation Steps**:
1. Document all environment variables (`.env.example` breakdown)
2. Write step-by-step staging deployment
3. Write step-by-step production deployment
4. Document rollback procedures
5. Add troubleshooting section (top 10 issues)
6. Add monitoring checklist
7. Review with ops team

**Acceptance Criteria**:
- [ ] New developer can deploy to staging from docs only
- [ ] Rollback procedure documented with examples
- [ ] All env vars documented with defaults
- [ ] Troubleshooting covers top 10 issues
- [ ] Reviewed by ops/infrastructure team

**Priority**: 🔴 **FIX IMMEDIATELY** (need for staging)

---

## 2. HIGH DEBTS (Fix Within 2 Weeks)

### 2.1 No Frontend E2E Tests
**Severity**: 🟠 HIGH  
**Impact**: Regressions not caught, manual testing only  
**Timeline**: 2 weeks (parallel with staging)  
**Effort**: 20-30 hours  
**Cost**: Medium (setup + test writing)

**Problem**:
- Frontend tested only manually
- No Cypress/Playwright setup
- No CI/CD integration for frontend
- E2E tests not running in pipeline
- High regression risk on releases

**Solution**: Add Cypress with 20+ critical tests

**Test Coverage Goals**:
```
✅ Authentication
  - Login flow
  - Register flow
  - Logout
  - Password recovery
  - Session persistence

✅ Multi-Tenancy
  - Switch tenant
  - Tenant isolation (cannot see other tenant data)
  - Tenant context persists

✅ Wallet & Ledger
  - View wallet balance
  - Create credit entry
  - Create debit entry
  - Balance updates after entry
  - History shows new entry

✅ Instructor Links (Phase 3)
  - Create invitation
  - Accept invitation
  - Reject invitation
  - Switch instructor context
  - Can't see other instructor data

✅ UI Components
  - Button clicks work
  - Form validation
  - Modal opens/closes
  - Dropdowns work
  - Pagination works
```

**Implementation Steps**:
1. Install Cypress: `npm install -D cypress`
2. Generate baseline config
3. Write 5 authentication tests
4. Write 5 multi-tenancy tests
5. Write 5 wallet tests
6. Write 5 instructor-link tests
7. Add GitHub Actions workflow
8. Document test running in README

**Acceptance Criteria**:
- [ ] 20+ Cypress tests written
- [ ] All tests passing in local + CI/CD
- [ ] Tests run on every PR
- [ ] Coverage report shows >60% coverage
- [ ] Documentation on writing new tests

**Priority**: 🟠 **FIX IN 2 WEEKS** (before production)

---

### 2.2 Incomplete Modularization
**Severity**: 🟠 HIGH  
**Impact**: Code reuse impossible for HL Consulting, architecture violation  
**Timeline**: 2-3 weeks  
**Effort**: 25-35 hours  
**Cost**: High (significant refactoring)

**Problem**:
- Packages in `/packages/backend/` are empty
- Packages in `/packages/frontend/` are empty
- Core logic is mixed with Drive logic in `apps/hl-drive-api`
- Cannot be reused for HL Consulting (Phase 6)
- Violates modular monolith architecture

**What Should Be Core**:
```
packages/backend/core/
├── Models/
│   ├── User.php          (authentication global)
│   ├── Tenant.php        (multi-tenancy)
│   ├── Wallet.php        (core wallet)
│   ├── LedgerEntry.php   (core ledger)
│   └── ...
├── Services/
│   ├── AuthService.php
│   ├── WalletService.php
│   ├── LedgerService.php
│   └── ...
├── Traits/
│   ├── BelongsToTenant.php
│   └── ...
└── Enums/
    ├── AccessLevel.php
    └── ...

packages/backend/drive/
├── Models/
│   ├── InstructorStudentLink.php  (Drive specific)
│   ├── Invitation.php             (Domain driven)
│   └── ...
├── Services/
│   ├── InstructorLinkService.php
│   └── ...
```

**What Stays in HL Drive App**:
```
apps/hl-drive-api/
├── app/Http/Controllers/     (Drive endpoints)
├── routes/api.php            (Drive routes)
├── config/                   (Drive config)
└── database/
    └── seeders/              (Drive data)
```

**Solution**:
1. Analyze which models are truly Core vs Drive-specific
2. Move Core models to `/packages/backend/core/`
3. Move Drive-specific to `/packages/backend/drive/`
4. Create shared imports in `apps/hl-drive-api`
5. Setup composer autoloading for packages
6. Update all namespace imports
7. Run tests after each move

**Implementation Steps** (per model/service):
1. Identify if Core or Drive-specific
2. Move file to appropriate `/packages/` location
3. Update namespace
4. Update imports in `apps/hl-drive-api`
5. Run tests: `php artisan test`
6. Commit with message

**Acceptance Criteria**:
- [ ] Core models in `/packages/backend/core/Models/`
- [ ] Core services in `/packages/backend/core/Services/`
- [ ] Drive models in `/packages/backend/drive/Models/`
- [ ] All tests passing after move
- [ ] Autoloading working (composer dumpautoload)
- [ ] New HL Consulting app can import from packages
- [ ] Documentation on package structure

**Priority**: 🟠 **FIX IN 2-3 WEEKS** (required for Phase 6)

---

### 2.3 No Cache Layer Implementation
**Severity**: 🟠 HIGH  
**Impact**: Wallet balance recalculated on every request, performance degrades  
**Timeline**: 2 weeks  
**Effort**: 15-20 hours  
**Cost**: Low-Medium (Redis setup + code)

**Problem**:
- Redis available but not used
- Every GET `/api/wallets/{id}/balance` recalculates SUM(ledger_entries)
- With 1000s of ledger entries, query becomes slow
- No caching strategy

**Performance Impact**:
```
Without Cache:
- 100 ledger entries: ~50ms per balance request
- 1000 ledger entries: ~200ms per balance request
- 10000 ledger entries: ~500ms per balance request

With 5-min Cache:
- First request: ~200ms (cache miss)
- Next 300 requests: ~5ms (cache hit)
- Avg: ~6ms (33x faster)
```

**Solution**: Implement Redis cache layer

**Caching Strategy**:
```php
// Cache wallet balance for 5 minutes
// Invalidate on LedgerEntry create

class BalanceCalculatorService {
    public function getWalletBalance(Wallet $wallet): string
    {
        $cacheKey = "wallet.{$wallet->id}.balance";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($wallet) {
            return $wallet->ledgerEntries()
                ->sum('hours');
        });
    }
}

// In LedgerEntry observer
class LedgerEntryObserver {
    public function created(LedgerEntry $entry)
    {
        Cache::forget("wallet.{$entry->wallet_id}.balance");
    }
}
```

**Implementation Steps**:
1. Update `BalanceCalculatorService` to use Cache::remember()
2. Add cache invalidation in `LedgerEntryObserver`
3. Add cache keys constant file
4. Implement cache busting on tag changes
5. Add tests for cache behavior
6. Document cache strategy
7. Add monitoring for cache hit rate

**Acceptance Criteria**:
- [ ] BalanceCalculatorService uses Cache::remember()
- [ ] Cache invalidated on LedgerEntry create/delete
- [ ] Tests verify cache behavior
- [ ] Cache hit rate > 90%
- [ ] Performance test shows 10x improvement
- [ ] Documentation on cache strategy

**Priority**: 🟠 **FIX IN 2 WEEKS** (performance critical)

---

### 2.4 No Centralized Error Logging (Sentry)
**Severity**: 🟠 HIGH  
**Impact**: Bugs in production not tracked, slow debugging  
**Timeline**: 2-3 days  
**Effort**: 8-12 hours  
**Cost**: Medium (Sentry subscription + code)

**Problem**:
- No centralized error tracking
- Errors only in log files on Vercel
- Hard to notice/reproduce production bugs
- No alerting for critical errors
- No error grouping/trends

**Solution**: Integrate Sentry

**Implementation Steps**:
1. Create Sentry project (free tier for small projects)
2. Install `sentry/sentry-laravel`: `composer require sentry/sentry-laravel`
3. Configure in `.env`: `SENTRY_LARAVEL_DSN=https://...`
4. Add to error handler
5. Configure alerts for critical errors
6. Add breadcrumbs for request context
7. Configure sourcemaps for frontend

**Acceptance Criteria**:
- [ ] Sentry configured in backend
- [ ] Test error is sent to Sentry
- [ ] Sentry alerts working for critical errors
- [ ] Team can view errors in Sentry dashboard
- [ ] Documentation on using Sentry

**Priority**: 🟠 **FIX IN 2 WEEKS** (need for production monitoring)

---

### 2.5 Incomplete Error Handling in Frontend
**Severity**: 🟠 HIGH  
**Impact**: Users see generic errors, UX poor  
**Timeline**: 1-2 weeks  
**Effort**: 10-15 hours  
**Cost**: Low

**Problem**:
- API errors often not handled properly
- Generic error messages to user
- No retry logic for transient errors
- Network errors not distinguished from API errors

**Example Problem**:
```vue
// Current: generic error
try {
  await createLedgerEntry()
} catch (error) {
  showToast('Error!') // Too generic
}

// Should be:
try {
  await createLedgerEntry()
} catch (error) {
  if (error.response?.status === 409) {
    showToast('Wallet locked, try again')
  } else if (error.response?.status === 422) {
    showToast('Validation error: ' + error.response.data.message)
  } else if (!error.response) {
    showToast('Network error, retrying...')
  } else {
    showToast('Error: ' + error.response.statusText)
  }
}
```

**Solution**:
1. Create error handling utility (`src/utils/errorHandler.ts`)
2. Map HTTP status codes to user messages
3. Add retry logic for 5xx errors
4. Distinguish network vs API errors
5. Add error context (which action failed)
6. Document common errors

**Implementation Steps**:
1. Create error handler utility
2. Update all API calls to use it
3. Add retry logic with exponential backoff
4. Update error messages in components
5. Add tests for error scenarios

**Acceptance Criteria**:
- [ ] All API errors caught and handled
- [ ] User sees specific error messages
- [ ] Retry logic works for 5xx errors
- [ ] Network errors distinguished
- [ ] Tests for error scenarios

**Priority**: 🟠 **FIX IN 1-2 WEEKS** (UX improvement)

---

## 3. MEDIUM DEBTS (Fix Within 1 Month)

### 3.1 No Two-Factor Authentication (2FA)
**Severity**: 🟡 MEDIUM  
**Impact**: Account compromise = full access to tenant  
**Timeline**: 3-4 weeks  
**Effort**: 20-30 hours  
**Cost**: Medium (TOTP libraries)

**Problem**:
- Single password is only auth factor
- No 2FA/MFA
- If password leaked, account fully compromised

**Solution Options**:
1. **TOTP** (Time-based One-Time Password) — Google Authenticator
2. **SMS** — Twilio integration
3. **Email** — Backup codes

**Recommended**: TOTP (most secure, no infrastructure)

**Implementation**:
```bash
composer require pragmarx/google2fa
composer require pragmarx/qr-code
```

**User Flow**:
```
1. User in settings clicks "Enable 2FA"
2. Backend generates secret
3. Frontend shows QR code
4. User scans with Google Authenticator
5. User enters 6-digit code to confirm
6. Backup codes generated
7. On next login, 2FA prompt after password

Login with 2FA:
1. User enters email + password
2. API returns temp_token (no full token yet)
3. User enters 2FA code
4. API verifies, returns full token
```

**Acceptance Criteria**:
- [ ] 2FA setup page in settings
- [ ] TOTP generation + QR code
- [ ] Backup codes generation
- [ ] Login flow includes 2FA prompt
- [ ] Tests for 2FA flow (10+ tests)
- [ ] Documentation for users

**Priority**: 🟡 **FIX IN 3-4 WEEKS** (security improvement)

---

### 3.2 Incomplete Audit Logging
**Severity**: 🟡 MEDIUM  
**Impact**: Cannot audit who did what, compliance issues  
**Timeline**: 2-3 weeks  
**Effort**: 15-20 hours  
**Cost**: Low

**Problem**:
- Basic activity logging exists
- Not complete across all endpoints
- No structured logging
- Hard to query audit trail

**Solution**:
1. Create `AuditLog` model
2. Add logging to all CRUD operations
3. Capture: user_id, action, resource, before/after values
4. Index by created_at for quick queries
5. Add UI for audit trail viewing (admin only)

**What to Log**:
```php
AuditLog::create([
    'user_id' => $user->id,
    'tenant_id' => $tenantId,
    'action' => 'ledger.entry.created',        // action type
    'resource_type' => 'LedgerEntry',         // entity type
    'resource_id' => $entry->id,              // entity ID
    'old_values' => null,                     // before
    'new_values' => $entry->toArray(),        // after
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);
```

**Acceptance Criteria**:
- [ ] All CRUD operations logged
- [ ] Audit log queryable by user/tenant/action
- [ ] UI showing audit trail (admin)
- [ ] Tests for audit logging (10+ tests)
- [ ] Documentation on audit schema

**Priority**: 🟡 **FIX IN 2-3 WEEKS** (compliance)

---

### 3.3 No API Pagination for Large Lists
**Severity**: 🟡 MEDIUM  
**Impact**: API returns all results, memory issues with large datasets  
**Timeline**: 2 weeks  
**Effort**: 10-15 hours  
**Cost**: Low

**Problem**:
- Some endpoints return all results (no pagination)
- Reports endpoint can return 10k+ rows
- Import history can have 1000+ rows
- No cursor/offset pagination

**Endpoints Missing Pagination**:
- GET `/api/reports`
- GET `/api/reports/by-wallet`
- GET `/api/import-plans`
- GET `/api/import-plan-rows`

**Solution**:
```php
// Use Laravel's built-in pagination
public function index(Request $request)
{
    return Report::paginate($request->get('per_page', 15));
}

// Frontend
const { data, total, current_page, per_page } = await fetchReports({
    page: 1,
    per_page: 25
})
```

**Acceptance Criteria**:
- [ ] All list endpoints paginated
- [ ] Default per_page = 15
- [ ] Max per_page = 100
- [ ] Tests for pagination (5+ tests)
- [ ] Frontend pagination UI

**Priority**: 🟡 **FIX IN 2 WEEKS** (performance)

---

### 3.4 Incomplete Timezone Support
**Severity**: 🟡 MEDIUM  
**Impact**: Time-based bugs with multi-timezone users  
**Timeline**: 2-3 weeks  
**Effort**: 15-20 hours  
**Cost**: Low

**Problem**:
- Timezone partially implemented
- LedgerEntry has `reference_date_timezone` but not all endpoints validate
- Reports might show wrong times
- Scheduling (for future phases) will be broken

**Where Needed**:
- Wallet creation (store timezone preference)
- LedgerEntry creation (validate timezone)
- Reports (convert to user timezone)
- Timers (store in user timezone)
- Scheduling (future phases)

**Solution**:
```php
// User timezone preference
$user->timezone // 'America/New_York'

// When creating ledger entry
$entry->reference_date_timezone = Auth::user()->timezone;

// In reports, convert to user timezone
$entry->reference_date->setTimezone(Auth::user()->timezone);
```

**Acceptance Criteria**:
- [ ] User timezone stored in preferences
- [ ] All date operations use user timezone
- [ ] Tests verify timezone correctness (10+ tests)
- [ ] Reports display in user timezone

**Priority**: 🟡 **FIX IN 2-3 WEEKS** (data correctness)

---

### 3.5 TypeScript Strictness (some `any` types remain)
**Severity**: 🟡 MEDIUM  
**Impact**: Runtime errors possible, type safety reduced  
**Timeline**: 3-4 weeks  
**Effort**: 20-25 hours  
**Cost**: Low

**Problem**:
- Some functions have `any` types
- Not all API responses typed
- State management has loose typing
- Reducers uses `any`

**Solution**:
1. Run `vue-tsc --strict --noEmit` to find all
2. Add proper types for each:
   ```typescript
   // Before: any
   const apiResponse: any = await fetch(...)
   
   // After: specific type
   interface WalletResponse {
     id: number
     name: string
     balance: string
   }
   const apiResponse: WalletResponse = await fetch(...)
   ```
3. Update type files in `src/types/`
4. Update store types
5. Run type check in CI/CD

**Acceptance Criteria**:
- [ ] No `any` types in codebase
- [ ] `vue-tsc --strict` passes
- [ ] All API responses typed
- [ ] Type checking in CI/CD

**Priority**: 🟡 **FIX IN 3-4 WEEKS** (optional, nice-to-have)

---

## 4. LOW DEBTS (Can Wait, Nice-to-Have)

### 4.1 Pre-commit Hooks (Lint/Format Automation)
**Severity**: 🟢 LOW  
**Timeline**: 2-3 weeks (when not busy)  
**Effort**: 5-8 hours  
**Implementation**: Husky + lint-staged

### 4.2 Improved Code Documentation (JSDoc/PHPDoc)
**Severity**: 🟢 LOW  
**Timeline**: Ongoing  
**Effort**: 20+ hours

### 4.3 CSS Optimization (Tailwind Purge)
**Severity**: 🟢 LOW  
**Timeline**: 1-2 weeks  
**Effort**: 3-5 hours

### 4.4 Test Coverage Dashboard
**Severity**: 🟢 LOW  
**Timeline**: After frontend tests done  
**Effort**: 5-8 hours

### 4.5 API Documentation (OpenAPI/Swagger)
**Severity**: 🟢 LOW  
**Timeline**: 3-4 weeks  
**Effort**: 20+ hours

---

## Remediation Timeline

### Week 1 (Immediate)
- [ ] Rate limiting on public endpoints (2-3h)
- [ ] Deploy documentation (8-10h)
- [ ] Sentry integration (8-12h)

### Weeks 2-3
- [ ] Frontend E2E tests (20-30h parallel)
- [ ] Cache layer (15-20h)
- [ ] Error handling improvements (10-15h)

### Weeks 4-6
- [ ] Modularization (25-35h)
- [ ] 2FA implementation (20-30h)
- [ ] Audit logging completion (15-20h)
- [ ] API pagination (10-15h)

### Optional (Weeks 7+)
- [ ] TypeScript strictness
- [ ] Pre-commit hooks
- [ ] API documentation
- [ ] Additional testing/coverage

---

## Summary Table

| Debt | Severity | Timeline | Effort | Blocker? |
|------|----------|----------|--------|----------|
| Rate limiting | 🔴 | Week 1 | 2-3h | YES (prod) |
| Deploy docs | 🔴 | Week 1 | 8-10h | YES (prod) |
| Frontend E2E tests | 🟠 | Weeks 2-3 | 20-30h | YES (prod) |
| Modularization | 🟠 | Weeks 4-6 | 25-35h | YES (Phase 6) |
| Cache layer | 🟠 | Weeks 2-3 | 15-20h | NO |
| Error logging (Sentry) | 🟠 | Week 1-2 | 8-12h | NO |
| Error handling frontend | 🟠 | Weeks 2-3 | 10-15h | NO |
| 2FA | 🟡 | Weeks 4-6 | 20-30h | NO |
| Audit logging | 🟡 | Weeks 4-6 | 15-20h | NO |
| API pagination | 🟡 | Weeks 2-4 | 10-15h | NO |
| Timezone support | 🟡 | Weeks 3-4 | 15-20h | NO |
| TypeScript strictness | 🟡 | Weeks 5-6 | 20-25h | NO |
| Pre-commit hooks | 🟢 | After | 5-8h | NO |
| Code docs | 🟢 | Ongoing | 20+ | NO |
| CSS optimization | 🟢 | After | 3-5h | NO |

---

## Impact Analysis

### Without Fixing Critical Debts
- ❌ Cannot go to production
- ❌ First deployment will fail or be very risky
- ❌ Brute force attacks possible
- ❌ No way to troubleshoot issues

### After Fixing Critical Debts (Week 1)
- ✅ Can deploy to staging
- ✅ Team can deploy safely
- ✅ Can monitor in production
- ⚠️ Still need frontend tests + modularization before full production

### After Fixing High Debts (Weeks 2-6)
- ✅ Production-ready
- ✅ Fully tested
- ✅ Can start Phase 6 (HL Consulting)
- ✅ No blocking issues

---

## Recommendation

**Short Term** (Week 1 - must do before staging):
1. ✅ Rate limiting
2. ✅ Deploy documentation

**Medium Term** (Weeks 2-6 - must do before full production):
1. ✅ Frontend E2E tests
2. ✅ Cache layer
3. ✅ Modularization
4. ✅ Error logging (Sentry)

**Long Term** (Weeks 7+ - nice-to-have):
1. ✅ 2FA
2. ✅ Audit logging
3. ✅ TypeScript strictness
4. ✅ Pre-commit hooks

---

**Next Steps**: Pick items from "Short Term" and create JIRA tickets for team.

Created: 2026-06-24  
Status: Ready for Implementation
