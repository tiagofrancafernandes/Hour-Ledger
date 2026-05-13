import type { App } from 'vue';
import { useAuthStore } from '@/stores/auth';
import type { User } from '@/types';

// Global helper functions
export function can(permission: string): boolean {
    const authStore = useAuthStore();

    return authStore.can(permission);
}

export function canAny(permissions: string[]): boolean {
    const authStore = useAuthStore();

    return authStore.canAny(permissions);
}

export function canAll(permissions: string[]): boolean {
    const authStore = useAuthStore();

    return authStore.canAll(permissions);
}

export function authUser(): User | null {
    const authStore = useAuthStore();

    return authStore.user;
}

export function authRole(): string | null {
    const authStore = useAuthStore();

    return authStore.role;
}

export function isAuthenticated(): boolean {
    const authStore = useAuthStore();

    return authStore.isAuthenticated;
}

// Vue plugin to add global properties
export const authPlugin = {
    install(app: App) {
        // Add global properties accessible via this.$can, etc. in Options API
        app.config.globalProperties.$can = can;
        app.config.globalProperties.$canAny = canAny;
        app.config.globalProperties.$canAll = canAll;
        app.config.globalProperties.$authUser = authUser;
        app.config.globalProperties.$authRole = authRole;
        app.config.globalProperties.$isAuthenticated = isAuthenticated;

        // Provide for injection in Composition API
        app.provide('can', can);
        app.provide('canAny', canAny);
        app.provide('canAll', canAll);
        app.provide('authUser', authUser);
        app.provide('authRole', authRole);
        app.provide('isAuthenticated', isAuthenticated);
    },
};

// Type augmentation for global properties
declare module 'vue' {
    interface ComponentCustomProperties {
        $can: typeof can;
        $canAny: typeof canAny;
        $canAll: typeof canAll;
        $authUser: typeof authUser;
        $authRole: typeof authRole;
        $isAuthenticated: typeof isAuthenticated;
    }
}

export default authPlugin;
