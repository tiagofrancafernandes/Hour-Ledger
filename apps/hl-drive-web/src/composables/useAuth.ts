import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useAuthStore } from '@/stores/auth';
import type { Wallet } from '@/types/index';

export function useAuth() {
    const authStore = useAuthStore();

    const { user, role, permissions, token, loading, error, initialized, isAuthenticated } = storeToRefs(authStore);

    const isCustomer = computed(() => authStore.hasRole('customer'));

    const getCustomerId = (): number | null => {
        return user.value?.customer_id ?? null;
    };

    const isOwnClient = (clientId: number): boolean => {
        if (!isCustomer.value) {
            return true;
        }

        const customerId = getCustomerId();

        return customerId !== null && customerId === clientId;
    };

    const canAccessWallet = (wallet: Wallet): boolean => {
        if (!isCustomer.value) {
            return true;
        }

        const customerId = getCustomerId();

        return customerId !== null && wallet.client_id === customerId;
    };

    const getTimezone = (defaultValue: any = null): string | null => {
        if (defaultValue && typeof defaultValue === 'string' && defaultValue) {
            return defaultValue;
        }

        if (user.value && typeof user.value?.timezone === 'string' && user.value?.timezone) {
            return user.value?.timezone;
        }

        return Intl.DateTimeFormat().resolvedOptions().timeZone;
    };

    return {
        // Reactive state
        user,
        role,
        permissions,
        token,
        loading,
        error,
        initialized,
        isAuthenticated,

        // Actions
        login: authStore.login,
        logout: authStore.logout,
        validateToken: authStore.validateToken,
        initialize: authStore.initialize,

        // Permission helpers
        hasPermission: authStore.can,
        hasAnyPermission: authStore.canAny,
        hasAllPermissions: authStore.canAll,
        hasRole: authStore.hasRole,
        hasAnyRole: authStore.hasAnyRole,

        // Customer helpers
        isCustomer,
        getCustomerId,
        isOwnClient,
        canAccessWallet,
        getTimezone,

        // Aliases for backwards compatibility
        can: authStore.can,
        canAny: authStore.canAny,
        canAll: authStore.canAll,
    };
}
