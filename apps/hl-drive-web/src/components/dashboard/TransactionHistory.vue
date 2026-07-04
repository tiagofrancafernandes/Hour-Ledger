<script setup lang="ts">
import { onMounted } from 'vue';
import { useLedger } from '@/composables/useLedger';

interface Props {
    walletId?: number;
    limit?: number;
}

const props = withDefaults(defineProps<Props>(), {
    limit: 10,
});

const { loading, error } = useLedger();

// Mock data for ledger entries - in a real app, this would come from an API
const transactions = [
    {
        id: 1,
        title: 'Compra de Pacote',
        description: 'Pacote 10 Horas',
        hours: '+10.00',
        type: 'credit',
        date: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000),
    },
    {
        id: 2,
        title: 'Aula Consumida',
        description: 'Aula com João Silva',
        hours: '-1.50',
        type: 'debit',
        date: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000),
    },
    {
        id: 3,
        title: 'Ajuste Manual',
        description: 'Bônus de referência',
        hours: '+2.00',
        type: 'credit',
        date: new Date(),
    },
];

const getTypeColor = (type: string) => {
    return type === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
};

const getTypeIcon = (type: string) => {
    return type === 'credit' ? '+' : '−';
};

const formatDate = (date: Date) => {
    return date.toLocaleDateString('pt-BR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Histórico de Transações</h2>

        <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg">
            <p class="text-red-800 dark:text-red-200 text-sm">{{ error }}</p>
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

        <div v-else class="space-y-4">
            <div v-for="transaction in transactions.slice(0, limit)" :key="transaction.id" class="flex items-start justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ transaction.title }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ transaction.description }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ formatDate(transaction.date) }}</p>
                </div>

                <div class="text-right ml-4">
                    <p :class="['text-lg font-semibold', getTypeColor(transaction.type)]">
                        {{ getTypeIcon(transaction.type) }}{{ transaction.hours }}h
                    </p>
                </div>
            </div>

            <button class="w-full text-center py-3 text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium text-sm">
                Ver todas as transações →
            </button>
        </div>
    </div>
</template>
