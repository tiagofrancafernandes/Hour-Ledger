<script setup lang="ts">
import { ref } from 'vue';
import { usePackages } from '@/composables/usePackages';
import type { Package, PackageForm } from '@/types';

interface Props {
    package?: Package;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'package-created': [pkg: Package];
}>();

const { createPackage, updatePackage, loading, error } = usePackages();

const formData = ref<PackageForm>({
    name: props.package?.name || '',
    description: props.package?.description || null,
    hours: props.package ? parseFloat(props.package.hours) : 1,
    price: props.package ? parseFloat(props.package.price) : 0,
    currency_code: props.package?.currency_code || 'BRL',
});

const validationErrors = ref<Partial<Record<keyof PackageForm, string>>>({});

const validateForm = (): boolean => {
    validationErrors.value = {};

    if (!formData.value.name.trim()) {
        validationErrors.value.name = 'Nome é obrigatório';
    }

    if (formData.value.hours <= 0) {
        validationErrors.value.hours = 'Horas devem ser maior que 0';
    }

    if (formData.value.price < 0) {
        validationErrors.value.price = 'Preço não pode ser negativo';
    }

    return Object.keys(validationErrors.value).length === 0;
};

const handleSubmit = async () => {
    if (!validateForm()) {
        return;
    }

    try {
        if (props.package) {
            await updatePackage(props.package.id, formData.value);
        } else {
            const newPackage = await createPackage(formData.value);
            emit('package-created', newPackage);
        }
    } catch (err) {
        // Error is handled by composable
    }
};
</script>

<template>
    <form @submit.prevent="handleSubmit" class="space-y-6">
        <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg">
            <p class="text-red-800 dark:text-red-200">{{ error }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Nome do Pacote <span class="text-red-500">*</span>
            </label>
            <input
                v-model="formData.name"
                type="text"
                placeholder="Ex: Pacote 10 Horas"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p v-if="validationErrors.name" class="text-red-500 text-sm mt-1">{{ validationErrors.name }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> Descrição </label>
            <textarea
                v-model="formData.description"
                placeholder="Descreva os benefícios deste pacote"
                rows="3"
                class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Horas <span class="text-red-500">*</span>
                </label>
                <input
                    v-model.number="formData.hours"
                    type="number"
                    step="0.5"
                    min="0"
                    placeholder="Ex: 10"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <p v-if="validationErrors.hours" class="text-red-500 text-sm mt-1">{{ validationErrors.hours }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Preço <span class="text-red-500">*</span>
                </label>
                <input
                    v-model.number="formData.price"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="Ex: 250.00"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <p v-if="validationErrors.price" class="text-red-500 text-sm mt-1">{{ validationErrors.price }}</p>
            </div>
        </div>

        <div class="flex gap-3">
            <button
                type="submit"
                :disabled="loading"
                class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition-colors font-medium"
            >
                {{ loading ? 'Salvando...' : package ? 'Atualizar Pacote' : 'Criar Pacote' }}
            </button>
        </div>
    </form>
</template>
