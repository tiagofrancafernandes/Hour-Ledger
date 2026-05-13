import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { api } from '@/services/api';
import type { User, LoginCredentials, LoginResponse, ValidateTokenResponse } from '@/types';

const STORAGE_KEYS = {
    TOKEN: 'auth_token',
    USER: 'auth_user',
    ROLE: 'auth_role',
    PERMISSIONS: 'auth_permissions',
};

export const useAuthStore = defineStore('auth', () => {
    // State
    const user = ref<User | null>(null);
    const role = ref<string | null>(null);
    const permissions = ref<string[]>([]);
    const token = ref<string | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const initialized = ref(false);

    // Getters
    const isAuthenticated = computed(() => !!token.value && !!user.value);

    // Actions
    function loadFromStorage(): void {
        const storedToken = localStorage.getItem(STORAGE_KEYS.TOKEN);
        const storedUser = localStorage.getItem(STORAGE_KEYS.USER);
        const storedRole = localStorage.getItem(STORAGE_KEYS.ROLE);
        const storedPermissions = localStorage.getItem(STORAGE_KEYS.PERMISSIONS);

        if (storedToken) {
            token.value = storedToken;
        }

        if (storedUser) {
            try {
                user.value = JSON.parse(storedUser);
            } catch {
                user.value = null;
            }
        }

        if (storedRole) {
            role.value = storedRole;
        }

        if (storedPermissions) {
            try {
                permissions.value = JSON.parse(storedPermissions);
            } catch {
                permissions.value = [];
            }
        }
    }

    function saveToStorage(): void {
        if (token.value) {
            localStorage.setItem(STORAGE_KEYS.TOKEN, token.value);
        } else {
            localStorage.removeItem(STORAGE_KEYS.TOKEN);
        }

        if (user.value) {
            localStorage.setItem(STORAGE_KEYS.USER, JSON.stringify(user.value));
        } else {
            localStorage.removeItem(STORAGE_KEYS.USER);
        }

        if (role.value) {
            localStorage.setItem(STORAGE_KEYS.ROLE, role.value);
        } else {
            localStorage.removeItem(STORAGE_KEYS.ROLE);
        }

        if (permissions.value.length > 0) {
            localStorage.setItem(STORAGE_KEYS.PERMISSIONS, JSON.stringify(permissions.value));
        } else {
            localStorage.removeItem(STORAGE_KEYS.PERMISSIONS);
        }
    }

    function clearStorage(): void {
        localStorage.removeItem(STORAGE_KEYS.TOKEN);
        localStorage.removeItem(STORAGE_KEYS.USER);
        localStorage.removeItem(STORAGE_KEYS.ROLE);
        localStorage.removeItem(STORAGE_KEYS.PERMISSIONS);
    }

    function clearState(): void {
        user.value = null;
        role.value = null;
        permissions.value = [];
        token.value = null;
        clearStorage();
    }

    async function login(credentials: LoginCredentials): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<LoginResponse>('/auth/login', credentials);

            user.value = response.user;
            role.value = response.role;
            permissions.value = response.permissions;
            token.value = response.token;

            saveToStorage();

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Login failed';
            clearState();

            return false;
        } finally {
            loading.value = false;
        }
    }

    async function logout(): Promise<void> {
        loading.value = true;

        try {
            await api.post('/auth/logout', {});
        } catch {
            // Ignore logout errors, clear state anyway
        } finally {
            clearState();
            loading.value = false;
        }
    }

    async function validateToken(): Promise<boolean> {
        if (!token.value) {
            return false;
        }

        loading.value = true;

        try {
            const response = await api.get<ValidateTokenResponse>('/auth/validate');

            if (response.valid) {
                user.value = response.user;
                role.value = response.role;
                permissions.value = response.permissions;
                saveToStorage();

                return true;
            }

            clearState();

            return false;
        } catch {
            clearState();

            return false;
        } finally {
            loading.value = false;
        }
    }

    async function initialize(): Promise<void> {
        if (initialized.value) {
            return;
        }

        loadFromStorage();

        if (token.value) {
            await validateToken();
        }

        initialized.value = true;
    }

    // Permission helpers
    function can(permission: string): boolean {
        return permissions.value.includes(permission);
    }

    function canAny(permissionList: string[]): boolean {
        return permissionList.some((p) => permissions.value.includes(p));
    }

    function canAll(permissionList: string[]): boolean {
        return permissionList.every((p) => permissions.value.includes(p));
    }

    function hasRole(roleName: string): boolean {
        return role.value === roleName;
    }

    function hasAnyRole(roleList: string[]): boolean {
        return role.value !== null && roleList.includes(role.value);
    }

    return {
        // State
        user,
        role,
        permissions,
        token,
        loading,
        error,
        initialized,

        // Getters
        isAuthenticated,

        // Actions
        login,
        logout,
        validateToken,
        initialize,
        loadFromStorage,
        clearState,

        // Permission helpers
        can,
        canAny,
        canAll,
        hasRole,
        hasAnyRole,
    };
});

// Export type for use in components
export type AuthStore = ReturnType<typeof useAuthStore>;
