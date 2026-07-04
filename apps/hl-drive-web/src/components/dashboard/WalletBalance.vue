<script setup lang="ts">
import { onMounted } from 'vue';
import { useWallets } from '@/composables/useWallets';
import type { Wallet } from '@/types';

interface Props {
    wallet?: Wallet;
}

const props = defineProps<Props>();

const { balance, loading, error, fetchBalance } = useWallets();

onMounted(async () => {
    if (props.wallet) {
        try {
            await fetchBalance(props.wallet.id);
        } catch (err) {
            // Error is handled by composable
        }
    }
});

const getBalanceColor = () => {
    const balanceValue = parseFloat(balance.value || '0');

    if (balanceValue > 10) {
        return 'text-green-600 dark:text-green-400';
    }

    if (balanceValue > 5) {
        return 'text-yellow-600 dark:text-yellow-400';
    }

    return 'text-red-600 dark:text-red-400';
};
</script>

<template>
    <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg shadow-md p-8 border border-blue-200 dark:border-blue-800">
        <p class="text-blue-700 dark:text-blue-300 text-sm font-medium mb-2">Saldo Disponível</p>

        <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg">
            <p class="text-red-800 dark:text-red-200 text-sm">{{ error }}</p>
        </div>

        <div v-else-if="loading" class="flex items-center justify-center py-8">
            <div class="animate-spin">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                    />
                </svg>
            </div>
        </div>

        <div v-else class="space-y-2">
            <p :class="['text-5xl font-bold', getBalanceColor()]">
                {{ balance }} <span class="text-2xl">horas</span>
            </p>

            <p v-if="wallet" class="text-blue-700 dark:text-blue-300 text-sm">
                Carteira: <span class="font-semibold">{{ wallet.name }}</span>
            </p>

            <p v-if="wallet?.description" class="text-blue-600 dark:text-blue-400 text-sm">
                {{ wallet.description }}
            </p>
        </div>
    </div>
</template>
