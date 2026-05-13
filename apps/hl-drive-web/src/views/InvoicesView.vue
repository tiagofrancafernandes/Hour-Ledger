<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { Icon } from '@iconify/vue';
import { useInvoices } from '@/composables/useInvoices';
import { useClients } from '@/composables/useClients';
import { useRouter } from 'vue-router';
import type { Invoice } from '@/types';

const router = useRouter();

const { invoices, loading, error, pagination, canViewAny, canCreate, canViewDraft, fetchInvoices } = useInvoices();
const { clients, fetchClients } = useClients();

// ─── Filters ────────────────────────────────────────────────────────────────

const showFilters = ref(true);

const filters = ref({
    client_id: null as number | null,
    status: null as string | null,
    from_date: '',
    to_date: '',
});

const activeFiltersCount = computed(() => {
    let count = 0;

    if (filters.value.client_id) count++;
    if (filters.value.status) count++;
    if (filters.value.from_date) count++;
    if (filters.value.to_date) count++;

    return count;
});

const clientOptions = computed(() => {
    return clients.value.map((c) => ({
        value: c.id,
        label: c.name,
    }));
});

const statusOptions = computed(() => {
    const options = [
        { value: 'sent', label: 'Sent' },
        { value: 'paid', label: 'Paid' },
        { value: 'cancelled', label: 'Cancelled' },
    ];

    if (canViewDraft.value) {
        options.unshift({ value: 'draft', label: 'Draft' });
    }

    return options;
});

// ─── List State ────────────────────────────────────────────────────────────

onMounted(async () => {
    await fetchInvoices();

    if (canViewAny.value) {
        await fetchClients();
    }
});

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function getStatusBadgeClass(status: string): string {
    const classes: Record<string, string> = {
        draft: 'bg-gray-100 text-gray-700',
        sent: 'bg-blue-100 text-blue-700',
        paid: 'bg-green-100 text-green-700',
        cancelled: 'bg-red-100 text-red-700',
    };

    return classes[status] || 'bg-gray-100 text-gray-700';
}

function handleClearFilters(): void {
    filters.value = {
        client_id: null,
        status: null,
        from_date: '',
        to_date: '',
    };

    handleSearch();
}

async function handleSearch(): Promise<void> {
    await fetchInvoices({
        client_id: filters.value.client_id || undefined,
        status: filters.value.status || undefined,
        from_date: filters.value.from_date || undefined,
        to_date: filters.value.to_date || undefined,
        page: 1,
    });
}

async function goToPage(page: number): Promise<void> {
    await fetchInvoices({
        client_id: filters.value.client_id || undefined,
        status: filters.value.status || undefined,
        from_date: filters.value.from_date || undefined,
        to_date: filters.value.to_date || undefined,
        page,
    });
}

function goToInvoice(invoice: Invoice): void {
    router.push({ name: 'invoice-detail', params: { id: invoice.id } });
}

function goToCreate(): void {
    router.push({ name: 'invoice-create' });
}
</script>

