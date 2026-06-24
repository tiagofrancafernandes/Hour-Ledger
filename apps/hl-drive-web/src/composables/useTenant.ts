import { computed } from 'vue';
import { useTenantStore } from '@/stores/tenant';
import type { Tenant } from '@/stores/tenant';

export interface UseTenantComposable {
    activeTenantId: ReturnType<typeof computed>;
    activeTenant: ReturnType<typeof computed>;
    tenants: ReturnType<typeof computed>;
    loading: ReturnType<typeof computed>;
    error: ReturnType<typeof computed>;
    selectTenant: (id: number) => void;
    fetchTenants: () => Promise<void>;
    clearTenant: () => void;
}

export function useTenant(): UseTenantComposable {
    const tenantStore = useTenantStore();

    const activeTenantId = computed(() => tenantStore.activeTenantId);
    const activeTenant = computed(() => tenantStore.getActiveTenant());
    const tenants = computed(() => tenantStore.tenants);
    const loading = computed(() => tenantStore.loading);
    const error = computed(() => tenantStore.error);

    function selectTenant(tenantId: number): void {
        tenantStore.setActiveTenant(tenantId);
    }

    async function fetchTenants(): Promise<void> {
        await tenantStore.fetchTenants();
    }

    function clearTenant(): void {
        tenantStore.clearTenant();
    }

    return {
        activeTenantId,
        activeTenant,
        tenants,
        loading,
        error,
        selectTenant,
        fetchTenants,
        clearTenant,
    };
}
