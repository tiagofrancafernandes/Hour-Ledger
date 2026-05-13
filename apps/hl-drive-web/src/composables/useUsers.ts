import { ref } from 'vue';
import { api } from '@/services/api';
import type { User, PaginatedResponse, CreateUserForm } from '@/types';

export interface UserDetail {
    user: User;
    direct_permissions: string[];
    role_permissions: string[];
}

export interface UserOptions {
    roles: string[];
    permissions: string[];
}

export function useUsers() {
    const users = ref<User[]>([]);
    const loading = ref(false);
    const debugOn = ref(false);
    const error = ref<string | null>(null);

    async function fetchUsers(
        params: Record<string, unknown> = {}
    ): Promise<PaginatedResponse<User | undefined> | undefined> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<PaginatedResponse<User>>('/users', params);

            users.value = response.data;

            return response;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch users';
            if (debugOn.value) {
                throw e;
            }
        } finally {
            loading.value = false;
        }
    }

    async function searchUsers(search: string, extra: Record<string, unknown> = {}): Promise<User[] | undefined> {
        try {
            const response = await api.get<PaginatedResponse<User>>('/users', { search, per_page: 10, ...extra });

            return response.data;
        } catch (e) {
            if (debugOn.value) {
                throw e;
            }
        }
    }

    async function fetchUser(id: number): Promise<UserDetail | undefined> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<UserDetail>(`/users/${id}`);

            return response;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch user';
            if (debugOn.value) {
                throw e;
            }
        } finally {
            loading.value = false;
        }
    }

    async function createUser(data: CreateUserForm): Promise<User | undefined> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<{ user: User }>('/users', data);

            users.value.unshift(response.user);

            return response.user;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to create user';
            if (debugOn.value) {
                throw e;
            }
        } finally {
            loading.value = false;
        }
    }

    async function updateUser(id: number, data: { name: string; email: string }): Promise<User | undefined> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put<{ user: User }>(`/users/${id}`, data);

            return response.user;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to update user';
            if (debugOn.value) {
                throw e;
            }
        } finally {
            loading.value = false;
        }
    }

    async function updateUserRole(id: number, role: string): Promise<User | undefined> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put<{ user: User }>(`/users/${id}/role`, { role });

            return response.user;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to update role';
            if (debugOn.value) {
                throw e;
            }
        } finally {
            loading.value = false;
        }
    }

    async function updateUserPermissions(id: number, permissions: string[]): Promise<string[] | undefined> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put<{ direct_permissions: string[] }>(`/users/${id}/permissions`, {
                permissions,
            });

            return response.direct_permissions;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to update permissions';
            if (debugOn.value) {
                throw e;
            }
        } finally {
            loading.value = false;
        }
    }

    async function fetchOptions(): Promise<UserOptions | undefined> {
        try {
            const response = await api.get<UserOptions>('/users/options');

            return response;
        } catch (e) {
            if (debugOn.value) {
                throw e;
            }
        }
    }

    async function attachUserToClient(
        clientId: number,
        userId: number,
        role?: 'admin' | 'member'
    ): Promise<User | undefined> {
        try {
            const payload: Record<string, unknown> = { user_id: userId };

            if (role) {
                payload.role = role;
            }

            const response = await api.post<{ user: User }>(`/clients/${clientId}/users/attach`, payload);

            const index = users.value.findIndex((u) => u.id === userId);

            if (index !== -1) {
                users.value[index] = response.user;
            }

            return response.user;
        } catch (e) {
            if (debugOn.value) {
                throw e;
            }
        }
    }

    async function setUserClientAdmin(clientId: number, userId: number): Promise<User | undefined> {
        try {
            const response = await api.put<{ user: User }>(`/clients/${clientId}/users/${userId}/set-admin`, {});

            users.value.forEach((u) => {
                if (u.customer_id === clientId && u.client_role === 'admin') {
                    u.client_role = 'member';
                }
            });

            const index = users.value.findIndex((u) => u.id === userId);

            if (index !== -1) {
                users.value[index] = response.user;
            }

            return response.user;
        } catch (e) {
            if (debugOn.value) {
                throw e;
            }
        }
    }

    async function updateUserPassword(
        id: number,
        data: { password: string; password_confirmation: string }
    ): Promise<User | undefined> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put<{ user: User }>(`/users/${id}/password`, data);

            return response.user;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to update password';
            if (debugOn.value) {
                throw e;
            }
        } finally {
            loading.value = false;
        }
    }

    return {
        debugOn,
        users,
        loading,
        error,
        fetchUsers,
        searchUsers,
        fetchUser,
        createUser,
        updateUser,
        updateUserRole,
        updateUserPermissions,
        fetchOptions,
        attachUserToClient,
        setUserClientAdmin,
        updateUserPassword,
    };
}
