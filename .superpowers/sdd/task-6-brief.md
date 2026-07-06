# Task 6: Integrate Tenant Selector into Login View

**Goal:** Modify LoginView.vue to import and use TenantSelector component + useTenantSelection composable.

**Files to modify:**
- `apps/hl-drive-web/src/views/LoginView.vue`

**Exact changes:**

1. **Add imports to script setup:**
```typescript
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
```

2. **In login handler (find existing handleLogin or onSubmit), after setToken/setUser calls:**
```typescript
// Store accessible tenants
authStore.setAccessibleTenants(response.accessible_tenants);

// Process tenant selection
// (auto-selects if 1, shows modal if 2+)
handleAccessibleTenants(response.accessible_tenants);
```

3. **In template, add TenantSelector component before/after existing form:**
```vue
<!-- Tenant selector modal (shows if 2+ tenants) -->
<TenantSelector
  v-model:is-open="showSelector"
  :tenants="accessibleTenants"
  :is-loading="isLoading"
  @selected="selectTenant"
  @cancel="cancelSelection"
/>
```

**Steps:**
1. Read LoginView.vue
2. Add imports
3. Add composable initialization
4. Add tenant handling in login handler
5. Add TenantSelector to template
6. Run typecheck
7. Commit: `feat: integrate tenant selector into login flow`

**Report to:** `/mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/.superpowers/sdd/task-6-report.md`
