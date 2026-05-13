<template>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Products & Services</h1>
            <button
                v-if="canCreate"
                type="button"
                class="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700"
                @click="handleCreate"
            >
                New Product/Service
            </button>
        </div>

        <div class="mb-6 rounded-lg bg-white p-4 shadow dark:bg-gray-800">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <CInput v-model="filters.search" label="Search" placeholder="Search by name or description..." />

                <CSelect v-model="filters.type" label="Type">
                    <option :value="undefined">All Types</option>
                    <option value="product">Product</option>
                    <option value="service">Service</option>
                </CSelect>

                <CSelect v-model="filters.is_active" label="Status">
                    <option :value="undefined">All</option>
                    <option :value="true">Active</option>
                    <option :value="false">Inactive</option>
                </CSelect>
            </div>

            <div class="mt-4 flex gap-2">
                <CButton @click="handleSearch">Search</CButton>
                <CButton preset="outlined-black" @click="handleClearFilters">Clear</CButton>
            </div>
        </div>

        <div v-if="loading" class="text-center">
            <p class="text-gray-600 dark:text-gray-400">Loading...</p>
        </div>

        <div v-else-if="error" class="rounded-lg bg-red-50 p-4 text-red-800">
            {{ error }}
        </div>

        <div v-else-if="productsServices.length === 0" class="rounded-lg bg-gray-50 p-8 text-center dark:bg-gray-800">
            <p class="text-gray-600 dark:text-gray-400">No products/services found</p>
        </div>

        <div v-else class="overflow-x-auto rounded-lg bg-white shadow dark:bg-gray-800">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-700 dark:text-gray-300">
                            Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-700 dark:text-gray-300">
                            Type
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-700 dark:text-gray-300">
                            Unit
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-700 dark:text-gray-300">
                            Price
                        </th>
                        <th
                            class="px-6 py-3 text-center text-xs font-medium uppercase text-gray-700 dark:text-gray-300"
                        >
                            Status
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-700 dark:text-gray-300">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <tr v-for="item in productsServices" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ item.name }}</div>
                                <div v-if="item.description" class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ item.description }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                    {
                                        'bg-blue-100 text-blue-800': item.type === 'product',
                                        'bg-purple-100 text-purple-800': item.type === 'service',
                                    },
                                ]"
                            >
                                {{ item.type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-900 dark:text-white">{{ item.unit }}</td>
                        <td class="px-6 py-4 text-right font-medium text-gray-900 dark:text-white">
                            {{ item.default_price }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span
                                :class="[
                                    'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                    {
                                        'bg-green-100 text-green-800': item.is_active,
                                        'bg-gray-100 text-gray-800': !item.is_active,
                                    },
                                ]"
                            >
                                {{ item.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button
                                v-if="canDelete"
                                type="button"
                                class="text-red-600 hover:text-red-800"
                                @click="handleDelete(item.id)"
                            >
                                Delete
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useProductsServices } from '@/composables/useProductsServices';
import { useToast } from '@/composables/useToast';

const toast = useToast();
const { productsServices, loading, error, canCreate, canDelete, fetchProductsServices, deleteProductService } =
    useProductsServices();

const filters = ref({
    search: undefined as string | undefined,
    type: undefined as 'product' | 'service' | undefined,
    is_active: undefined as boolean | undefined,
});

async function handleSearch() {
    await fetchProductsServices(filters.value);
}

function handleClearFilters() {
    filters.value = {
        search: undefined,
        type: undefined,
        is_active: undefined,
    };
    handleSearch();
}

function handleCreate() {
    alert('Create Product/Service modal would open here');
}

async function handleDelete(id: number) {
    if (!confirm('Are you sure you want to delete this product/service?')) {
        return;
    }

    try {
        await deleteProductService(id);
        toast.success('Product/Service deleted successfully');
    } catch (err) {
        toast.error('Failed to delete product/service');
    }
}

onMounted(async () => {
    await fetchProductsServices();
});
</script>
