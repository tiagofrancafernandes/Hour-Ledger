# Tenant Selection Frontend Integration (V1 Minimalista)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Integrate tenant selection into login flow on frontend only. Auto-select single tenant, show modal if multiple. Backend unchanged.

**Architecture:** After successful global login, frontend receives `accessible_tenants` from backend. Decision logic: 0 tenants → error, 1 tenant → auto-select & proceed, 2+ tenants → show modal. Auth store tracks active tenant. Aligns with V1 focus on single-instructor use case.

**Tech Stack:** Vue 3 + Nuxt, Pinia (auth store), UButton/UModal (Nuxt UI), TypeScript

**Rationale:** V1 is optimized for a single instructor managing one business (one tenant). Multi-tenant complexity is future-proofed but not required now. Backend already returns tenant list; frontend just needs to use it intelligently.

## Global Constraints

- Multi-tenant architecture already in backend (no changes needed)
- Login endpoint already accepts optional `tenant_id` (unused for now)
- Frontend uses Pinia auth store + Nuxt routing
- Must maintain backwards compatibility with existing single-tenant flow
- Commits use conventional format (feat:, fix:, test:, etc.)
- No backend changes in this plan
- Simplicity over sophistication (per project constitution)

---

## File Structure

**Create:**
- `apps/hl-drive-web/src/types/tenant.ts` — Tenant type definitions
- `apps/hl-drive-web/src/components/TenantSelector.vue` — Selection modal (simple)
- `apps/hl-drive-web/src/composables/useTenantSelection.ts` — Selection logic
- `apps/hl-drive-web/src/components/__tests__/TenantSelector.spec.ts` — Component tests
- `apps/hl-drive-web/src/composables/__tests__/useTenantSelection.spec.ts` — Composable tests

**Modify:**
- `apps/hl-drive-web/src/stores/auth.ts` — Add tenant state + setters
- `apps/hl-drive-web/src/views/LoginView.vue` — Integrate selector modal
- `apps/hl-drive-web/src/types/index.ts` — Export Tenant types

---

## Task 1: Define Tenant Types

**Files:**
- Create: `apps/hl-drive-web/src/types/tenant.ts`

**Produces:** `Tenant`, `LoginResponse` types used by auth store and selector

- [ ] **Step 1: Create tenant types file**

```typescript
// apps/hl-drive-web/src/types/tenant.ts

export type TenantRole = 'admin' | 'instructor' | 'student' | 'member';

export interface Tenant {
  id: number;
  name: string;
  role: TenantRole;
  status?: 'active' | 'inactive';
  created_at?: string;
}

export interface LoginResponse {
  user: {
    id: number;
    email: string;
    name?: string;
  };
  token: string;
  role: string;
  permissions: string[];
  accessible_tenants: Tenant[];
}
```

- [ ] **Step 2: Run type check**

```bash
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web
npm run typecheck
```

Expected: No errors

- [ ] **Step 3: Commit**

```bash
git add src/types/tenant.ts
git commit -m "feat: define tenant and login response types"
```

---

## Task 2: Create Tenant Selector Component

**Files:**
- Create: `apps/hl-drive-web/src/components/TenantSelector.vue`

**Produces:** Simple modal component for tenant selection

- [ ] **Step 1: Create component**

