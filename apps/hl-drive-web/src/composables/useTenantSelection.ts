import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import type { Tenant } from '@/types/tenant';
import { useAuthStore } from '@/stores/auth';
import { useTenantStore } from '@/stores/tenant';

export function useTenantSelection() {
    const authStore = useAuthStore();
    const tenantStore = useTenantStore();
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
        tenantStore.setActiveTenant(Number(tenant.id));
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
