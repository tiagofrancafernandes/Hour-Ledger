import { ref } from 'vue';
import api from '@/services/api';
import type { Client, PaginatedResponse } from '@/types';

export function useClients() {
    const clients = ref<Client[]>([]);
    const client = ref<Client | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const pagination = ref({
        currentPage: 1,
        lastPage: 1,
        total: 0,
    });

    async function fetchClients(page = 1, search = '') {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams({ page: String(page) });

            if (search) {
                params.append('search', search);
            }

            const response = await api.get<PaginatedResponse<Client>>(`/clients?${params}`);

            clients.value = response.data;
            pagination.value = {
                currentPage: response.current_page,
                lastPage: response.last_page,
                total: response.total,
            };
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch clients';
        } finally {
            loading.value = false;
        }
    }

    async function searchClients(search: string): Promise<Client[]> {
        try {
            const params = new URLSearchParams({ search, per_page: '10' });

            const response = await api.get<PaginatedResponse<Client>>(`/clients?${params}`);

            return response.data;
        } catch (e) {
            return [];
        }
    }

    async function fetchClient(id: number) {
        loading.value = true;
        error.value = null;

        try {
            client.value = await api.get<Client>(`/clients/${id}`);
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch client';
        } finally {
            loading.value = false;
        }
    }

    async function createClient(data: { name: string; notes?: string }) {
        loading.value = true;
        error.value = null;

        try {
            const newClient = await api.post<Client>('/clients', data);

            clients.value.push(newClient);

            return newClient;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to create client';
        } finally {
            loading.value = false;
        }
    }

    async function updateClient(id: number, data: { name?: string; notes?: string }) {
        loading.value = true;
        error.value = null;

        try {
            const updated = await api.put<Client>(`/clients/${id}`, data);

            const index = clients.value.findIndex((c) => c.id === id);

            if (index !== -1) {
                clients.value[index] = updated;
            }

            if (client.value?.id === id) {
                client.value = updated;
            }

            return updated;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to update client';
        } finally {
            loading.value = false;
        }
    }

    async function deleteClient(id: number) {
        loading.value = true;
        error.value = null;

        try {
            await api.delete(`/clients/${id}`);

            clients.value = clients.value.filter((c) => c.id !== id);
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to delete client';
        } finally {
            loading.value = false;
        }
    }

    return {
        clients,
        client,
        loading,
        error,
        pagination,
        fetchClients,
        searchClients,
        fetchClient,
        createClient,
        updateClient,
        deleteClient,
    };
}
