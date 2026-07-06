# Task 2: Create Tenant Selector Component

**Goal:** Create a Vue 3 modal component (`TenantSelector.vue`) that displays list of available tenants and allows user to select one.

**Context:** Used after login when user has 2+ accessible tenants. Backend returns list of tenants; this component renders a simple modal selection UI.

**Files to create:**
- `apps/hl-drive-web/src/components/TenantSelector.vue`

**Exact code to create:**

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

**Steps:**
1. Create file `apps/hl-drive-web/src/components/TenantSelector.vue` with exact code above
2. Run `npm run typecheck` to verify no errors
3. Commit with message: `feat: create tenant selector component`

**What to report:**
- Status: DONE / NEEDS_CONTEXT / DONE_WITH_CONCERNS / BLOCKED
- Commits: list of commit SHA7 created
- Test output: typecheck result
- Concerns: any issues found during implementation