```vue
<template>
  <UModal :model-value="isOpen" title="Select Your Workspace" @update:model-value="$emit('update:isOpen', $event)">
    <div class="space-y-4">
      <p class="text-sm text-gray-600 dark:text-gray-400">
        You have access to multiple workspaces. Please select one to continue:
      </p>

      <div class="space-y-2">
        <button
          v-for="tenant in tenants"
          :key="tenant.id"
          type="button"
          :class="[
            'w-full px-4 py-3 text-left rounded-lg border-2 transition-all',
            selectedTenantId === tenant.id
              ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
              : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600',
          ]"
          @click="selectedTenantId = tenant.id"
        >
          <div class="flex items-center justify-between">
            <div>
              <h3 class="font-medium text-gray-900 dark:text-white">
                {{ tenant.name }}
              </h3>
              <p class="text-xs text-gray-500 dark:text-gray-400 capitalize">
                {{ tenant.role }}
              </p>
            </div>
            <div v-if="selectedTenantId === tenant.id" class="text-blue-500">
              <iconify-icon icon="fa7-solid:check-circle" />
            </div>
          </div>
        </button>
      </div>

      <div class="flex justify-end gap-2">
        <UButton
          color="gray"
          variant="ghost"
          @click="$emit('cancel')"
        >
          Cancel
        </UButton>
        <UButton
          :disabled="!selectedTenantId || isLoading"
          :loading="isLoading"
          @click="handleConfirm"
        >
          Continue
        </UButton>
      </div>
    </div>
  </UModal>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue';
import type { Tenant } from '~/types/tenant';

interface Props {
  tenants: Tenant[];
  isOpen: boolean;
  isLoading?: boolean;
}

withDefaults(defineProps<Props>(), {
  isLoading: false,
});

const emit = defineEmits<{
  'update:isOpen': [value: boolean];
  'selected': [tenant: Tenant];
  'cancel': [];
}>();

const selectedTenantId = ref<number | null>(null);

const selectedTenant = computed(() => {
  return props.tenants.find(t => t.id === selectedTenantId.value);
});

const handleConfirm = () => {
  if (selectedTenant.value) {
    emit('selected', selectedTenant.value);
  }
};
</script>
```

- [ ] **Step 2: Verify component renders**

```bash
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web
npm run typecheck
```

Expected: No errors

- [ ] **Step 3: Commit**

```bash
git add src/components/TenantSelector.vue
git commit -m "feat: create tenant selector component"
```

---

## Task 3: Create Tenant Selection Composable

**Files:**
- Create: `apps/hl-drive-web/src/composables/useTenantSelection.ts`

**Consumes:** Auth store, Tenant type
**Produces:** Composable with selection logic (auto-select vs. modal)

- [ ] **Step 1: Create composable**

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

- [ ] **Step 2: Commit**

```bash
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web
git add src/composables/useTenantSelection.ts
git commit -m "feat: create tenant selection composable"
```

---

## Task 4: Update Auth Store

**Files:**
- Modify: `apps/hl-drive-web/src/stores/auth.ts`

**Consumes:** Tenant type
**Produces:** Auth store with tenant state

- [ ] **Step 1: Read current auth store**

```bash
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web
head -100 src/stores/auth.ts
```

- [ ] **Step 2: Add tenant state to auth store**

Find the `defineStore` definition and add to state:

```typescript
const state = reactive({
  // ... existing properties (user, token, etc.)
  accessibleTenants: [] as Tenant[],
  activeTenant: null as Tenant | null,
});

const setAccessibleTenants = (tenants: Tenant[]) => {
  state.accessibleTenants = tenants;
};

const setActiveTenant = (tenant: Tenant) => {
  state.activeTenant = tenant;
};

const getActiveTenantId = computed(() => {
  return state.activeTenant?.id ?? null;
});
```

- [ ] **Step 3: Verify store compiles**

```bash
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web
npm run typecheck
```

Expected: No errors

- [ ] **Step 4: Commit**

```bash
git add src/stores/auth.ts
git commit -m "feat: add tenant tracking to auth store"
```

---

## Task 5: Export Tenant Types

**Files:**
- Modify: `apps/hl-drive-web/src/types/index.ts`

**Produces:** Tenant types available app-wide

- [ ] **Step 1: Add export**

```typescript
// apps/hl-drive-web/src/types/index.ts

export * from './tenant';
```

- [ ] **Step 2: Commit**

```bash
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web
git add src/types/index.ts
git commit -m "feat: export tenant types"
```

---

## Task 6: Integrate Tenant Selector into Login View

