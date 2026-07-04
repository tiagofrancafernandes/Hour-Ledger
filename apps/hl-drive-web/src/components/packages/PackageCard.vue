<script setup lang="ts">
import { ref } from 'vue';
import type { Package } from '@/types';
import { usePackagePurchases } from '@/composables/usePackagePurchases';

interface Props {
    package: Package;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    refresh: [];
}>();

const { createPurchase, loading } = usePackagePurchases();
const showPurchaseModal = ref(false);
const quantity = ref(1);
const purchaseError = ref<string | null>(null);

const handlePurchase = async () => {
    purchaseError.value = null;

    if (quantity.value < 1) {
        purchaseError.value = 'Quantidade deve ser maior que 0';
        return;
    }

    try {
        await createPurchase({
            package_id: props.package.id,
            quantity: quantity.value,
        });

        showPurchaseModal.value = false;
        quantity.value = 1;
        emit('refresh');
    } catch (err) {
        purchaseError.value = err instanceof Error ? err.message : 'Erro ao comprar pacote';
    }
};

const totalPrice = () => {
    const basePrice = parseFloat(props.package.price);
    return (basePrice * quantity.value).toFixed(2);
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ package.name }}</h3>

        <p v-if="package.description" class="text-gray-600 dark:text-gray-400 text-sm mb-4">
            {{ package.description }}
        </p>

        <div class="space-y-3 mb-6">
            <div class="flex justify-between items-center">
                <span class="text-gray-700 dark:text-gray-300">Horas:</span>
                <span class="font-semibold text-lg text-blue-600 dark:text-blue-400">{{ package.hours }}h</span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-gray-700 dark:text-gray-300">Preço:</span>
                <span class="font-semibold text-lg text-green-600 dark:text-green-400">
                    R$ {{ parseFloat(package.price).toFixed(2) }}
                </span>
            </div>
        </div>

        <button
            @click="showPurchaseModal = true"
            class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium"
        >
            Comprar
        </button>

        <!-- Purchase Modal -->
        <div v-if="showPurchaseModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 w-96 max-w-full mx-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Comprar Pacote</h2>

                <div v-if="purchaseError" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg mb-4">
                    <p class="text-red-800 dark:text-red-200 text-sm">{{ purchaseError }}</p>
                </div>

                <div class="space-y-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Quantidade
                        </label>
                        <input
                            v-model.number="quantity"
                            type="number"
                            min="1"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                        />
                    </div>

                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="flex justify-between mb-2">
                            <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                            <span class="font-semibold text-gray-900 dark:text-white">R$ {{ totalPrice() }}</span>
                        </div>
                        <div class="flex justify-between text-lg border-t border-gray-300 dark:border-gray-600 pt-2">
                            <span class="font-bold text-gray-900 dark:text-white">Total:</span>
                            <span class="font-bold text-green-600 dark:text-green-400">R$ {{ totalPrice() }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button
                        @click="showPurchaseModal = false"
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="handlePurchase"
                        :disabled="loading"
                        class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 transition-colors font-medium"
                    >
                        {{ loading ? 'Processando...' : 'Confirmar Compra' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
