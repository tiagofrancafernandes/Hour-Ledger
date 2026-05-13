<template>
    <div class="container mx-auto px-4 py-8">
        <div v-if="loading && !currentPlan" class="text-center py-12">
            <Icon icon="mdi:loading" class="w-12 h-12 animate-spin text-blue-600 mx-auto" />
            <p class="mt-4 text-gray-600">Loading import plan...</p>
        </div>

        <div v-else-if="currentPlan" class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Review Import</h1>
                    <p class="mt-2 text-gray-600">
                        File:
                        <strong>{{ currentPlan.original_filename }}</strong>
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <span
                        :class="[
                            'px-3 py-1 rounded-full text-sm font-medium',
                            {
                                'bg-yellow-100 text-yellow-800': currentPlan.status === 'pending',
                                'bg-green-100 text-green-800': currentPlan.status === 'validated',
                                'bg-blue-100 text-blue-800': currentPlan.status === 'confirmed',
                                'bg-gray-100 text-gray-800': currentPlan.status === 'cancelled',
                            },
                        ]"
                    >
                        {{ statusLabels[currentPlan.status] }}
                    </span>
                </div>
            </div>

            <div v-if="currentPlan.summary" class="grid gap-4 md:grid-cols-4">
                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">Total Rows</p>
                    <p class="text-2xl font-bold text-gray-900">{{ currentPlan.summary.total_rows }}</p>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">Valid Rows</p>
                    <p class="text-2xl font-bold text-green-600">{{ currentPlan.summary.valid_rows }}</p>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">Rows with Errors</p>
                    <p class="text-2xl font-bold text-red-600">{{ currentPlan.summary.invalid_rows }}</p>
                </div>

                <div class="bg-white rounded-lg shadow p-4">
                    <p class="text-sm text-gray-600">Total Hours</p>
                    <p class="text-2xl font-bold text-blue-600">{{ currentPlan.summary.total_hours }}</p>
                </div>
            </div>

            <div
                v-if="currentPlan.summary && currentPlan.summary.invalid_rows > 0"
                class="bg-yellow-50 border border-yellow-200 rounded-lg p-4"
            >
                <div class="flex items-start gap-3">
                    <Icon icon="mdi:alert-outline" class="w-6 h-6 text-yellow-600 flex-shrink-0 mt-0.5" />
                    <div>
                        <h3 class="font-medium text-yellow-900">Warning: Rows with Errors</h3>
                        <p class="text-sm text-yellow-800 mt-1">
                            There are {{ currentPlan.summary.invalid_rows }} row(s) with validation errors. Fix the
                            errors or remove invalid rows before confirming the import.
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Records to Import</h2>

                    <div class="flex gap-2">
                        <CButton
                            v-if="currentPlan.status !== 'confirmed'"
                            preset="outlined-black"
                            size="sm"
                            @click="handleAddRow"
                        >
                            <Icon icon="mdi:plus" class="w-4 h-4" />
                            Add Row
                        </CButton>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hours</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tags</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            <tr
                                v-for="row in currentPlan.rows"
                                :key="row.id"
                                :class="{
                                    'bg-red-50 border-l-4 border-red-500': !row.is_valid,
                                    'border-l-4 border-transparent': row.is_valid,
                                }"
                            >
                                <td class="px-4 py-3 text-sm text-gray-900">{{ row.row_number }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ formatDate(row.reference_date) }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span
                                        :class="[
                                            {
                                                'text-green-600 font-medium': (row.hours ?? 0) > 0,
                                                'text-red-600 font-medium': (row.hours ?? 0) < 0,
                                                'text-gray-400': !row.hours,
                                            },
                                        ]"
                                    >
                                        {{ formatHours(row.hours) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span
                                        :class="[
                                            'px-2 py-1 rounded text-xs font-medium',
                                            {
                                                'bg-red-100 text-red-800': row.input_type === 'debit',
                                                'bg-green-100 text-green-800': row.input_type === 'credit',
                                                'bg-blue-100 text-blue-800': row.input_type === 'adjustment',
                                            },
                                        ]"
                                    >
                                        {{ row.input_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    {{ row.title || '-' }}
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <div v-if="row.tags && row.tags.length > 0" class="flex flex-wrap gap-1">
                                        <span
                                            v-for="(tag, index) in row.tags"
                                            :key="index"
                                            class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs"
                                        >
                                            {{ tag }}
                                        </span>
                                    </div>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="row.is_valid" class="flex items-center gap-1 text-green-600">
                                        <Icon icon="mdi:check-circle" class="w-5 h-5" />
                                        <span class="text-xs">Valid</span>
                                    </div>
                                    <div v-else class="flex items-center gap-1 text-red-600">
                                        <Icon icon="mdi:alert-circle" class="w-5 h-5" />
                                        <span class="text-xs">{{ row.validation_errors.length }} error(s)</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div v-if="currentPlan.status !== 'confirmed'" class="flex gap-2">
                                        <button class="text-blue-600 hover:text-blue-800" @click="handleEditRow(row)">
                                            <Icon icon="mdi:pencil" class="w-5 h-5" />
                                        </button>
                                        <button class="text-red-600 hover:text-red-800" @click="handleDeleteRow(row)">
                                            <Icon icon="mdi:delete" class="w-5 h-5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div
                v-if="currentPlan.status !== 'confirmed' && currentPlan.status !== 'cancelled'"
                class="flex gap-3 justify-end"
            >
                <CButton preset="outlined-black" @click="handleCancel">Cancel Import</CButton>

                <CButton preset="primary" :disabled="!canConfirm || confirming" @click="handleConfirm">
                    <Icon v-if="confirming" icon="mdi:loading" class="w-5 h-5 animate-spin" />
                    <span v-else>Confirm and Create Entries</span>
                </CButton>
            </div>

            <div
                v-else-if="currentPlan.status === 'confirmed'"
                class="bg-green-50 border border-green-200 rounded-lg p-4"
            >
                <div class="flex items-start gap-3">
                    <Icon icon="mdi:check-circle" class="w-6 h-6 text-green-600 shrink-0" />
                    <div>
                        <h3 class="font-medium text-green-900">Import Confirmed</h3>
                        <p class="text-sm text-green-800 mt-1">
                            All records were imported successfully on
                            {{ formatDate(currentPlan.confirmed_at!) }}.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-12">
            <Icon icon="mdi:file-alert-outline" class="w-16 h-16 text-gray-400 mx-auto" />
            <p class="mt-4 text-gray-600">Import plan not found</p>
            <CButton preset="outlined-black" class="mt-4" @click="$router.push('/imports')">Back to Imports</CButton>
        </div>

        <!-- Edit Row Modal -->
        <ImportRowEditModal
            v-model:is-open="showEditModal"
            :row="editingRow"
            :plan-id="currentPlan?.id"
            @save="handleSaveRow"
        />
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, type Ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';
import { useImport } from '@/composables/useImport';
import { useConfirm } from '@/composables/useConfirm';
import { useToast } from '@/composables/useToast';
import { formatHoursDisplay } from '@/utils/timeFormatters';
import ImportRowEditModal from '@/components/ImportRowEditModal.vue';
import type { ImportPlanRow, ImportRowForm } from '@/types';

const route = useRoute();
const router = useRouter();
const { currentPlan, loading, fetchImportPlan, confirmImportPlan, cancelImportPlan, deleteRow, updateRow, addRow } =
    useImport();
const { confirm } = useConfirm();
const toast = useToast();

const confirming: Ref<boolean> = ref(false);
const showEditModal: Ref<boolean> = ref(false);
const editingRow: Ref<ImportPlanRow | null> = ref(null);

const statusLabels: Record<string, string> = {
    pending: 'Pending',
    validated: 'Validated',
    confirmed: 'Confirmed',
    cancelled: 'Cancelled',
};

const canConfirm = computed(() => {
    if (!currentPlan.value || !currentPlan.value.summary) {
        return false;
    }

    return currentPlan.value.summary.invalid_rows === 0 && currentPlan.value.summary.valid_rows > 0;
});

onMounted(async () => {
    const planId = Number(route.params.id);

    if (planId) {
        await fetchImportPlan(planId);
    }
});

function formatDate(dateString: string): string {
    if (!dateString) {
        return '-';
    }

    const date = new Date(dateString);

    return date.toLocaleDateString('pt-BR');
}

function formatHours(hours: number | null): string {
    if (hours === null || hours === undefined) {
        return '-';
    }

    return formatHoursDisplay(hours, true);
}

function handleEditRow(row: ImportPlanRow): void {
    editingRow.value = row;
    showEditModal.value = true;
}

async function handleDeleteRow(row: ImportPlanRow): Promise<void> {
    const confirmed = await confirm({
        title: 'Delete Row',
        message: `Are you sure you want to delete row ${row.row_number}?`,
        confirmText: 'Delete',
        cancelText: 'Cancel',
        variant: 'danger',
    });

    if (!confirmed) {
        return;
    }

    try {
        await deleteRow(row.id);
        toast.success('Row deleted successfully');
    } catch (err) {
        toast.error('Error deleting row');
    }
}

function handleAddRow(): void {
    editingRow.value = null;
    showEditModal.value = true;
}

async function handleSaveRow(data: ImportRowForm): Promise<void> {
    if (!currentPlan.value) {
        return;
    }

    try {
        if (editingRow.value) {
            await updateRow(editingRow.value.id, data);
            toast.success('Row updated successfully');
        } else {
            await addRow(currentPlan.value.id, data);
            toast.success('Row added successfully');
        }

        showEditModal.value = false;
        editingRow.value = null;
    } catch (err) {
        toast.error('Error saving row');
    }
}

async function handleConfirm(): Promise<void> {
    if (!currentPlan.value || !canConfirm.value) {
        return;
    }

    const confirmed = await confirm({
        title: 'Confirm Import',
        message: `Confirm importing ${currentPlan.value.summary?.valid_rows} record(s)? This action cannot be undone.`,
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        variant: 'warning',
    });

    if (!confirmed) {
        return;
    }

    confirming.value = true;

    try {
        await confirmImportPlan(currentPlan.value.id);
        toast.success('Import confirmed successfully!');

        setTimeout(() => {
            router.push('/imports');
        }, 2000);
    } catch (err) {
        toast.error('Error confirming import');
    } finally {
        confirming.value = false;
    }
}

async function handleCancel(): Promise<void> {
    if (!currentPlan.value) {
        return;
    }

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
        await cancelImportPlan(currentPlan.value.id);
        toast.success('Import cancelled');

        setTimeout(() => {
            router.push('/imports');
        }, 1000);
    } catch (err) {
        toast.error('Error cancelling import');
    }
}
</script>