**Files:**
- Modify: `apps/hl-drive-web/src/views/LoginView.vue`

**Consumes:** TenantSelector component, useTenantSelection composable
**Produces:** Updated LoginView with selector integration

- [ ] **Step 1: Add imports and composable**

```vue
<script setup lang="ts">
import { useTenantSelection } from '~/composables/useTenantSelection';
import TenantSelector from '~/components/TenantSelector.vue';

const { 
  showSelector, 
  isLoading, 
  accessibleTenants, 
  handleAccessibleTenants, 
  selectTenant, 
  cancelSelection 
} = useTenantSelection();
</script>
```

- [ ] **Step 2: Update login handler**

Find the `handleLogin` or `onSubmit` method and modify to process `accessible_tenants`:

```typescript
const handleLogin = async (credentials: LoginCredentials) => {
  try {
    isLoading.value = true;
    
    const response = await $fetch('/api/auth/login', {
      method: 'POST',
      body: credentials,
    });

    // Store auth data
    authStore.setToken(response.token);
    authStore.setUser(response.user);
    authStore.setAccessibleTenants(response.accessible_tenants);
    
    // Process tenant selection
    // (auto-selects if 1, shows modal if 2+)
    handleAccessibleTenants(response.accessible_tenants);
    
  } catch (error) {
    errorMessage.value = error.message || 'Login failed';
  } finally {
    isLoading.value = false;
  }
};
```

- [ ] **Step 3: Add TenantSelector to template**

```vue
<template>
  <div>
    <!-- Existing login form -->
    <form @submit.prevent="handleLogin">
      <!-- ... existing form fields ... -->
    </form>

    <!-- Tenant selector modal (shows if 2+ tenants) -->
    <TenantSelector
      v-model:is-open="showSelector"
      :tenants="accessibleTenants"
      :is-loading="isLoading"
      @selected="selectTenant"
      @cancel="cancelSelection"
    />
  </div>
</template>
```

- [ ] **Step 4: Commit**

```bash
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web
git add src/views/LoginView.vue
git commit -m "feat: integrate tenant selector into login flow"
```

---

## Task 7: Test Tenant Selector Component

**Files:**
- Create: `apps/hl-drive-web/src/components/__tests__/TenantSelector.spec.ts`

**Produces:** Component tests

- [ ] **Step 1: Create test file**

```typescript
// apps/hl-drive-web/src/components/__tests__/TenantSelector.spec.ts

import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import TenantSelector from '../TenantSelector.vue';
import type { Tenant } from '~/types/tenant';

describe('TenantSelector', () => {
  const mockTenants: Tenant[] = [
    { id: 1, name: 'My School', role: 'admin' },
    { id: 2, name: 'Partner School', role: 'member' },
  ];

  it('renders all tenants', () => {
    const wrapper = mount(TenantSelector, {
      props: {
        tenants: mockTenants,
        isOpen: true,
      },
      global: {
        stubs: {
          UModal: false,
          UButton: false,
        },
      },
    });

    expect(wrapper.text()).toContain('My School');
    expect(wrapper.text()).toContain('Partner School');
  });

  it('emits selected event when tenant chosen', async () => {
    const wrapper = mount(TenantSelector, {
      props: {
        tenants: mockTenants,
        isOpen: true,
      },
      global: {
        stubs: {
          UModal: false,
          UButton: false,
        },
      },
    });

    // Click first tenant
    const tenantButtons = wrapper.findAll('button[type="button"]');
    await tenantButtons[0].trigger('click');

    // Click confirm
    const confirmBtn = wrapper.findAll('button').find(b => b.text().includes('Continue'));
    await confirmBtn?.trigger('click');

    expect(wrapper.emitted('selected')).toBeTruthy();
    expect(wrapper.emitted('selected')?.[0]?.[0]).toEqual(mockTenants[0]);
  });

  it('emits cancel event', async () => {
    const wrapper = mount(TenantSelector, {
      props: {
        tenants: mockTenants,
        isOpen: true,
      },
      global: {
        stubs: {
          UModal: false,
          UButton: false,
        },
      },
    });

    const cancelBtn = wrapper.findAll('button').find(b => b.text().includes('Cancel'));
    await cancelBtn?.trigger('click');

    expect(wrapper.emitted('cancel')).toBeTruthy();
  });
});
```

