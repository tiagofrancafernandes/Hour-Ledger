<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue';
import { Icon } from '@iconify/vue';
import type { CreditPurchase, CreditPurchasePayment, PaymentMethod } from '@/types';
import { useCreditPurchases } from '@/composables/useCreditPurchases';
import { usePermissions } from '@/composables/usePermissions';
import { useClients } from '@/composables/useClients';
import { useWallets } from '@/composables/useWallets';
import { useToast } from '@/composables/useToast';
import type { TypeaheadOption } from '@/types';

const {
    purchases,
    loading,
    error,
    fetchPurchases,
    setPaymentMethod,
    uploadReceipt,
    downloadReceipt,
    approvePayment,
    rejectPayment,
} = useCreditPurchases();

const { canApprovePayments, canViewAnyReports, isCustomer, canViewClients } = usePermissions();
const { clients, fetchClients } = useClients();
const { wallets, fetchWallets } = useWallets();
const toast = useToast();

// ─── Filters ────────────────────────────────────────────────────────────────

const showFilters = ref(true);

type FiltersType = {
    dateFrom: string | number | Date;
    dateTo: string | number | Date;
    purchaseStatus: string;
    paymentStatus: string;
    paymentMethod: string;
    walletId: number | null;
    clientId: number | null;
};

const filters = ref<FiltersType>({
    dateFrom: '',
    dateTo: '',
    purchaseStatus: '',
    paymentStatus: '',
    paymentMethod: '',
    walletId: null,
    clientId: null,
});

// ─── Debounced filter watch ───────────────────────────────────────────────────

let filterDebounceTimer: ReturnType<typeof setTimeout> | null = null;

async function fetchPurchasesXd() {
    let params: any = Object.fromEntries(
        Object.entries(filters.value || {}).filter((i: any) => ![null, '', undefined].includes(i[1]))
    );

    return await fetchPurchases(params);
}

watch(
    filters,
    () => {
        if (filterDebounceTimer !== null) {
            clearTimeout(filterDebounceTimer);
        }

        filterDebounceTimer = setTimeout(async () => {
            await fetchPurchasesXd();
        }, 400);
    },
    { deep: true }
);

// ─── Typeahead options ─────────────────────────────────────────────────────────

const walletOptions = computed<TypeaheadOption[]>(() => {
    return wallets.value.map((w) => ({
        value: w.id,
        label: w.name,
    }));
});

const clientOptions = computed<TypeaheadOption[]>(() => {
    return clients.value.map((c) => ({
        value: c.id,
        label: c.name,
    }));
});

async function refreshWalletOptions({ searchTerm }: { searchTerm: string }): Promise<TypeaheadOption[]> {
    await fetchWallets(undefined, 1);

    return walletOptions.value;
}

async function refreshClientOptions({ searchTerm }: { searchTerm: string }): Promise<TypeaheadOption[]> {
    if (!canViewClients.value) {
        return;
    }

    await fetchClients(1, searchTerm || '');

    return clientOptions.value;
}

function resetFilters(): void {
    filters.value = {
        dateFrom: '',
        dateTo: '',
        purchaseStatus: '',
        paymentStatus: '',
        paymentMethod: '',
        walletId: null,
        clientId: null,
    };
}

// ─── Filtered data ───────────────────────────────────────────────────────────

const filteredPurchases = computed(() => {
    return purchases.value.filter((p) => {
        if (filters.value.dateFrom) {
            if (new Date(p.created_at) < new Date(filters.value.dateFrom)) {
                return false;
            }
        }

        if (filters.value.dateTo) {
            const to = new Date(filters.value.dateTo);
            to.setHours(23, 59, 59, 999);

            if (new Date(p.created_at) > to) {
                return false;
            }
        }

        if (filters.value.purchaseStatus && p.status !== filters.value.purchaseStatus) {
            return false;
        }

        if (filters.value.walletId && p.wallet_id !== filters.value.walletId) {
            return false;
        }

        if (filters.value.clientId && p.wallet?.client?.id !== filters.value.clientId) {
            return false;
        }

        const payment = p.payments?.[0];

        if (filters.value.paymentStatus) {
            if (!payment || payment.payment_status !== filters.value.paymentStatus) {
                return false;
            }
        }

        if (filters.value.paymentMethod) {
            if (filters.value.paymentMethod === 'none') {
                if (payment?.payment_method !== null) {
                    return false;
                }
            } else {
                if (payment?.payment_method?.key !== filters.value.paymentMethod) {
                    return false;
                }
            }
        }

        return true;
    });
});

