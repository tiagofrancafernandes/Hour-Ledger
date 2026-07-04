import { ref } from 'vue';
import api from '@/services/api';
import type { Package, PackageForm } from '@/types';

export function usePackages() {
    const loading = ref(false);
    const error = ref<string | null>(null);
    const packages = ref<Package[]>([]);

    async function fetchPackages(): Promise<Package[]> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<Package[]>('/packages');
            packages.value = Array.isArray(response) ? response : [];
            return packages.value;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch packages';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function fetchPackage(id: number): Promise<Package> {
        loading.value = true;
        error.value = null;

        try {
            return await api.get<Package>(`/packages/${id}`);
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch package';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function createPackage(data: PackageForm): Promise<Package> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<Package>('/packages', data);
            const newPackage = response;
            packages.value.push(newPackage);
            return newPackage;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to create package';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function updatePackage(id: number, data: PackageForm): Promise<Package> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put<Package>(`/packages/${id}`, data);
            const index = packages.value.findIndex((p) => p.id === id);
            if (index !== -1) {
                packages.value[index] = response;
            }
            return response;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to update package';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function deletePackage(id: number): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            await api.delete(`/packages/${id}`);
            packages.value = packages.value.filter((p) => p.id !== id);
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to delete package';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    return {
        loading,
        error,
        packages,
        fetchPackages,
        fetchPackage,
        createPackage,
        updatePackage,
        deletePackage,
    };
}
