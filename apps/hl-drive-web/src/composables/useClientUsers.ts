import { ref } from 'vue';
import api from '@/services/api';
import { extractErrorMessage } from '@/utils/errorHelpers';
import type { User, ClientUserForm, UpdateClientUserForm } from '@/types';

export function useClientUsers() {
    const users = ref<User[]>([]);
    const user = ref<User | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function attachUser(clientId: number, userId: number, role?: 'admin' | 'member'): Promise<User> {
        loading.value = true;
        error.value = null;

        try {
            const payload: Record<string, unknown> = { user_id: userId };

            if (role) {
                payload.role = role;
            }

            const response = await api.post<{ user: User }>(`/clients/${clientId}/users/attach`, payload);

            users.value.push(response.user);

            return response.user;
        } catch (e) {
            error.value = extractErrorMessage(e);

            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function setClientAdmin(clientId: number, userId: number): Promise<User> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put<{ user: User }>(`/clients/${clientId}/users/${userId}/set-admin`, {});

            users.value.forEach((u) => {
                if (u.client_role === 'admin') {
                    u.client_role = 'member';
                }
            });

            const index = users.value.findIndex((u) => u.id === userId);

            if (index !== -1) {
                users.value[index] = response.user;
            }

            return response.user;
        } catch (e) {
            error.value = extractErrorMessage(e);

            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function fetchClientUsers(clientId: number) {
        loading.value = true;
        error.value = null;

        try {
            users.value = await api.get<User[]>(`/clients/${clientId}/users`);
        } catch (e) {
            error.value = extractErrorMessage(e);
        } finally {
            loading.value = false;
        }
    }

    async function createClientUser(clientId: number, data: ClientUserForm) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<{ user: User }>(`/clients/${clientId}/users`, data);

            users.value.push(response.user);

            return response.user;
        } catch (e) {
            error.value = extractErrorMessage(e);

            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function updateClientUser(clientId: number, userId: number, data: UpdateClientUserForm) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put<{ user: User }>(`/clients/${clientId}/users/${userId}`, data);

            const index = users.value.findIndex((u) => u.id === userId);

            if (index !== -1) {
                users.value[index] = response.user;
            }

            return response.user;
        } catch (e) {
            error.value = extractErrorMessage(e);

            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function deleteClientUser(clientId: number, userId: number) {
        loading.value = true;
        error.value = null;

        try {
            await api.delete(`/clients/${clientId}/users/${userId}`);

            users.value = users.value.filter((u) => u.id !== userId);
        } catch (e) {
            error.value = extractErrorMessage(e);

            throw e;
        } finally {
            loading.value = false;
        }
    }

    return {
        users,
        user,
        loading,
        error,
        fetchClientUsers,
        createClientUser,
        attachUser,
        setClientAdmin,
        updateClientUser,
        deleteClientUser,
    };
}