const activeFiltersCount = computed(() => {
    let count = 0;

    if (filters.value.dateFrom) count++;
    if (filters.value.dateTo) count++;
    if (filters.value.purchaseStatus) count++;
    if (filters.value.paymentStatus) count++;
    if (filters.value.paymentMethod) count++;
    if (filters.value.walletId) count++;
    if (filters.value.clientId) count++;

    return count;
});

// ─── Detail modal ────────────────────────────────────────────────────────────

const modalOpen = ref(false);
const selectedPurchase = ref<CreditPurchase | null>(null);

const modalPayment = computed<CreditPurchasePayment | null>(() => {
    return selectedPurchase.value?.payments?.[0] ?? null;
});

function openModal(purchase: CreditPurchase): void {
    selectedPurchase.value = purchase;
    modalOpen.value = true;
    resetModalForms();
}

function closeModal(): void {
    modalOpen.value = false;
    selectedPurchase.value = null;
    resetModalForms();
}

// ─── Modal: set payment method ───────────────────────────────────────────────

const showSetMethodForm = ref(false);
const pendingMethod = ref<'pix_offline' | 'bank_transfer' | ''>('');
const submittingMethod = ref(false);

async function handleSetMethod(): Promise<void> {
    if (!selectedPurchase.value || !modalPayment.value || !pendingMethod.value) {
        return;
    }

    submittingMethod.value = true;

    try {
        await setPaymentMethod(selectedPurchase.value.id, modalPayment.value.id, pendingMethod.value);
        toast.success('Payment method updated successfully');
        showSetMethodForm.value = false;
        await reloadPurchase();
    } catch {
        toast.error('Failed to update payment method');
    } finally {
        submittingMethod.value = false;
    }
}

// ─── Modal: upload receipt ───────────────────────────────────────────────────

const showUploadForm = ref(false);
const pendingReceiptFile = ref<File | null>(null);
const submittingReceipt = ref(false);

function handleReceiptFileSelect(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (!file) {
        return;
    }

    const validTypes = ['application/pdf', 'image/png', 'image/jpeg'];

    if (!validTypes.includes(file.type)) {
        toast.error('Invalid file type. Use PDF, PNG or JPG');
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        toast.error('File too large. Maximum 5MB');
        return;
    }

    pendingReceiptFile.value = file;
}

async function handleUploadReceipt(): Promise<void> {
    if (!selectedPurchase.value || !modalPayment.value || !pendingReceiptFile.value) {
        return;
    }

    submittingReceipt.value = true;

    try {
        await uploadReceipt(selectedPurchase.value.id, modalPayment.value.id, pendingReceiptFile.value);
        toast.success('Receipt uploaded successfully');
        showUploadForm.value = false;
        pendingReceiptFile.value = null;
        await reloadPurchase();
    } catch {
        toast.error('Failed to upload receipt');
    } finally {
        submittingReceipt.value = false;
    }
}

async function handleDownloadReceipt(): Promise<void> {
    if (!modalPayment.value) {
        return;
    }

    try {
        await downloadReceipt(modalPayment.value.id);
    } catch {
        toast.error('Failed to download receipt');
    }
}

// ─── Modal: approve / reject ─────────────────────────────────────────────────

const showRejectForm = ref(false);
const approveNotes = ref('');
const rejectNotes = ref('');
const submittingApproval = ref(false);

async function handleApprove(): Promise<void> {
    if (!modalPayment.value) {
        return;
    }

    submittingApproval.value = true;

    try {
        await approvePayment(modalPayment.value.id, approveNotes.value || undefined);
        toast.success('Payment approved and credits added to wallet');
        approveNotes.value = '';
        await reloadPurchase();
    } catch {
        toast.error('Failed to approve payment');
    } finally {
        submittingApproval.value = false;
    }
}

