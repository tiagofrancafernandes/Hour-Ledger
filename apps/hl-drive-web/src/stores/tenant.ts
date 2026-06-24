import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { api } from '@/services/api';
import type { GenericResponse } from '@/types';

const STORAGE_KEYS = {
    ACTIVE_TENANT_ID: 'tenant_active_id',
};

export interface Tenant {
    id: number;
    name: string;
    status: 'active' | 'suspended' | 'deleted';
    created_at: string;
}

interface TenantState {
    activeTenantId: number | null;
    tenants: Tenant[];
    loading: boolean;
    error: string | null;
}

export const useTenantStore = defineStore('tenant', () => {
    // State
    const state = ref<TenantState>({
        activeTenantId: null,
        tenants: [],
        loading: false,
        error: null,
    });

    // Getters
    const activeTenantId = computed(() => state.value.activeTenantId);
    const tenants = computed(() => state.value.tenants);
    const loading = computed(() => state.value.loading);
    const error = computed(() => state.value.error);

    // Helper Methods
    function loadFromStorage(): void {
        const storedTenantId = localStorage.getItem(STORAGE_KEYS.ACTIVE_TENANT_ID);

        if (storedTenantId) {
            const tenantId = parseInt(storedTenantId, 10);
            state.value.activeTenantId = tenantId;
        }
    }

    function saveToStorage(): void {
        if (state.value.activeTenantId) {
            localStorage.setItem(STORAGE_KEYS.ACTIVE_TENANT_ID, String(state.value.activeTenantId));
        } else {
            localStorage.removeItem(STORAGE_KEYS.ACTIVE_TENANT_ID);
        }
    }

    function clearStorage(): void {
        localStorage.removeItem(STORAGE_KEYS.ACTIVE_TENANT_ID);
    }

    function getActiveTenant(): Tenant | null {
        if (!state.value.activeTenantId) {
            return null;
        }

        const tenant = state.value.tenants.find((t) => t.id === state.value.activeTenantId);

        return tenant || null;
    }

    function isActiveTenantValid(): boolean {
        if (!state.value.activeTenantId) {
            return false;
        }

        const tenant = getActiveTenant();

        if (!tenant) {
            return false;
        }

        if (tenant.status !== 'active') {
            return false;
        }

        return true;
    }

    // Actions
    function setActiveTenant(tenantId: number): void {
        const tenant = state.value.tenants.find((t) => t.id === tenantId);

        if (!tenant) {
            state.value.error = 'Tenant not found';

            return;
        }

        if (tenant.status !== 'active') {
            state.value.error = 'Tenant is not active';

            return;
        }

        state.value.activeTenantId = tenantId;
        state.value.error = null;
        saveToStorage();

        // Emit custom event to notify headers need update
        window.dispatchEvent(new CustomEvent('tenant-changed', { detail: { tenantId } }));
    }

    async function fetchTenants(): Promise<void> {
        state.value.loading = true;
        state.value.error = null;

        try {
            const response = await api.get<GenericResponse<Tenant[]>>('/tenants');

            let tenantsList: Tenant[] = [];

            if (Array.isArray(response.data)) {
                tenantsList = response.data;
            } else if (response.data && typeof response.data === 'object' && 'data' in response.data) {
                const dataProperty = response.data.data as Tenant[];
                tenantsList = dataProperty;
            }

            state.value.tenants = tenantsList;

            // Validate active tenant after fetch
            if (!isActiveTenantValid() && tenantsList.length > 0) {
                // Auto-select first active tenant if current is invalid
                const firstActiveTenant = tenantsList.find((t) => t.status === 'active');

                if (firstActiveTenant) {
                    state.value.activeTenantId = firstActiveTenant.id;
                    saveToStorage();
                }
            }
        } catch (err) {
            state.value.error = err instanceof Error ? err.message : 'Failed to fetch tenants';
            state.value.tenants = [];
            state.value.activeTenantId = null;
            clearStorage();
        } finally {
            state.value.loading = false;
        }
    }

    function clearTenant(): void {
        state.value.activeTenantId = null;
        state.value.tenants = [];
        state.value.error = null;
        clearStorage();
    }

    async function initialize(): Promise<void> {
        loadFromStorage();
        await fetchTenants();
    }

    return {
        // State
        activeTenantId,
        tenants,
        loading,
        error,

        // Getters
        getActiveTenant,
        isActiveTenantValid,

        // Actions
        setActiveTenant,
        fetchTenants,
        clearTenant,
        initialize,
        loadFromStorage,
    };
});

// Export type for use in components
export type TenantStore = ReturnType<typeof useTenantStore>;
