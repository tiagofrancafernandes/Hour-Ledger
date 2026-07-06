# Task 8: Test Tenant Selection Composable

**Goal:** Create Vitest unit tests for useTenantSelection composable.

**Files to create:**
- `apps/hl-drive-web/src/composables/__tests__/useTenantSelection.spec.ts`

**Exact test code:**

```typescript
import { describe, it, expect, beforeEach, vi } from 'vitest';
import { useTenantSelection } from '../useTenantSelection';
import { useAuthStore } from '~/stores/auth';
import { useRouter } from 'vue-router';
import type { Tenant } from '~/types/tenant';

vi.mock('~/stores/auth');
vi.mock('vue-router');

describe('useTenantSelection', () => {
  let authStoreMock: any;
  let routerMock: any;

  beforeEach(() => {
    authStoreMock = {
      setActiveTenant: vi.fn(),
      logout: vi.fn(),
    };
    routerMock = {
      push: vi.fn(),
    };
    vi.mocked(useAuthStore).mockReturnValue(authStoreMock);
    vi.mocked(useRouter).mockReturnValue(routerMock);
  });

  it('shows selector for multiple tenants', () => {
    const { handleAccessibleTenants, showSelector } = useTenantSelection();

    const tenants: Tenant[] = [
      { id: 1, name: 'T1', role: 'admin' },
      { id: 2, name: 'T2', role: 'member' },
    ];

    handleAccessibleTenants(tenants);

    expect(showSelector.value).toBe(true);
  });

  it('auto-selects single tenant', () => {
    const { handleAccessibleTenants, showSelector } = useTenantSelection();

    const tenants: Tenant[] = [
      { id: 1, name: 'T1', role: 'admin' },
    ];

    handleAccessibleTenants(tenants);

    expect(showSelector.value).toBe(false);
    expect(authStoreMock.setActiveTenant).toHaveBeenCalledWith(tenants[0]);
    expect(routerMock.push).toHaveBeenCalledWith('/');
  });

  it('throws error for no accessible tenants', () => {
    const { handleAccessibleTenants } = useTenantSelection();

    expect(() => {
      handleAccessibleTenants([]);
    }).toThrow('No accessible tenants');
  });

  it('cancels selection and logs out', () => {
    const { cancelSelection } = useTenantSelection();

    cancelSelection();

    expect(authStoreMock.logout).toHaveBeenCalled();
  });
});
```

**Steps:**
1. Create test file with exact code above
2. Run `npm run typecheck` to verify types
3. Commit: `test: add tenant selection composable tests`

**Report to:** `/mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/.superpowers/sdd/task-8-report.md`