- [ ] **Step 2: Run tests**

```bash
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web
npm run test src/components/__tests__/TenantSelector.spec.ts
```

Expected: All tests pass

- [ ] **Step 3: Commit**

```bash
git add src/components/__tests__/TenantSelector.spec.ts
git commit -m "test: add tenant selector component tests"
```

---

## Task 8: Test Tenant Selection Composable

**Files:**
- Create: `apps/hl-drive-web/src/composables/__tests__/useTenantSelection.spec.ts`

**Produces:** Composable tests

- [ ] **Step 1: Create test file**

```typescript
// apps/hl-drive-web/src/composables/__tests__/useTenantSelection.spec.ts

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

- [ ] **Step 2: Run tests**

```bash
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web
npm run test src/composables/__tests__/useTenantSelection.spec.ts
```

Expected: All tests pass

- [ ] **Step 3: Commit**

```bash
git add src/composables/__tests__/useTenantSelection.spec.ts
git commit -m "test: add tenant selection composable tests"
```

---

## Task 9: Manual Test - Login Flow in Browser

**Manual validation in browser**

- [ ] **Step 1: Start dev server**

```bash
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web
npm run dev
```

Wait for server to start on http://localhost:3000 (or shown port)

- [ ] **Step 2: Navigate to login**

Open https://local.hlcore.com/login (or http://localhost:3000/login)

- [ ] **Step 3: Test Case A - Single Tenant (If test user has 1 tenant)**

- Login with valid credentials
- Expected behavior:
  - ✅ Login succeeds
  - ✅ NO modal appears
  - ✅ Auto-redirects to dashboard
  - ✅ Auth store shows `activeTenant` is set

- [ ] **Step 4: Test Case B - Multiple Tenants (If test user has 2+ tenants)**

- Login with valid credentials
- Expected behavior:
  - ✅ Login succeeds
  - ✅ Modal appears showing list of tenants
  - ✅ Can select tenant by clicking
  - ✅ Continue button works
  - ✅ Redirects to dashboard with selected tenant active
  - ✅ Cancel button logs out

- [ ] **Step 5: Test Case C - No Tenants (If possible)**

- Create test user with no tenant access
- Expected behavior:
  - ✅ Login fails with error message

- [ ] **Step 6: Document findings**

Create file: `docs/agent/checkpoints/2026-07-06-tenant-selection-browser-test.md`

```markdown
# Tenant Selection Browser Test Results

**Date:** 2026-07-06
**Tester:** [Your name]

## Test Results

### Test A: Single Tenant
- [ ] Login succeeds
- [ ] No modal shown
- [ ] Auto-redirect to dashboard
- [ ] Active tenant set in store

Status: [PASS/FAIL]
Notes: [Any observations]

### Test B: Multiple Tenants
- [ ] Login succeeds
- [ ] Modal appears with tenant list
- [ ] Can select tenant
- [ ] Continue works
- [ ] Redirects with active tenant

Status: [PASS/FAIL]
Notes: [Any observations]

### Test C: No Tenants
Status: [N/A / PASS / FAIL]
Notes: [If tested]

## Overall Assessment

[Summary of UX, any issues found]
```

- [ ] **Step 7: Stop dev server**

```bash
# Ctrl+C in terminal
```

---

## Task 10: Create Feature Documentation

**Files:**
- Create: `docs/features/2026-07-06-tenant-selection.md`

**Produces:** Feature documentation

- [ ] **Step 1: Create documentation**

```markdown
# Tenant Selection Feature (V1)

