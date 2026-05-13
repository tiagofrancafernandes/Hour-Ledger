<template>
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <UIPageHeader title="Imports" description="Manage your record imports.">
            <template v-slot:actions>
                <CButton preset="primary" @click="$router.push('/imports/upload')">
                    <Icon icon="mdi:upload" class="w-5 h-5" />
                    New Import
                </CButton>
            </template>
        </UIPageHeader>

        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-4 border-b border-gray-200">
                <div class="grid gap-4 md:grid-cols-3">
                    <CSelect label="Status" v-model="filters.status" @change="handleFilterChange">
                        <option value="">All</option>
                        <option value="pending">Pending</option>
                        <option value="validated">Validated</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="cancelled">Cancelled</option>
                    </CSelect>

                    <CSelect label="Wallet" v-model="filters.wallet_id" @change="handleFilterChange">
                        <option :value="undefined">All</option>
                        <option v-for="wallet in wallets" :key="wallet.id" :value="wallet.id">
                            {{ wallet.name }}
                            <span v-if="wallet.client">({{ wallet.client.name }})</span>
                        </option>
                    </CSelect>

                    <div class="flex items-end align-middle justify-end-safe">
                        <CButton
                            preset="outlined-black-md"
                            class="w-full md:w-6/12"
                            @click="clearFilters"
                            icon="mdi:filter-off"
                        >
                            Clear Filters
                        </CButton>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="loading" class="text-center py-12">
            <Icon icon="mdi:loading" class="w-12 h-12 animate-spin text-blue-600 mx-auto" />
            <p class="mt-4 text-gray-600">Loading imports...</p>
        </div>

        <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
            <p class="text-sm text-red-800">{{ error }}</p>
        </div>

        <div v-else-if="importPlans.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
            <Icon icon="mdi:file-upload-outline" class="w-16 h-16 text-gray-400 mx-auto" />
            <h3 class="mt-4 text-lg font-medium text-gray-900">No imports found</h3>
            <p class="mt-2 text-gray-600">Start by uploading a CSV or XLSX file</p>
            <CButton preset="primary" class="mt-6" @click="$router.push('/imports/upload')">
                <Icon icon="mdi:upload" class="w-5 h-5" />
                New Import
            </CButton>
        </div>

        <div v-else class="space-y-4">
            <div
                v-for="plan in importPlans"
                :key="plan.id"
                class="bg-white rounded-lg shadow hover:shadow-md transition-shadow"
            >
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ plan.original_filename }}
                                </h3>

                                <span
                                    :class="[
                                        'px-3 py-1 rounded-full text-xs font-medium',
                                        {
                                            'bg-yellow-100 text-yellow-800': plan.status === 'pending',
                                            'bg-green-100 text-green-800': plan.status === 'validated',
                                            'bg-blue-100 text-blue-800': plan.status === 'confirmed',
                                            'bg-gray-100 text-gray-800': plan.status === 'cancelled',
                                        },
                                    ]"
                                >
                                    {{ statusLabels[plan.status] }}
                                </span>
                            </div>

                            <div class="grid gap-2 text-sm text-gray-600">
                                <div class="flex items-center gap-2">
                                    <Icon icon="mdi:wallet-outline" class="w-4 h-4" />
                                    <span>
                                        {{ plan.wallet?.name }}
                                        <span v-if="plan.wallet?.client">({{ plan.wallet.client.name }})</span>
                                    </span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <Icon icon="mdi:calendar-outline" class="w-4 h-4" />
                                    <span>Created at {{ formatDate(plan.created_at) }}</span>
                                </div>

                                <div v-if="plan.confirmed_at" class="flex items-center gap-2 text-green-600">
                                    <Icon icon="mdi:check-circle-outline" class="w-4 h-4" />
                                    <span>Confirmed at {{ formatDate(plan.confirmed_at) }}</span>
                                </div>
                            </div>

                            <div v-if="plan.summary" class="mt-4 flex gap-6 text-sm">
                                <div>
                                    <span class="text-gray-600">Total:</span>
                                    <span class="ml-1 font-medium text-gray-900">{{ plan.summary.total_rows }}</span>
                                </div>

                                <div>
                                    <span class="text-gray-600">Valid:</span>
                                    <span class="ml-1 font-medium text-green-600">{{ plan.summary.valid_rows }}</span>
                                </div>

                                <div v-if="plan.summary.invalid_rows > 0">
                                    <span class="text-gray-600">Errors:</span>
                                    <span class="ml-1 font-medium text-red-600">{{ plan.summary.invalid_rows }}</span>
                                </div>

                                <div>
                                    <span class="text-gray-600">Hours:</span>
                                    <span class="ml-1 font-medium text-blue-600">{{ plan.summary.total_hours }}h</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <CButton
                                preset="outlined-black"
                                size="sm"
                                @click="$router.push(`/imports/${plan.id}/review`)"
                            >
                                <Icon icon="mdi:eye-outline" class="w-4 h-4" />
                                View
                            </CButton>

                            <CButton
                                v-if="plan.status !== 'confirmed' && plan.status !== 'cancelled'"
                                preset="danger"
                                size="sm"
                                @click="handleCancelPlan(plan.id)"
                            >
                                <Icon icon="mdi:close" class="w-4 h-4" />
                            </CButton>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="totalPages > 1" class="flex justify-center gap-2 mt-6">
                <CButton
                    preset="outlined-black"
                    size="sm"
                    :disabled="currentPage === 1"
                    @click="handlePageChange(currentPage - 1)"
                >
                    <Icon icon="mdi:chevron-left" class="w-5 h-5" />
                </CButton>

                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600">Page {{ currentPage }} of {{ totalPages }}</span>
                </div>

                <CButton
                    preset="outlined-black"
                    size="sm"
                    :disabled="currentPage === totalPages"
                    @click="handlePageChange(currentPage + 1)"
                >
                    <Icon icon="mdi:chevron-right" class="w-5 h-5" />
                </CButton>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, type Ref } from 'vue';
import { useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';
import { useImport } from '@/composables/useImport';
import { useWallets } from '@/composables/useWallets';
import { useConfirm } from '@/composables/useConfirm';
import { useToast } from '@/composables/useToast';

const router = useRouter();
const { importPlans, loading, error, currentPage, totalPages, fetchImportPlans, cancelImportPlan } = useImport();
const { wallets, fetchWallets } = useWallets();
const { confirm } = useConfirm();
const toast = useToast();

const filters: Ref<{ status?: string; wallet_id?: number }> = ref({
    status: undefined,
    wallet_id: undefined,
});

const statusLabels: Record<string, string> = {
    pending: 'Pending',
    validated: 'Validated',
    confirmed: 'Confirmed',
    cancelled: 'Cancelled',
};

onMounted(async () => {
    await Promise.all([fetchWallets(), fetchImportPlans(1, filters.value)]);
});

function formatDate(dateString: string): string {
    if (!dateString) {
        return '-';
    }

    const date = new Date(dateString);

    return date.toLocaleString('pt-BR');
}

async function handleFilterChange(): Promise<void> {
    await fetchImportPlans(1, filters.value);
}

async function clearFilters(): Promise<void> {
    filters.value = {
        status: undefined,
        wallet_id: undefined,
    };

    await fetchImportPlans(1, filters.value);
}

async function handlePageChange(page: number): Promise<void> {
    await fetchImportPlans(page, filters.value);
}

async function handleCancelPlan(planId: number): Promise<void> {
    const confirmed = await confirm({
        title: 'Cancel Import',
        message: 'Are you sure you want to cancel this import? The file will be deleted.',
        confirmText: 'Yes, Cancel',
        cancelText: 'No',
        variant: 'danger',
    });

    if (!confirmed) {
        return;
    }

    try {
        await cancelImportPlan(planId);
        toast.success('Import cancelled successfully');
        await fetchImportPlans(currentPage.value, filters.value);
    } catch (err) {
        toast.error('Error cancelling import');
    }
}
</script>
