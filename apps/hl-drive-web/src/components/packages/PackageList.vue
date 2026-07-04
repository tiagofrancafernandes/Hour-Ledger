<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { usePackages } from '@/composables/usePackages';
import type { Package } from '@/types';

const { packages, loading, error, fetchPackages } = usePackages();
const showCreateForm = ref(false);

onMounted(async () => {
    try {
        await fetchPackages();
    } catch (err) {
        // Error is handled by composable
    }
});

const handlePackageCreated = async (newPackage: Package) => {
    showCreateForm.value = false;
    await fetchPackages();
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Pacotes</h1>
            <button
                @click="showCreateForm = !showCreateForm"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
            >
                {{ showCreateForm ? 'Cancelar' : 'Novo Pacote' }}
            </button>
        </div>

        <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg">
            <p class="text-red-800 dark:text-red-200">{{ error }}</p>
        </div>

        <div v-if="showCreateForm" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <PackageForm @package-created="handlePackageCreated" />
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

        <div v-else-if="packages.length === 0" class="text-center py-12">
            <p class="text-gray-500 dark:text-gray-400">Nenhum pacote disponível</p>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <PackageCard v-for="pkg in packages" :key="pkg.id" :package="pkg" @refresh="fetchPackages" />
        </div>
    </div>
</template>
