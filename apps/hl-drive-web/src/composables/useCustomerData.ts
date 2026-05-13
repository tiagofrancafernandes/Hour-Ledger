import { ref, computed } from 'vue';
import api from '@/services/api';
import type { Client, WalletWithBalance } from '@/types';
import { useAuth } from '@/composables/useAuth';

export function useCustomerData() {
    const auth = useAuth();

    const myClient = ref<Client | null>(null);
    const myWallets = ref<WalletWithBalance[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    const myWalletsSummary = computed(() => {
        const summary = {
            totalWallets: myWallets.value.length,
            totalBalance: 0,
            walletsWithBalance: [] as WalletWithBalance[],
        };

        myWallets.value.forEach((wallet) => {
            const balance = parseFloat(wallet.balance) || 0;

            summary.totalBalance += balance;
            summary.walletsWithBalance.push(wallet);
        });

        return summary;
    });

    async function fetchMyClient() {
        const customerId = auth.getCustomerId();

        if (!customerId) {
            error.value = 'Client not found';

            return;
        }

        loading.value = true;
        error.value = null;

        try {
            myClient.value = await api.get<Client>(`/clients/${customerId}`);
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch client data';
        } finally {
            loading.value = false;
        }
    }

    async function fetchMyWallets() {
        const customerId = auth.getCustomerId();

        if (!customerId) {
            error.value = 'Client not found';

            return;
        }

        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<{ wallets: WalletWithBalance[] }>(`/clients/${customerId}`);

            if (response.wallets) {
                myWallets.value = response.wallets;
            }
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch wallets';
        } finally {
            loading.value = false;
        }
    }

    return {
        myClient,
        myWallets,
        loading,
        error,
        myWalletsSummary,
        fetchMyClient,
        fetchMyWallets,
    };
}
