import { ref } from 'vue';
import api from '@/services/api';
import type { CreditPurchase, CreditPurchasePayment, PaginatedResponse } from '@/types';

export function useCreditPurchases() {
    const purchases = ref<CreditPurchase[]>([]);
    const purchase = ref<CreditPurchase | null>(null);
    const payment = ref<CreditPurchasePayment | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function fetchPurchases(params: Record<string, unknown> = {}) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<PaginatedResponse<CreditPurchase>>('/credit-purchases', params);
            purchases.value = response.data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch credit purchases';
        } finally {
            loading.value = false;
        }
    }

    async function createPurchase(walletId: number, totalHours: number, totalPrice: number) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<{ data: CreditPurchase }>('/credit-purchases', {
                wallet_id: walletId,
                total_hours: totalHours,
                total_price: totalPrice,
            });

            purchases.value.push(response.data);
            purchase.value = response.data;

            return response.data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to create credit purchase';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function getPurchase(purchaseId: number) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<CreditPurchase>(`/credit-purchases/${purchaseId}`);
            purchase.value = response;
            return response;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch credit purchase';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function createPayment(purchaseId: number, paymentMethod: 'pix_offline' | 'bank_transfer' | null) {
        loading.value = true;
        error.value = null;

        try {
            const body: Record<string, unknown> = {};

            if (paymentMethod) {
                body.payment_method = paymentMethod;
            }

            const response = await api.post<{ data: CreditPurchasePayment }>(
                `/credit-purchases/${purchaseId}/payments`,
                body
            );

            payment.value = response.data;

            return response.data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to create payment';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function setPaymentMethod(
        purchaseId: number,
        paymentId: number,
        paymentMethod: 'pix_offline' | 'bank_transfer'
    ) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put<{ data: CreditPurchasePayment }>(
                `/credit-purchases/${purchaseId}/payments/${paymentId}/set-method`,
                { payment_method: paymentMethod }
            );

            payment.value = response.data;

            return response.data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to update payment method';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function uploadReceipt(purchaseId: number, paymentId: number, file: File) {
        loading.value = true;
        error.value = null;

        try {
            const formData = new FormData();
            formData.append('receipt', file);

            const response = await api.post<{ data: CreditPurchasePayment }>(
                `/credit-purchases/${purchaseId}/payments/${paymentId}/upload-receipt`,
                formData
            );

            payment.value = response.data;

            return response.data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to upload receipt';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function approvePayment(paymentId: number, notes?: string): Promise<CreditPurchasePayment> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<{ data: CreditPurchasePayment }>(`/payments/${paymentId}/approve`, {
                notes: notes || null,
            });

            return response.data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to approve payment';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function rejectPayment(paymentId: number, notes: string): Promise<CreditPurchasePayment> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<{ data: CreditPurchasePayment }>(`/payments/${paymentId}/reject`, {
                notes,
            });

            return response.data;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to reject payment';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function downloadReceipt(paymentId: number): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            await api.download(`/payments/${paymentId}/receipt-download`);
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to download receipt';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    return {
        purchases,
        purchase,
        payment,
        loading,
        error,
        fetchPurchases,
        createPurchase,
        getPurchase,
        createPayment,
        setPaymentMethod,
        uploadReceipt,
        downloadReceipt,
        approvePayment,
        rejectPayment,
    };
}