async function handleReject(): Promise<void> {
    if (!modalPayment.value || !rejectNotes.value.trim()) {
        toast.error('Reason is required for rejection');
        return;
    }

    submittingApproval.value = true;

    try {
        await rejectPayment(modalPayment.value.id, rejectNotes.value);
        toast.success('Payment rejected');
        showRejectForm.value = false;
        rejectNotes.value = '';
        await reloadPurchase();
    } catch {
        toast.error('Failed to reject payment');
    } finally {
        submittingApproval.value = false;
    }
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

function resetModalForms(): void {
    showSetMethodForm.value = false;
    showUploadForm.value = false;
    showRejectForm.value = false;
    pendingMethod.value = '';
    pendingReceiptFile.value = null;
    approveNotes.value = '';
    rejectNotes.value = '';
}

async function reloadPurchase(): Promise<void> {
    await fetchPurchasesXd();

    if (selectedPurchase.value) {
        const refreshed = purchases.value.find((p) => p.id === selectedPurchase.value?.id);

        if (refreshed) {
            selectedPurchase.value = refreshed;
        }
    }
}

function getPaymentMethodLabel(method: PaymentMethod | null): string {
    if (!method) {
        return 'Not selected';
    }

    return method.label;
}

function getPaymentStatusColor(status: string): string {
    const map: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        completed: 'bg-blue-100 text-blue-800',
    };

    return map[status] ?? 'bg-gray-100 text-gray-700';
}

function getPurchaseStatusColor(status: string): string {
    const map: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
        cancelled: 'bg-gray-100 text-gray-700',
    };

    return map[status] ?? 'bg-gray-100 text-gray-700';
}

function isPaymentExpired(payment: CreditPurchasePayment): boolean {
    if (!payment.expires_at) {
        return false;
    }

    return new Date(payment.expires_at) < new Date();
}

function formatCurrency(amount: string | number, code = 'USD'): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: code,
    }).format(typeof amount === 'string' ? parseFloat(amount) : amount);
}

