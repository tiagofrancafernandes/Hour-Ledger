import { ref } from 'vue';
import api from '@/services/api';
import { useAuth } from '@/composables/useAuth';
import type { LedgerEntry, PaginatedResponse, WalletWithBalance } from '@/types';

export function useWallets() {
    const wallets = ref<WalletWithBalance[]>([]);
    const wallet = ref<WalletWithBalance | null>(null);
    const entries = ref<LedgerEntry[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const pagination = ref({
        currentPage: 1,
        lastPage: 1,
        total: 0,
    });

    // Internal note visibility state per-wallet
    const internalNoteVisibility = ref<Record<number, boolean>>({});
    const auth = useAuth();

    function toggleInternalNoteVisibility(walletId: number): void {
        internalNoteVisibility.value[walletId] = !internalNoteVisibility.value[walletId];
    }

    function isInternalNoteVisible(walletId: number): boolean {
        return internalNoteVisibility.value[walletId] ?? false;
    }

    function hasInternalNotePermission(): boolean {
        return !!auth.hasPermission && auth.hasPermission('wallet.view_internal_note');
    }

    async function fetchWallets(clientId?: number, page = 1) {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams({ page: String(page) });

            if (clientId) {
                params.append('client_id', String(clientId));
            }

            const response = await api.get<PaginatedResponse<WalletWithBalance>>(`/wallets?${params}`);

            wallets.value = response.data;
            pagination.value = {
                currentPage: response.current_page,
                lastPage: response.last_page,
                total: response.total,
            };
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch wallets';
        } finally {
            loading.value = false;
        }
    }

    async function fetchWallet(id: number) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<WalletWithBalance>(`/wallets/${id}`);
            // API returns full wallet object
            wallet.value = response;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch wallet';
        } finally {
            loading.value = false;
        }
    }

    async function fetchWalletEntries(walletId: number, page = 1) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<PaginatedResponse<LedgerEntry>>(`/wallets/${walletId}/entries?page=${page}`);

            entries.value = response.data;
            pagination.value = {
                currentPage: response.current_page,
                lastPage: response.last_page,
                total: response.total,
            };
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch entries';
        } finally {
            loading.value = false;
        }
    }

    async function createWallet(data: {
        client_id: number;
        name: string;
        description?: string;
        hourly_rate_reference?: number;
        currency_code?: string;
        credit_purchase_allowed?: boolean;
        internal_note?: string;
    }) {
        loading.value = true;
        error.value = null;

        try {
            const newWallet = await api.post<WalletWithBalance>('/wallets', data);

            return newWallet;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to create wallet';

            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function updateWallet(
        id: number,
        data: {
            name?: string;
            description?: string;
            hourly_rate_reference?: number;
            currency_code?: string;
            internal_note?: string;
            credit_purchase_allowed?: boolean;
        }
    ) {
        loading.value = true;
        error.value = null;

        try {
            const updated = await api.put<WalletWithBalance>(`/wallets/${id}`, data);

            if (wallet.value?.id === id) {
                wallet.value = updated;
            }

            return updated;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to update wallet';

            throw e;
        } finally {
            loading.value = false;
        }
    }

    return {
        wallets,
        wallet,
        entries,
        loading,
        error,
        pagination,
        fetchWallets,
        fetchWallet,
        fetchWalletEntries,
        createWallet,
        updateWallet,
        // Internal note helpers
        internalNoteVisibility,
        toggleInternalNoteVisibility,
        isInternalNoteVisible,
        hasInternalNotePermission,
    };
}
