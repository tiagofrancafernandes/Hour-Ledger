<script setup lang="ts">
import { onMounted } from 'vue';
import { usePackagePurchases } from '@/composables/usePackagePurchases';

const { purchases, loading, error, fetchPurchases } = usePackagePurchases();

onMounted(async () => {
    try {
        await fetchPurchases();
    } catch (err) {
        // Error is handled by composable
    }
});

const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
        completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    };
    return colors[status] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};

const getStatusLabel = (status: string) => {
    const labels: Record<string, string> = {
        completed: 'Concluído',
        pending: 'Pendente',
        cancelled: 'Cancelado',
    };
    return labels[status] || status;
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('pt-BR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
};
</script>

<template>
    <div class="space-y-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Histórico de Compras</h2>

        <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg">
            <p class="text-red-800 dark:text-red-200">{{ error }}</p>
        </div>

        <div v-if="loading" class="flex items-center justify-center p-8">
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

        <div v-else-if="purchases.length === 0" class="text-center py-12">
            <p class="text-gray-500 dark:text-gray-400">Nenhuma compra encontrada</p>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-300 dark:border-gray-600">
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Pacote</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Quantidade</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Preço Total</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Status</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900 dark:text-white">Data</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="purchase in purchases" :key="purchase.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="px-4 py-3 text-gray-900 dark:text-white">
                            {{ purchase.package?.name || 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">{{ purchase.quantity }}</td>
                        <td class="px-4 py-3 font-semibold text-green-600 dark:text-green-400">
                            R$ {{ parseFloat(purchase.total_price).toFixed(2) }}
                        </td>
                        <td class="px-4 py-3">
                            <span :class="['px-3 py-1 rounded-full text-xs font-medium', getStatusColor(purchase.status)]">
                                {{ getStatusLabel(purchase.status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ formatDate(purchase.created_at) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
