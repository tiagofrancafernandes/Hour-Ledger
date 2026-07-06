# Task 4: Update Auth Store

**Goal:** Add tenant state and setter methods to Pinia auth store to track active tenant and accessible tenants list.

**Files to modify:**
- `apps/hl-drive-web/src/stores/auth.ts`

**Exact additions (add to store state and actions):**

Add these properties to reactive state:
```typescript
accessibleTenants: [] as Tenant[],
activeTenant: null as Tenant | null,
```

Add these setter methods:
```typescript
const setAccessibleTenants = (tenants: Tenant[]) => {
  state.accessibleTenants = tenants;
};

const setActiveTenant = (tenant: Tenant) => {
  state.activeTenant = tenant;
};
```

Add this computed property:
```typescript
const getActiveTenantId = computed(() => {
  return state.activeTenant?.id ?? null;
});
```

Export these new functions from the store. Import `Tenant` type at top of file:
```typescript
import type { Tenant } from '~/types/tenant';
```

**Steps:**
1. Read current `apps/hl-drive-web/src/stores/auth.ts`
2. Add imports at top: `import type { Tenant } from '~/types/tenant';`
3. Add state properties (accessibleTenants, activeTenant) to state definition
4. Add setter methods (setAccessibleTenants, setActiveTenant)
5. Add computed (getActiveTenantId)
6. Export all new functions from store
7. Run `npm run typecheck` to verify
8. Commit with message: `feat: add tenant tracking to auth store`

**What to report:**
- Status: DONE / NEEDS_CONTEXT / DONE_WITH_CONCERNS / BLOCKED
- Commits: SHA7
- Typecheck result
- Concerns