<template>
    <div class="flex flex-col h-full">
        <!-- Header -->
        <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100">
                    <Icon icon="heroicons:document-text" class="text-red-600" />
                </div>
                <h1 class="text-lg font-semibold text-gray-900">Invoices</h1>
            </div>

            <CButton v-if="canCreate" preset="primary-md" icon="heroicons:plus" @click="goToCreate">
                New Invoice
            </CButton>
        </div>

        <!-- Filters Card -->
        <div class="flex-shrink-0 px-6 py-4 border-b border-gray-100 bg-gray-50">
            <div class="flex items-center justify-between mb-3">
                <button
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors"
                    @click="showFilters = !showFilters"
                >
                    <Icon
                        icon="heroicons:funnel"
                        class="w-4 h-4 transition-transform"
                        :class="{ 'rotate-180': !showFilters }"
                    />
                    <span>Filters</span>
                    <span
                        v-if="activeFiltersCount > 0"
                        class="inline-flex items-center justify-center h-4 min-w-4 px-1 rounded-full bg-red-600 text-white text-[10px] font-bold"
                    >
                        {{ activeFiltersCount }}
                    </span>
                </button>

                <button
                    v-if="activeFiltersCount > 0"
                    class="text-xs text-red-600 hover:text-red-700 font-medium transition-colors"
                    @click="handleClearFilters"
                >
                    Clear all
                </button>
            </div>

            <div v-if="showFilters" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <CTypeahead
                    v-if="canViewAny"
                    label="Client"
                    v-model="filters.client_id"
                    :initialOptions="clientOptions"
                    placeholder="All Clients"
                    clearable
                />

                <CSelect label="Status" v-model="filters.status">
                    <option :value="null">All Status</option>
                    <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                    </option>
                </CSelect>

                <CInput label="From Date" v-model="filters.from_date" type="date" />

                <CInput label="To Date" v-model="filters.to_date" type="date" />
            </div>

            <div v-if="showFilters" class="flex gap-2 mt-3">
                <CButton preset="primary-sm" @click="handleSearch">Search</CButton>
                <CButton preset="lightgray-sm" @click="handleClearFilters">Clear</CButton>
            </div>
        </div>

        <!-- Table Container -->
        <div class="flex-1 overflow-auto px-6 py-4">
            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center py-12">
                <div class="text-center">
                    <Icon icon="heroicons:arrow-path" class="w-8 h-8 text-gray-400 mx-auto mb-2 animate-spin" />
                    <p class="text-sm text-gray-500">Loading invoices...</p>
                </div>
            </div>

            <!-- Error State -->
            <div
                v-else-if="error"
                class="flex items-center justify-center py-12 px-4 rounded-lg bg-red-50 border border-red-200"
            >
                <div class="text-center">
                    <Icon icon="heroicons:exclamation-circle" class="w-8 h-8 text-red-500 mx-auto mb-2" />
                    <p class="text-sm font-medium text-red-800">{{ error }}</p>
                </div>
            </div>

            <!-- Table -->
            <div v-else class="border border-gray-200 rounded-lg overflow-hidden bg-white">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                            >
                                Invoice #
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                            >
                                Client
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                            >
                                Date
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                            >
                                Total
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                            >
                                Status
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="invoice in invoices"
                            :key="invoice.id"
                            class="hover:bg-gray-50 transition-colors cursor-pointer"
                            @click="goToInvoice(invoice)"
                        >
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium text-gray-900">#{{ invoice.invoice_number }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-900">
                                    {{ invoice.client?.name || 'N/A' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm text-gray-600">
                                    {{ formatDate(invoice.issue_date) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium text-gray-900">
                                    {{ invoice.currency }} {{ invoice.total }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold',
                                        getStatusBadgeClass(invoice.status),
                                    ]"
                                >
                                    {{ invoice.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button
                                    class="text-sm text-red-600 hover:text-red-700 font-medium transition-colors"
                                    @click.stop="goToInvoice(invoice)"
                                >
                                    View
                                </button>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="!invoices.length">
                            <td colspan="6" class="px-6 py-12 text-center">
                                <Icon icon="heroicons:document-text" class="w-10 h-10 text-gray-300 mx-auto mb-2" />
                                <p class="text-sm font-medium text-gray-500">No invoices found</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="pagination.lastPage > 1" class="flex-shrink-0 px-6 py-4 border-t border-gray-100">
            <div class="flex items-center justify-center gap-2">
                <button
                    :disabled="pagination.currentPage === 1"
                    class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    @click="goToPage(pagination.currentPage - 1)"
                >
                    <Icon icon="heroicons:chevron-left" class="w-4 h-4" />
                    Previous
                </button>
                <span class="text-sm text-gray-500">
                    Page {{ pagination.currentPage }} of {{ pagination.lastPage }}
                </span>
                <button
                    :disabled="pagination.currentPage === pagination.lastPage"
                    class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    @click="goToPage(pagination.currentPage + 1)"
                >
                    Next
                    <Icon icon="heroicons:chevron-right" class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>
</template>
