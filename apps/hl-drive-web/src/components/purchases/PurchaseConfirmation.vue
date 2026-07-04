<script setup lang="ts">
import { ref } from 'vue';
import type { PackagePurchase } from '@/types';

interface Props {
    purchase: PackagePurchase;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    confirmed: [];
    cancelled: [];
}>();

const confirmed = ref(false);

const handleConfirm = () => {
    confirmed.value = true;
    setTimeout(() => {
        emit('confirmed');
    }, 1500);
};
</script>

<template>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
            <div v-if="!confirmed" class="p-6">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">Confirmar Compra</h2>

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4 mb-6 space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-700 dark:text-gray-300">Pacote:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ purchase.package?.name || 'N/A' }}
                        </span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-700 dark:text-gray-300">Quantidade:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ purchase.quantity }}</span>
                    </div>

                    <div class="flex justify-between">
                        <span class="text-gray-700 dark:text-gray-300">Horas:</span>
                        <span class="font-semibold text-gray-900 dark:text-white">
                            {{ (parseFloat(purchase.package?.hours || '0') * purchase.quantity).toFixed(2) }}h
                        </span>
                    </div>

                    <div class="border-t border-blue-300 dark:border-blue-600 pt-3 flex justify-between text-lg">
                        <span class="font-bold text-gray-900 dark:text-white">Total:</span>
                        <span class="font-bold text-green-600 dark:text-green-400">
                            R$ {{ parseFloat(purchase.total_price).toFixed(2) }}
                        </span>
                    </div>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                    Você está prestes a comprar este pacote. As horas serão adicionadas à sua carteira imediatamente.
                </p>

                <div class="flex gap-3">
                    <button
                        @click="emit('cancelled')"
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors font-medium"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="handleConfirm"
                        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium"
                    >
                        Confirmar
                    </button>
                </div>
            </div>

            <div v-else class="p-6 text-center">
                <div class="mb-6">
                    <svg class="w-16 h-16 mx-auto text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Compra Realizada!</h3>

                <p class="text-gray-600 dark:text-gray-400">
                    {{ parseFloat(purchase.package?.hours || '0') * purchase.quantity }} horas foram adicionadas à sua carteira.
                </p>
            </div>
        </div>
    </div>
</template>
