import { ref } from 'vue';
import api from '@/services/api';
import type { PackagePurchase, PackagePurchaseForm } from '@/types';

export function usePackagePurchases() {
    const loading = ref(false);
    const error = ref<string | null>(null);
    const purchases = ref<PackagePurchase[]>([]);

    async function fetchPurchases(): Promise<PackagePurchase[]> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<PackagePurchase[]>('/package-purchases');
            purchases.value = Array.isArray(response) ? response : [];
            return purchases.value;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch purchases';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function fetchPurchase(id: number): Promise<PackagePurchase> {
        loading.value = true;
        error.value = null;

        try {
            return await api.get<PackagePurchase>(`/package-purchases/${id}`);
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch purchase';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function createPurchase(data: PackagePurchaseForm): Promise<PackagePurchase> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<PackagePurchase>('/package-purchases', data);
            const newPurchase = response;
            purchases.value.push(newPurchase);
            return newPurchase;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to create purchase';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function fetchStudentPurchases(studentId: number): Promise<PackagePurchase[]> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<PackagePurchase[]>(`/students/${studentId}/purchases`);
            return Array.isArray(response) ? response : [];
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch student purchases';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    return {
        loading,
        error,
        purchases,
        fetchPurchases,
        fetchPurchase,
        createPurchase,
        fetchStudentPurchases,
    };
}
