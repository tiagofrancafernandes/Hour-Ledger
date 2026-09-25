import type { ComputedRef } from 'vue';
import { computed } from 'vue';
import { useTenantStore } from '@/stores/tenant';
import type { Tenant } from '@/stores/tenant';

export interface UseTenantComposable {
    activeTenantId: ComputedRef<number | null>;
    activeTenant: ComputedRef<Tenant | null>;
    tenants: ComputedRef<Tenant[]>;
    loading: ComputedRef<boolean>;
    error: ComputedRef<string | null>;
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