function formatDate(date: string): string {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

function formatExpiresAt(expiresAt: string): string {
    const diff = new Date(expiresAt).getTime() - Date.now();

    if (diff <= 0) {
        return 'Expired';
    }

    const hours = Math.floor(diff / 3_600_000);
    const days = Math.floor(hours / 24);

    if (days > 0) {
        return `${days}d ${hours % 24}h left`;
    }

    if (hours > 0) {
        const minutes = Math.floor((diff % 3_600_000) / 60_000);
        return `${hours}h ${minutes}m left`;
    }

    return `${Math.floor(diff / 60_000)}m left`;
}

// ─── Lifecycle ───────────────────────────────────────────────────────────────

onMounted(async () => {
    await Promise.all([fetchPurchasesXd(), fetchClients(1, ''), fetchWallets()]);
});
</script>

<template>
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- Page header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Payment History</h1>
                <p class="text-sm text-gray-500 mt-1">Track credit purchases and payment status</p>
            </div>

            <button
                @click="showFilters = !showFilters"
                :class="[
                    'inline-flex items-center gap-2 px-3 py-2 rounded-lg border text-sm font-medium transition-colors',
                    {
                        'bg-red-50 border-red-200 text-red-700': activeFiltersCount > 0,
                        'bg-white border-gray-300 text-gray-700 hover:bg-gray-50': activeFiltersCount === 0,
                    },
                ]"
            >
                <Icon icon="mdi:filter-outline" class="w-4 h-4" />
                Filters
                <span
                    v-if="activeFiltersCount > 0"
                    class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-red-600 text-white text-xs font-bold"
                >
                    {{ activeFiltersCount }}
                </span>
            </button>
        </div>

        <!-- Error state -->
        <div v-if="error" class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
            {{ error }}
        </div>

        <!-- Filter panel -->
        <div v-if="showFilters" class="mb-5 rounded-xl border border-gray-200 bg-white shadow-sm p-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Date from -->
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">From</label>
                    <input
                        v-model="filters.dateFrom"
                        type="date"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                    />
                </div>

                <!-- Date to -->
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">To</label>
                    <input
                        v-model="filters.dateTo"
                        type="date"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                    />
                </div>

                <!-- Purchase status -->
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">
                        Purchase Status
                    </label>
                    <select
                        v-model="filters.purchaseStatus"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                    >
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Payment status -->
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">
                        Payment Status
                    </label>
                    <select
                        v-model="filters.paymentStatus"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                    >
                        <option value="">All statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>

                <!-- Payment method -->
                <div class="space-y-1">
                    <label class="block text-xs font-medium text-gray-600 uppercase tracking-wide">
                        Payment Method
                    </label>
                    <select
                        v-model="filters.paymentMethod"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent"
                    >
                        <option value="">All methods</option>
                        <option value="pix_offline">PIX Offline</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="none">Not selected</option>
                    </select>
                </div>

                <CTypeahead
                    label="Wallet"
                    v-model="filters.walletId"
                    :initialOptions="walletOptions"
                    :refreshOptions="refreshWalletOptions"
                    placeholder="All Wallets"
                    clearable
                />

                <CTypeahead
                    v-if="canViewAnyReports"
                    label="Client"
                    v-model="filters.clientId"
                    :initialOptions="clientOptions"
                    :refreshOptions="refreshClientOptions"
                    placeholder="All Clients"
                    clearable
                />

                <!-- Reset filters -->
                <div class="flex items-end">
                    <button
                        v-if="activeFiltersCount > 0"
                        @click="resetFilters"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm text-gray-600 hover:text-red-600 transition-colors"
                    >
                        <Icon icon="mdi:filter-off-outline" class="w-4 h-4" />
                        Clear all
                    </button>
                </div>
            </div>
        </div>

        <!-- Loading skeleton -->
        <div
            v-if="loading && purchases.length === 0"
            class="rounded-xl border border-gray-200 bg-white overflow-hidden"
        >
            <div class="animate-pulse">
                <div class="h-10 bg-gray-100 border-b border-gray-200"></div>
                <div v-for="i in 5" :key="i" class="flex gap-4 px-6 py-4 border-b border-gray-100">
                    <div class="h-4 bg-gray-200 rounded w-24"></div>
                    <div class="h-4 bg-gray-200 rounded w-32"></div>
                    <div class="h-4 bg-gray-200 rounded w-28"></div>
                    <div class="h-4 bg-gray-200 rounded w-20 ml-auto"></div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div
            v-else-if="!loading && purchases.length === 0"
            class="rounded-xl border border-gray-200 bg-white p-16 text-center"
        >
            <Icon icon="mdi:wallet-outline" class="w-12 h-12 text-gray-300 mx-auto mb-3" />
            <p class="font-medium text-gray-600">No credit purchases yet</p>
            <p class="text-sm text-gray-400 mt-1">Start by purchasing credits from your wallet page</p>
        </div>

        <!-- Empty filtered state -->
        <div
            v-else-if="!loading && filteredPurchases.length === 0"
            class="rounded-xl border border-gray-200 bg-white p-12 text-center"
        >
            <Icon icon="mdi:filter-off-outline" class="w-10 h-10 text-gray-300 mx-auto mb-3" />
            <p class="font-medium text-gray-600">No results match the current filters</p>
            <button @click="resetFilters" class="mt-3 text-sm text-red-600 hover:underline">Clear filters</button>
        </div>

        <!-- Table -->
        <div v-else class="relative rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
            <!-- Loading overlay (re-fetch while data is already shown) -->
            <div
                v-if="loading"
                class="absolute inset-0 z-10 flex items-center justify-center bg-white/70 backdrop-blur-[1px]"
            >
                <div
                    class="flex items-center gap-2 rounded-full bg-white px-4 py-2 shadow-md text-sm font-medium text-gray-600"
                >
                    <svg class="animate-spin w-4 h-4 text-red-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    Carregando…
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"
                            >
                                Date
                            </th>
                            <th
                                v-if="canViewAnyReports"
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"
                            >
                                Client
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"
                            >
                                Wallet
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider"
                            >
                                Hours
                            </th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider"
                            >
                                Amount
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"
                            >
                                Method
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"
                            >
                                Tags
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"
                            >
                                Receipt
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"
                            >
                                Payment
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider"
                            >
                                Purchase
                            </th>
                            <th class="px-4 py-3 w-12"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        <tr
                            v-for="purchase in filteredPurchases"
                            :key="purchase.id"
                            class="hover:bg-gray-50 transition-colors cursor-pointer"
                            @click="openModal(purchase)"
                        >
                            <!-- Date -->
                            <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap">
                                {{ formatDate(purchase.created_at) }}
                            </td>

                            <!-- Client -->
                            <td v-if="canViewAnyReports" class="px-4 py-3 text-sm text-gray-800 whitespace-nowrap">
                                {{ purchase.wallet?.client?.name ?? '—' }}
                            </td>

                            <!-- Wallet -->
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 whitespace-nowrap">
                                {{ purchase.wallet?.name ?? '—' }}
                            </td>

                            <!-- Hours -->
                            <td class="px-4 py-3 text-sm text-right text-gray-800 whitespace-nowrap font-medium">
                                {{ purchase.total_hours }}h
                            </td>

                            <!-- Amount -->
                            <td class="px-4 py-3 text-sm text-right text-gray-800 whitespace-nowrap">
                                {{ formatCurrency(purchase.total_price, purchase.currency_code) }}
                            </td>

                            <!-- Payment method -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    v-if="purchase.payments?.[0]?.payment_method"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-gray-700"
                                >
                                    <Icon
                                        :icon="
                                            purchase.payments[0].payment_method.key === 'pix_offline'
                                                ? 'mdi:qrcode'
                                                : 'mdi:bank-outline'
                                        "
                                        class="w-3.5 h-3.5 text-gray-400"
                                    />
                                    {{ purchase.payments[0].payment_method.label }}
                                </span>
                                <span v-else-if="purchase.payments?.length" class="text-xs text-gray-400 italic">
                                    Not selected
                                </span>
                                <span v-else class="text-xs text-gray-300">—</span>
                            </td>

                            <!-- Payment tags -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    v-if="purchase.payments?.[0]?.payment_method"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-gray-700"
                                >
                                    {{
                                        [
                                            purchase.payments[0].payment_method.key,
                                            purchase.payments[0].payment_method.is_offline ? 'offline' : '',
                                            purchase.payments[0].payment_method.currency,
                                        ]
                                            .filter(Boolean)
                                            .join('|')
                                    }}
                                </span>
                            </td>

                            <!-- Payment receipt -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    v-if="purchase.payments?.[0]?.payment_method"
                                    class="inline-flex items-center gap-1 text-xs font-medium text-gray-700"
                                >
                                    <template
                                        v-if="
                                            purchase.payments[0].payment_method.is_offline &&
                                            purchase.payments[0].pix_receipt_path
                                        "
                                    >
                                        <Icon icon="flowbite:file-check-solid" class="w-3.5 h-3.5 text-gray-400" />
                                    </template>
                                </span>
                            </td>

                            <!-- Payment status -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <template v-if="purchase.payments?.[0]">
                                    <span
                                        v-if="isPaymentExpired(purchase.payments[0])"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600"
                                    >
                                        Expired
                                    </span>
                                    <span
                                        v-else
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                            getPaymentStatusColor(purchase.payments[0].payment_status),
                                        ]"
                                    >
                                        {{ purchase.payments[0].payment_status }}
                                    </span>
                                </template>
                                <span v-else class="text-xs text-gray-300">—</span>
                            </td>

                            <!-- Purchase status -->
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    :class="[
                                        'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                        getPurchaseStatusColor(purchase.status),
                                    ]"
                                >
                                    {{ purchase.status }}
                                </span>
                            </td>

                            <!-- Action -->
                            <td class="px-4 py-3 text-right">
                                <button
                                    @click.stop="openModal(purchase)"
                                    class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                                    aria-label="View details"
                                >
                                    <Icon icon="mdi:open-in-new" class="w-4 h-4" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Table footer -->
            <div class="px-5 py-3 border-t border-gray-100 bg-gray-50 flex items-center justify-between">
                <p class="text-xs text-gray-500">
                    Showing {{ filteredPurchases.length }} of {{ purchases.length }} purchases
                </p>
            </div>
        </div>

        <!-- ─── Detail Modal ─────────────────────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="modalOpen && selectedPurchase"
                class="fixed inset-0 z-50 overflow-y-auto"
                role="dialog"
                aria-modal="true"
            >
                <!-- Backdrop -->
                <div class="fixed inset-0 bg-black/40 backdrop-blur-sm" @click="closeModal" />

                <!-- Panel -->
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl">
                        <!-- Modal header -->
                        <div class="flex items-start justify-between px-6 py-5 border-b border-gray-200">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Purchase Details</h2>
                                <p class="text-sm text-gray-500 mt-0.5">
                                    #{{ selectedPurchase.id }} · {{ formatDate(selectedPurchase.created_at) }}
                                </p>
                            </div>
                            <button
                                @click="closeModal"
                                class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                                aria-label="Close modal"
                            >
                                <Icon icon="mdi:close" class="w-5 h-5" />
                            </button>
                        </div>

                        <div class="px-6 py-5 space-y-6 overflow-y-auto max-h-[calc(90vh-10rem)]">
                            <!-- Purchase summary -->
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 mb-1">Hours</p>
                                    <p class="text-lg font-bold text-gray-900">{{ selectedPurchase.total_hours }}h</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 mb-1">Amount</p>
                                    <p class="text-lg font-bold text-gray-900">
                                        {{
                                            formatCurrency(selectedPurchase.total_price, selectedPurchase.currency_code)
                                        }}
                                    </p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 mb-1">Status</p>
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold',
                                            getPurchaseStatusColor(selectedPurchase.status),
                                        ]"
                                    >
                                        {{ selectedPurchase.status }}
                                    </span>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 mb-1">Wallet</p>
                                    <p class="text-sm font-medium text-gray-800 truncate">
                                        {{ selectedPurchase.wallet?.name ?? '—' }}
                                    </p>
                                </div>
                                <div v-if="canViewAnyReports" class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 mb-1">Client</p>
                                    <p class="text-sm font-medium text-gray-800 truncate">
                                        {{ selectedPurchase.wallet?.client?.name ?? '—' }}
                                    </p>
                                </div>
                                <div v-if="canViewAnyReports" class="bg-gray-50 rounded-lg p-3">
                                    <p class="text-xs text-gray-500 mb-1">Customer</p>
                                    <p class="text-sm font-medium text-gray-800 truncate">
                                        {{ selectedPurchase.customer?.name ?? '—' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Payment section -->
                            <div v-if="modalPayment" class="space-y-4">
                                <h3
                                    class="text-sm font-semibold text-gray-700 uppercase tracking-wide border-b border-gray-100 pb-2"
                                >
                                    Payment
                                </h3>

                                <!-- Payment info row -->
                                <div class="flex flex-wrap items-center gap-3">
                                    <!-- Method badge -->
                                    <div class="flex items-center gap-1.5">
                                        <Icon
                                            :icon="
                                                modalPayment.payment_method?.key === 'pix_offline'
                                                    ? 'mdi:qrcode'
                                                    : modalPayment.payment_method
                                                      ? 'mdi:bank-outline'
                                                      : 'mdi:help-circle-outline'
                                            "
                                            class="w-4 h-4 text-gray-500"
                                        />
                                        <span class="text-sm font-medium text-gray-800">
                                            {{ getPaymentMethodLabel(modalPayment.payment_method) }}
                                        </span>
                                    </div>

                                    <!-- Payment status badge -->
                                    <span
                                        v-if="isPaymentExpired(modalPayment)"
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600"
                                    >
                                        <Icon icon="mdi:timer-off-outline" class="w-3.5 h-3.5 mr-1" />
                                        Expired
                                    </span>
                                    <span
                                        v-else
                                        :class="[
                                            'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium',
                                            getPaymentStatusColor(modalPayment.payment_status),
                                        ]"
                                    >
                                        {{ modalPayment.payment_status }}
                                    </span>

                                    <!-- Expiry countdown -->
                                    <span
                                        v-if="
                                            modalPayment.expires_at &&
                                            !isPaymentExpired(modalPayment) &&
                                            modalPayment.payment_status === 'pending'
                                        "
                                        class="inline-flex items-center gap-1 text-xs text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-full"
                                    >
                                        <Icon icon="mdi:clock-outline" class="w-3.5 h-3.5" />
                                        {{ formatExpiresAt(modalPayment.expires_at) }}
                                    </span>
                                </div>

                                <!-- Admin notes -->
                                <div
                                    v-if="modalPayment.notes"
                                    class="rounded-lg bg-gray-50 border border-gray-200 px-4 py-3"
                                >
                                    <p class="text-xs font-semibold text-gray-500 mb-1">Notes</p>
                                    <p class="text-sm text-gray-700">{{ modalPayment.notes }}</p>
                                </div>

                                <!-- ── Customer actions ─────────────────────── -->

                                <!-- Set method -->
                                <div
                                    v-if="
                                        !modalPayment.payment_method &&
                                        modalPayment.payment_status === 'pending' &&
                                        !isPaymentExpired(modalPayment)
                                    "
                                    class="rounded-lg border border-dashed border-gray-300 p-4"
                                >
                                    <div v-if="showSetMethodForm" class="space-y-3">
                                        <p class="text-sm font-medium text-gray-700">Select a payment method:</p>
                                        <div class="flex flex-col gap-2">
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    value="bank_transfer"
                                                    v-model="pendingMethod"
                                                    class="accent-red-600"
                                                />
                                                <span class="text-sm text-gray-700">Bank Transfer</span>
                                                <span class="text-xs text-amber-600">(expires in 7 days)</span>
                                            </label>
                                            <label class="flex items-center gap-2 cursor-pointer">
                                                <input
                                                    type="radio"
                                                    value="pix_offline"
                                                    v-model="pendingMethod"
                                                    class="accent-red-600"
                                                />
                                                <span class="text-sm text-gray-700">PIX Offline</span>
                                                <span class="text-xs text-amber-600">(expires in 48h)</span>
                                            </label>
                                        </div>
                                        <div class="flex gap-2 pt-1">
                                            <CButton
                                                preset="primary"
                                                size="sm"
                                                :disabled="!pendingMethod || submittingMethod"
                                                @click="handleSetMethod"
                                            >
                                                Confirm
                                            </CButton>
                                            <CButton
                                                preset="outlined-black"
                                                size="sm"
                                                :disabled="submittingMethod"
                                                @click="showSetMethodForm = false"
                                            >
                                                Cancel
                                            </CButton>
                                        </div>
                                    </div>
                                    <button
                                        v-else
                                        @click="showSetMethodForm = true"
                                        class="flex items-center gap-1.5 text-sm text-red-600 hover:text-red-800 font-medium"
                                    >
                                        <Icon icon="mdi:credit-card-outline" class="w-4 h-4" />
                                        Select payment method
                                    </button>
                                </div>

                                <!-- Upload receipt -->
                                <div
                                    v-if="
                                        modalPayment.payment_method &&
                                        !modalPayment.pix_receipt_path &&
                                        modalPayment.payment_status === 'pending' &&
                                        !isPaymentExpired(modalPayment)
                                    "
                                    class="rounded-lg border border-dashed border-gray-300 p-4"
                                >
                                    <div v-if="showUploadForm" class="space-y-3">
                                        <p class="text-sm font-medium text-gray-700">Upload payment receipt:</p>
                                        <label
                                            class="flex flex-col items-center justify-center w-full h-24 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer hover:border-red-400 hover:bg-red-50 transition-all"
                                        >
                                            <input
                                                type="file"
                                                accept=".pdf,.png,.jpg,.jpeg"
                                                class="hidden"
                                                @change="handleReceiptFileSelect"
                                            />
                                            <div v-if="!pendingReceiptFile" class="text-center">
                                                <Icon
                                                    icon="mdi:cloud-upload-outline"
                                                    class="w-7 h-7 text-gray-400 mx-auto mb-1"
                                                />
                                                <p class="text-xs text-gray-500">PDF, PNG or JPG (max 5MB)</p>
                                            </div>
                                            <div v-else class="text-center text-green-600">
                                                <Icon icon="mdi:check-circle" class="w-7 h-7 mx-auto mb-1" />
                                                <p class="text-xs font-medium">{{ pendingReceiptFile.name }}</p>
                                            </div>
                                        </label>
                                        <div class="flex gap-2">
                                            <CButton
                                                preset="primary"
                                                size="sm"
                                                :disabled="!pendingReceiptFile || submittingReceipt"
                                                @click="handleUploadReceipt"
                                            >
                                                Upload
                                            </CButton>
                                            <CButton
                                                preset="outlined-black"
                                                size="sm"
                                                :disabled="submittingReceipt"
                                                @click="
                                                    showUploadForm = false;
                                                    pendingReceiptFile = null;
                                                "
                                            >
                                                Cancel
                                            </CButton>
                                        </div>
                                    </div>
                                    <button
                                        v-else
                                        @click="showUploadForm = true"
                                        class="flex items-center gap-1.5 text-sm text-red-600 hover:text-red-800 font-medium"
                                    >
                                        <Icon icon="mdi:file-upload-outline" class="w-4 h-4" />
                                        Upload receipt
                                    </button>
                                </div>

                                <!-- Download receipt -->
                                <div v-if="modalPayment.pix_receipt_path">
                                    <button
                                        @click="handleDownloadReceipt"
                                        class="inline-flex items-center gap-1.5 text-sm text-gray-600 hover:text-red-600 font-medium transition-colors"
                                    >
                                        <Icon icon="mdi:file-download-outline" class="w-4 h-4" />
                                        Download receipt
                                    </button>
                                </div>

                                <!-- ── Admin approval actions ───────────────── -->
                                <div
                                    v-if="
                                        canApprovePayments &&
                                        modalPayment.payment_status === 'pending' &&
                                        !isPaymentExpired(modalPayment)
                                    "
                                    class="rounded-lg bg-amber-50 border border-amber-200 p-4 space-y-3"
                                >
                                    <p class="text-sm font-semibold text-amber-800 flex items-center gap-1.5">
                                        <Icon icon="mdi:shield-check-outline" class="w-4 h-4" />
                                        Approval
                                    </p>

                                    <!-- Reject form -->
                                    <div v-if="showRejectForm" class="space-y-3">
                                        <div class="space-y-1">
                                            <label for="reject-notes" class="block text-xs font-medium text-gray-700">
                                                Reason for rejection
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <textarea
                                                id="reject-notes"
                                                v-model="rejectNotes"
                                                rows="3"
                                                placeholder="Describe why this payment is being rejected…"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent resize-none"
                                            />
                                        </div>
                                        <div class="flex gap-2">
                                            <CButton
                                                preset="danger"
                                                size="sm"
                                                :disabled="!rejectNotes.trim() || submittingApproval"
                                                @click="handleReject"
                                            >
                                                Confirm Rejection
                                            </CButton>
                                            <CButton
                                                preset="outlined-black"
                                                size="sm"
                                                :disabled="submittingApproval"
                                                @click="showRejectForm = false"
                                            >
                                                Cancel
                                            </CButton>
                                        </div>
                                    </div>

                                    <!-- Approve / Reject buttons -->
                                    <div v-else class="space-y-3">
                                        <div class="space-y-1">
                                            <label for="approve-notes" class="block text-xs font-medium text-gray-700">
                                                Notes (optional)
                                            </label>
                                            <input
                                                id="approve-notes"
                                                v-model="approveNotes"
                                                type="text"
                                                placeholder="Add a note…"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                            />
                                        </div>
                                        <div class="flex gap-2">
                                            <CButton
                                                preset="success"
                                                size="sm"
                                                :disabled="submittingApproval"
                                                @click="handleApprove"
                                            >
                                                <Icon icon="mdi:check" class="w-4 h-4 mr-1" />
                                                Approve & Add Credits
                                            </CButton>
                                            <CButton
                                                preset="outlined-black"
                                                size="sm"
                                                :disabled="submittingApproval"
                                                @click="showRejectForm = true"
                                            >
                                                <Icon icon="mdi:close" class="w-4 h-4 mr-1" />
                                                Reject
                                            </CButton>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- No payment yet -->
                            <div v-else class="rounded-lg bg-gray-50 border border-gray-200 p-4 text-center">
                                <Icon icon="mdi:credit-card-off-outline" class="w-8 h-8 text-gray-300 mx-auto mb-2" />
                                <p class="text-sm text-gray-500">No payment registered yet for this purchase</p>
                            </div>
                        </div>

                        <!-- Modal footer -->
                        <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                            <CButton preset="outlined-black" @click="closeModal">Close</CButton>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