## Overview

Frontend integration of multi-tenant login flow. Backend support exists but frontend did not use it.

**V1 Approach:** Minimalista. Auto-select if 1 tenant, modal if 2+. No backend changes.

## User Flows

### Flow A: Single Tenant (Most common in V1)

```
Login Page
  │
  └─→ Email + Password
      │
      └─→ Backend validates globally
          │
          └─→ Returns: user, token, accessible_tenants=[T1]
              │
              └─→ Frontend auto-selects T1
                  │
                  └─→ Auth store: activeTenant = T1
                      │
                      └─→ Dashboard
```

### Flow B: Multiple Tenants (Future case)

```
Login Page
  │
  └─→ Email + Password
      │
      └─→ Backend validates globally
          │
          └─→ Returns: user, token, accessible_tenants=[T1,T2,...]
              │
              └─→ Frontend shows modal
                  │
                  ├─→ User selects T2
                  │
                  └─→ Auth store: activeTenant = T2
                      │
                      └─→ Dashboard
```

## Implementation Details

### Frontend Components

- **TenantSelector.vue**: Modal component (simple, 5 tenants max per design)
- **useTenantSelection()**: Composable with auto-select logic
- **Auth store**: Tracks `activeTenant`, `accessibleTenants`

### Backend (Unchanged)

- `/api/auth/login` already returns `accessible_tenants`
- No changes needed for V1

### Decision Logic

```
if (tenants.length === 0)
  → Error: "No workspace access"
else if (tenants.length === 1)
  → Auto-select(tenants[0])
  → Redirect to /
else
  → Show TenantSelector modal
  → Wait for user selection
  → Redirect to /
```

## Future Enhancements

Post-V1, when HL Consulting exists:

- [ ] Tenant switching mid-session (top navbar)
- [ ] Workspace sidebar
- [ ] Cross-tenant analytics
- [ ] Multi-tenant dashboard personalization

## Testing

```bash
# Component tests
npm run test src/components/__tests__/TenantSelector.spec.ts

# Composable tests
npm run test src/composables/__tests__/useTenantSelection.spec.ts

# Manual browser test
npm run dev
# Navigate to https://local.hlcore.com/login
```

## Files Changed

### Created
- `src/types/tenant.ts`
- `src/components/TenantSelector.vue`
- `src/composables/useTenantSelection.ts`
- `src/components/__tests__/TenantSelector.spec.ts`
- `src/composables/__tests__/useTenantSelection.spec.ts`

### Modified
- `src/stores/auth.ts` (added tenant state)
- `src/views/LoginView.vue` (integrated selector)
- `src/types/index.ts` (exported tenant types)
```

- [ ] **Step 2: Commit**

```bash
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem
git add docs/features/2026-07-06-tenant-selection.md
git commit -m "docs: add tenant selection feature documentation"
```

---

## Summary of Changes

| Component | Action | Purpose |
|-----------|--------|---------|
| **Types** | Create `tenant.ts` | Define Tenant and LoginResponse types |
| **Component** | Create `TenantSelector.vue` | Modal for tenant selection |
| **Composable** | Create `useTenantSelection.ts` | Auto-select logic + modal control |
| **Auth Store** | Modify | Track activeTenant + accessibleTenants |
| **Login View** | Modify | Integrate TenantSelector |
| **Tests** | Create 2 test files | Component + composable coverage |
| **Docs** | Create 2 docs | Feature + browser test results |

**Backend:** No changes needed. Already returns `accessible_tenants`.

**Philosophy:** V1 minimalista. Frontend intelligently uses backend data. Future-proof without over-engineering.

---

## Ready for Execution?

✅ Plan is complete, saved to `docs/superpowers/plans/2026-07-06-tenant-selection-frontend-integration.md`

**Next step:** Use `superpowers:subagent-driven-development` to execute tasks 1-10 sequentially with reviews between each.
