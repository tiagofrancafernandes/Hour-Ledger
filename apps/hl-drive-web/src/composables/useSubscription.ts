import { ref } from 'vue';
import api from '@/services/api';
import type {
    SubscriptionBannerData,
    SubscriptionPaymentRecord,
    SubscriptionSummaryResponse,
    TenantSubscriptionData,
} from '@/types';

export function useSubscription() {
    const loading = ref(false);
    const error = ref<string | null>(null);

    const subscription = ref<TenantSubscriptionData | null>(null);
    const banner = ref<SubscriptionBannerData | null>(null);
    const canMutate = ref(true);
    const paymentInstructions = ref<SubscriptionSummaryResponse['payment_instructions'] | null>(null);
    const payments = ref<SubscriptionPaymentRecord[]>([]);

    // Admin state
    const adminSubscriptions = ref<TenantSubscriptionData[]>([]);
    const adminPayments = ref<SubscriptionPaymentRecord[]>([]);

    async function fetchSummary(): Promise<SubscriptionSummaryResponse | null> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<SubscriptionSummaryResponse>('/subscription');

            if (!response) {
                return null;
            }

            subscription.value = response.subscription;
            banner.value = response.banner;
            canMutate.value = response.can_mutate;
            paymentInstructions.value = response.payment_instructions;

            return response;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Falha ao buscar dados da assinatura';
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function fetchPayments(page = 1, perPage = 15): Promise<SubscriptionPaymentRecord[]> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<{ data: SubscriptionPaymentRecord[] }>('/subscription/payments', {
                page,
                per_page: perPage,
            });

            const items = response?.data || (Array.isArray(response) ? response : []);
            payments.value = items;

            return items;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Falha ao buscar histórico de pagamentos';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function createPaymentIntent(method = 'pix_online'): Promise<{
        payment: SubscriptionPaymentRecord;
        pix_details?: { code: string; key: string; receiver: string; amount: number };
    }> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<{
                payment: SubscriptionPaymentRecord;
                pix_details?: { code: string; key: string; receiver: string; amount: number };
            }>('/subscription/pay', {
                payment_method: method,
            });

            return response;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Falha ao gerar intenção de pagamento';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function uploadReceipt(
        file: File,
        paymentId?: number | null,
        notes?: string
    ): Promise<SubscriptionPaymentRecord> {
        loading.value = true;
        error.value = null;

        const formData = new FormData();
        formData.append('receipt', file);

        if (paymentId) {
            formData.append('payment_id', String(paymentId));
        }

        if (notes && notes.trim()) {
            formData.append('notes', notes.trim());
        }

        try {
            const response = await api.post<{ payment: SubscriptionPaymentRecord }>(
                '/subscription/upload-receipt',
                formData
            );

            await fetchSummary();
            await fetchPayments();

            return response.payment;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Falha ao enviar comprovante de pagamento';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    // ─── Super Admin methods ─────────────────────────────────────────────────────

    async function fetchAdminSubscriptions(params: Record<string, unknown> = {}): Promise<TenantSubscriptionData[]> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<{ data: TenantSubscriptionData[] }>('/admin/subscriptions', params);
            const items = response?.data || (Array.isArray(response) ? response : []);
            adminSubscriptions.value = items;

            return items;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Falha ao listar assinaturas administrativas';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function fetchAdminPayments(params: Record<string, unknown> = {}): Promise<SubscriptionPaymentRecord[]> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<{ data: SubscriptionPaymentRecord[] }>('/admin/payments', params);
            const items = response?.data || (Array.isArray(response) ? response : []);
            adminPayments.value = items;

            return items;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Falha ao listar pagamentos administrativos';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function approvePayment(paymentId: number, notes?: string): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            await api.post(`/admin/payments/${paymentId}/approve`, {
                notes: notes || undefined,
            });
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Falha ao aprovar pagamento';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function rejectPayment(paymentId: number, reason: string): Promise<void> {
        if (!reason || !reason.trim()) {
            throw new Error('A justificativa é obrigatória para rejeição.');
        }

        loading.value = true;
        error.value = null;

        try {
            await api.post(`/admin/payments/${paymentId}/reject`, {
                reason: reason.trim(),
            });
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Falha ao rejeitar pagamento';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function extendGracePeriod(subscriptionId: number, extendedUntil: string, notes?: string): Promise<void> {
        if (!extendedUntil) {
            throw new Error('Data de prorrogação é obrigatória.');
        }

        loading.value = true;
        error.value = null;

        try {
            await api.post(`/admin/subscriptions/${subscriptionId}/extend`, {
                extended_until: extendedUntil,
                notes: notes || undefined,
            });
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Falha ao prorrogar carência';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    return {
        loading,
        error,
        subscription,
        banner,
        canMutate,
        paymentInstructions,
        payments,
        adminSubscriptions,
        adminPayments,
        fetchSummary,
        fetchPayments,
        createPaymentIntent,
        uploadReceipt,
        fetchAdminSubscriptions,
        fetchAdminPayments,
        approvePayment,
        rejectPayment,
        extendGracePeriod,
    };
}
