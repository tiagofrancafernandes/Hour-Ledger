# Task 3: Create Tenant Selection Composable

**Goal:** Create a `useTenantSelection()` composable that handles the logic for tenant selection: auto-selects if 1 tenant, shows modal if 2+, throws error if 0.

**Context:** Used by LoginView after successful authentication. Processes `accessible_tenants` from login response and decides whether to auto-select or show selector modal.

**Files to create:**
- `apps/hl-drive-web/src/composables/useTenantSelection.ts`

**Exact code to create:**

```typescript
// apps/hl-drive-web/src/composables/useTenantSelection.ts

import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import type { Tenant } from '~/types/tenant';
import { useAuthStore } from '~/stores/auth';

export function useTenantSelection() {
  const authStore = useAuthStore();
  const router = useRouter();

  const showSelector = ref(false);
  const isLoading = ref(false);
  const accessibleTenants = ref<Tenant[]>([]);

  // Process tenants after login
  // Auto-select if 1, show modal if 2+, error if 0
  const handleAccessibleTenants = (tenants: Tenant[]) => {
    accessibleTenants.value = tenants;

    if (tenants.length === 0) {
      throw new Error('No accessible tenants. Please contact an administrator.');
    }

    if (tenants.length === 1) {
      // Auto-select single tenant
      selectTenant(tenants[0]);
    } else {
      // Show selector for multiple tenants
      showSelector.value = true;
    }
  };

  const selectTenant = (tenant: Tenant) => {
    authStore.setActiveTenant(tenant);
    showSelector.value = false;
    
    // Navigate to dashboard
    router.push('/');
  };

  const cancelSelection = () => {
    showSelector.value = false;
    authStore.logout();
  };

  return {
    showSelector,
    isLoading,
    accessibleTenants,
    handleAccessibleTenants,
    selectTenant,
    cancelSelection,
  };
}
```

**Steps:**
1. Create file `apps/hl-drive-web/src/composables/useTenantSelection.ts` with exact code above
2. Run `npm run typecheck` to verify no errors
3. Commit with message: `feat: create tenant selection composable`

**What to report:**
- Status: DONE / NEEDS_CONTEXT / DONE_WITH_CONCERNS / BLOCKED
- Commits: list of commit SHA7 created
- Test output: typecheck result
- Concerns: any issues found during implementation
