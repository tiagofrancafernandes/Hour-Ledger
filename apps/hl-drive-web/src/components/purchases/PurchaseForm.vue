<script setup lang="ts">
import { ref } from 'vue';
import { usePackages } from '@/composables/usePackages';
import { usePackagePurchases } from '@/composables/usePackagePurchases';
import type { Package, PackagePurchaseForm } from '@/types';

interface Props {
    walletId: number;
    studentId: number;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'purchase-completed': [];
}>();

const { packages, loading: packagesLoading, fetchPackages } = usePackages();
const { createPurchase, loading: purchaseLoading } = usePackagePurchases();

const selectedPackage = ref<Package | null>(null);
const quantity = ref(1);
const validationErrors = ref<Record<string, string>>({});

fetchPackages();

const validateForm = (): boolean => {
    validationErrors.value = {};

    if (!selectedPackage.value) {
        validationErrors.value.package = 'Selecione um pacote';
    }

    if (quantity.value < 1) {
        validationErrors.value.quantity = 'Quantidade deve ser maior que 0';
    }

    return Object.keys(validationErrors.value).length === 0;
};

const handlePurchase = async () => {
    if (!validateForm() || !selectedPackage.value) {
        return;
    }

    const formData: PackagePurchaseForm = {
        package_id: selectedPackage.value.id,
        quantity: quantity.value,
    };

    try {
        await createPurchase(formData);
        selectedPackage.value = null;
        quantity.value = 1;
        emit('purchase-completed');
    } catch (err) {
        // Error is handled by composable
    }
};

const totalPrice = () => {
    if (!selectedPackage.value) {
        return '0.00';
    }
    const basePrice = parseFloat(selectedPackage.value.price);
    return (basePrice * quantity.value).toFixed(2);
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Comprar Pacote</h2>

        <form @submit.prevent="handlePurchase" class="space-y-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Selecione um Pacote <span class="text-red-500">*</span>
                </label>

                <div v-if="packagesLoading" class="flex items-center justify-center p-8">
                    <div class="animate-spin">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button
                        v-for="pkg in packages"
                        :key="pkg.id"
                        @click="selectedPackage = pkg"
                        :class="[
                            'p-4 rounded-lg border-2 transition-colors text-left',
                            selectedPackage?.id === pkg.id
                                ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20'
                                : 'border-gray-300 dark:border-gray-600 hover:border-blue-400',
                        ]"
                        type="button"
                    >
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ pkg.name }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ pkg.hours }}h - R$ {{ parseFloat(pkg.price).toFixed(2) }}</p>
                    </button>
                </div>

                <p v-if="validationErrors.package" class="text-red-500 text-sm mt-2">{{ validationErrors.package }}</p>
            </div>

            <div v-if="selectedPackage" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Quantidade <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model.number="quantity"
                        type="number"
                        min="1"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <p v-if="validationErrors.quantity" class="text-red-500 text-sm mt-1">{{ validationErrors.quantity }}</p>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Pacote:</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ selectedPackage.name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Quantidade:</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ quantity }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Horas:</span>
                        <span class="text-gray-900 dark:text-white font-medium">
                            {{ (parseFloat(selectedPackage.hours) * quantity).toFixed(2) }}h
                        </span>
                    </div>
                    <div class="border-t border-gray-300 dark:border-gray-600 pt-2 flex justify-between text-lg">
                        <span class="font-bold text-gray-900 dark:text-white">Total:</span>
                        <span class="font-bold text-green-600 dark:text-green-400">R$ {{ totalPrice() }}</span>
                    </div>
                </div>
            </div>

            <button
                type="submit"
                :disabled="purchaseLoading"
                class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 transition-colors font-medium"
            >
                {{ purchaseLoading ? 'Processando...' : 'Confirmar Compra' }}
            </button>
        </form>
    </div>
</template>
