<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Icon } from '@iconify/vue';
import type { Client, CreditPurchase, CreditPurchasePayment } from '@/types';
import { useCreditPurchases } from '@/composables/useCreditPurchases';
import { useClients } from '@/composables/useClients';
import { useToast } from '@/composables/useToast';

const expandedPaymentIds = ref<Set<number>>(new Set());

type TypeaheadOption = {
    value: unknown;
    label: string;
};

const { purchases, loading, error, fetchPurchases, downloadReceipt, approvePayment, rejectPayment } =
    useCreditPurchases();
const toast = useToast();
const { searchClients } = useClients();

type PendingPayment = CreditPurchasePayment & {
    creditPurchase: CreditPurchase;
};

const selectedPayment = ref<PendingPayment | null>(null);
const showApprovalModal = ref(false);
const approvalNotes = ref('');
const approvalAction = ref<'approve' | 'reject' | null>(null);
const isSubmitting = ref(false);

const offlinePaymentMethodKeys = new Set<string>(['pix_offline', 'bank_transfer']);

// Filter pending payments
const pendingPayments = ref<PendingPayment[]>([]);

const clientOptions = ref<TypeaheadOption[]>([]);
const walletOptions = ref<TypeaheadOption[]>([]);
const paymentMethodOptions = ref<TypeaheadOption[]>([]);

const selectedClientId = ref<number | null>(null);
const selectedWalletId = ref<number | null>(null);
const selectedPaymentMethodKey = ref<string | null>(null);

function isOfflinePaymentMethod(method: CreditPurchasePayment['payment_method']): boolean {
    if (!method) {
        return false;
    }

    if (method.is_offline) {
        return true;
    }

    return offlinePaymentMethodKeys.has(method.key);
}

async function loadPendingPayments(filters: Record<string, unknown> = {}): Promise<void> {
    const params: Record<string, unknown> = { ...filters };

    await fetchPurchases({ ...params, pending_approvals: true });

    // Extract pending offline payments
    const pending: PendingPayment[] = [];

    purchases.value.forEach((purchase) => {
        if (purchase.payments) {
            purchase.payments.forEach((payment) => {
                if (isOfflinePaymentMethod(payment.payment_method) && payment.payment_status === 'pending') {
                    pending.push({
                        ...payment,
                        creditPurchase: purchase,
                    });
                }
            });
        }
    });

    pendingPayments.value = pending;

    await Promise.all([
        refreshClientOptions(),
        refreshWalletOptions(selectedClientId.value),
        refreshPaymentMethodOptions(),
    ]);
}

function getPaymentMethodKey(method: CreditPurchasePayment['payment_method']): string | null {
    if (!method) {
        return null;
    }

    if (typeof method === 'string') {
        return method;
    }

    return method.key;
}

const filteredPendingPayments = computed(() => {
    let filtered = pendingPayments.value;

    if (selectedClientId.value) {
        filtered = filtered.filter((payment) => {
            return payment.creditPurchase.wallet?.client?.id === selectedClientId.value;
        });
    }

    if (selectedWalletId.value) {
        filtered = filtered.filter((payment) => {
            return payment.creditPurchase.wallet?.id === selectedWalletId.value;
        });
    }

    if (selectedPaymentMethodKey.value) {
        filtered = filtered.filter((payment) => {
            return getPaymentMethodKey(payment.payment_method) === selectedPaymentMethodKey.value;
        });
    }

    return filtered;
});

const activeFiltersCount = computed(() => {
    let count = 0;

    if (selectedClientId.value) {
        count += 1;
    }

    if (selectedWalletId.value) {
        count += 1;
    }

    if (selectedPaymentMethodKey.value) {
        count += 1;
    }

    return count;
});

async function waitForPurchases(): Promise<void> {
    if (!loading.value) {
        return;
    }

    await new Promise<void>((resolve) => {
        const stop = watch(
            () => loading.value,
            (isLoading) => {
                if (isLoading) {
                    return;
                }

                stop();
                resolve();
            }
        );
    });
}

async function ensurePurchasesLoaded(): Promise<void> {
    if (purchases.value.length > 0) {
        return;
    }

    if (!loading.value) {
        await fetchPurchases({ pending_approvals: true });
        return;
    }

    await waitForPurchases();
}

function formatClientLabel(client: Client): string {
    return client.notes ? `${client.name} — ${client.notes}` : client.name;
}

function buildClientOptions(): TypeaheadOption[] {
    const map = new Map<number, string>();

    purchases.value.forEach((purchase) => {
        const client = purchase.wallet?.client;

        if (!client || map.has(client.id)) {
            return;
        }

        map.set(client.id, formatClientLabel(client));
    });

    return Array.from(map.entries()).map(([id, name]) => {
        return {
            value: id,
            label: name,
        };
    });
}

function buildWalletOptions(clientId: number | null = null): TypeaheadOption[] {
    const map = new Map<number, string>();

    purchases.value.forEach((purchase) => {
        const wallet = purchase.wallet;

        if (!wallet || map.has(wallet.id)) {
            return;
        }

        if (clientId && wallet.client?.id !== clientId) {
            return;
        }

        map.set(wallet.id, wallet.name);
    });

    return Array.from(map.entries()).map(([id, name]) => {
        return {
            value: id,
            label: name,
        };
    });
}

function buildPaymentMethodOptions(): TypeaheadOption[] {
    const map = new Map<string, string>();

    purchases.value.forEach((purchase) => {
        purchase.payments?.forEach((payment) => {
            const methodKey = getPaymentMethodKey(payment.payment_method);

            if (!methodKey || map.has(methodKey)) {
                return;
            }

            const methodLabel =
                typeof payment.payment_method === 'string'
                    ? payment.payment_method
                    : (payment.payment_method?.label ?? methodKey);

            map.set(methodKey, methodLabel);
        });
    });

    return Array.from(map.entries()).map(([key, label]) => {
        return {
            value: key,
            label,
        };
    });
}
async function refreshClientOptions(): Promise<void> {
    await ensurePurchasesLoaded();
    clientOptions.value = buildClientOptions();
}

async function refreshWalletOptions(clientId: number | null = null): Promise<void> {
    await ensurePurchasesLoaded();
    walletOptions.value = buildWalletOptions(clientId);
}

async function refreshPaymentMethodOptions(): Promise<void> {
    await ensurePurchasesLoaded();
    paymentMethodOptions.value = buildPaymentMethodOptions();
}

async function refreshClientSearch({ searchTerm }: { searchTerm: string }): Promise<TypeaheadOption[]> {
    await ensurePurchasesLoaded();

    const normalized = (searchTerm ?? '').trim().toLowerCase();
    const baseMatches = normalized
        ? clientOptions.value.filter((option) => option.label.toLowerCase().includes(normalized))
        : clientOptions.value.slice();

    if (!normalized) {
        return baseMatches;
    }

    try {
        const results = await searchClients(searchTerm);
        const options = results.map((client) => {
            return {
                value: client.id,
                label: formatClientLabel(client),
            };
        });

        const existingValues = new Set(baseMatches.map((option) => option.value));

        options.forEach((option) => {
            if (!existingValues.has(option.value)) {
                baseMatches.push(option);
            }
        });
    } catch {
        // Ignore and fall back to cached matches
    }

    return baseMatches;
}

function resetFilters(): void {
    selectedClientId.value = null;
    selectedWalletId.value = null;
    selectedPaymentMethodKey.value = null;
}

function openApprovalModal(payment: PendingPayment, action: 'approve' | 'reject'): void {
    selectedPayment.value = payment;
    approvalAction.value = action;
    approvalNotes.value = '';
    showApprovalModal.value = true;
}

async function handleApprovalSubmit(): Promise<void> {
    if (!selectedPayment.value || !approvalAction.value) {
        return;
    }

    if (approvalAction.value === 'reject' && !approvalNotes.value.trim()) {
        toast.error('Please provide a reason for rejection');
        return;
    }

    isSubmitting.value = true;

    try {
        const approvalActionIsApprove = approvalAction.value === 'approve';

        const responseData: any = approvalActionIsApprove
            ? await approvePayment(selectedPayment.value.id, approvalNotes.value || '')
            : await rejectPayment(selectedPayment.value.id, approvalNotes.value || '');

        console.log('responseData', responseData);
        responseData?.payment_status === 'rejected';

        const message =
            approvalAction.value === 'approve' ? 'Payment approved and credits applied!' : 'Payment rejected';

        showApprovalModal.value = false;
        selectedPayment.value = null;
        approvalNotes.value = '';
        approvalAction.value = null;

        toast.success(message);

        // Reload pending payments
        await loadPendingPayments(backendFilters.value);
    } catch (err) {
        toast.error(err instanceof Error ? err.message : 'Failed to process approval');
    } finally {
        isSubmitting.value = false;
    }
}

async function handleDownloadReceipt(): Promise<void> {
    if (!selectedPayment.value?.pix_receipt_path) {
        toast.error('No receipt available');
        return;
    }

    try {
        await downloadReceipt(selectedPayment.value.id);
    } catch {
        toast.error('Failed to download receipt');
    }
}

function formatCurrency(amount: string | number, currencyCode: string = 'USD'): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currencyCode,
    }).format(typeof amount === 'string' ? parseFloat(amount) : amount);
}

const backendFilters = computed<Record<string, unknown>>(() => {
    const params: Record<string, unknown> = {};

    if (selectedClientId.value) {
        params.client_id = selectedClientId.value;
    }

    if (selectedWalletId.value) {
        params.wallet_id = selectedWalletId.value;
    }

    if (selectedPaymentMethodKey.value) {
        params.payment_method = selectedPaymentMethodKey.value;
    }

    return params;
});

watch(
    backendFilters,
    (filters) => {
        loadPendingPayments(filters);
    },
    { immediate: true }
);

watch(selectedClientId, (clientId, oldClientId) => {
    if (clientId !== oldClientId) {
        selectedWalletId.value = null;
    }

    refreshWalletOptions(clientId ?? null);
});

function togglePaymentExpansion(paymentId: number): void {
    const newSet = new Set(expandedPaymentIds.value);

    if (newSet.has(paymentId)) {
        newSet.delete(paymentId);
    } else {
        newSet.add(paymentId);
    }

    expandedPaymentIds.value = newSet;
}

function isPaymentExpanded(paymentId: number): boolean {
    return expandedPaymentIds.value.has(paymentId);
}

async function handleRefreshList(): Promise<void> {
    expandedPaymentIds.value.clear();
    await loadPendingPayments(backendFilters.value);
}
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Approval Workflow</h1>
                <p class="text-sm text-gray-600">Manage and review pending purchase requests for client accounts.</p>
            </div>

            <button
                @click="handleRefreshList"
                :disabled="loading"
                class="inline-flex items-center gap-2 px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 whitespace-nowrap"
                aria-label="Refresh list"
            >
                <Icon icon="heroicons:arrow-path" :class="['w-4 h-4', loading && 'animate-spin']" />
                <span>{{ loading ? 'Refreshing...' : 'Refresh' }}</span>
            </button>
        </div>

        <!-- Error State -->
        <div v-if="error" class="mb-6 rounded-lg bg-red-100 p-4 text-red-700">
            {{ error }}
        </div>

        <!-- Filters -->
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                    <Icon icon="mdi:filter-variant" class="w-4 h-4 text-gray-500" />
                    Filters
                </div>

                <button
                    v-if="activeFiltersCount > 0"
                    type="button"
                    class="text-xs font-medium text-gray-600 hover:text-red-600 transition-colors"
                    @click="resetFilters"
                >
                    Clear all
                </button>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <CTypeahead
                    v-model="selectedClientId"
                    label="Client"
                    placeholder="Select a client"
                    clearable
                    loading-text="Loading clients..."
                    empty-text="No clients found"
                    :initial-options="clientOptions"
                    :refresh-options="refreshClientSearch"
                />

                <CTypeahead
                    v-model="selectedWalletId"
                    label="Wallet"
                    placeholder="Select a wallet"
                    clearable
                    loading-text="Loading wallets..."
                    empty-text="No wallets found"
                    :initial-options="walletOptions"
                />

                <CTypeahead
                    v-model="selectedPaymentMethodKey"
                    label="Payment Method"
                    placeholder="Select a method"
                    clearable
                    loading-text="Loading methods..."
                    empty-text="No methods found"
                    :initial-options="paymentMethodOptions"
                />
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="loading && pendingPayments.length === 0" class="py-12 text-center">
            <div class="inline-flex items-center gap-2">
                <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-red-600"></div>
                <span class="text-gray-600">Loading pending approvals...</span>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else-if="filteredPendingPayments.length === 0"
            class="rounded-lg border border-green-200 bg-green-50 p-12 text-center"
        >
            <Icon icon="mdi:check-circle" class="w-12 h-12 text-green-600 mx-auto mb-4" />
            <p class="text-green-900 font-semibold mb-2">
                {{ activeFiltersCount > 0 ? 'No Results Found' : 'No Pending Approvals' }}
            </p>
            <p class="text-sm text-green-700">
                {{
                    activeFiltersCount > 0
                        ? 'No pending approvals match the current filters'
                        : 'All offline payments have been reviewed'
                }}
            </p>
        </div>

        <!-- Pending Payments List -->
        <div v-else class="space-y-4">
            <div
                v-for="payment in filteredPendingPayments"
                :key="payment.id"
                class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden transition-all duration-300 hover:shadow-md"
            >
                <!-- Collapsed State -->
                <div
                    v-if="!isPaymentExpanded(payment.id)"
                    class="cursor-pointer px-5 sm:px-6 py-4 sm:py-5 flex items-center justify-between gap-3 sm:gap-4 hover:bg-gradient-to-r hover:from-gray-50 hover:to-transparent transition-all duration-200 group"
                    @click="togglePaymentExpansion(payment.id)"
                >
                    <!-- Left Section: Icon + Info -->
                    <div class="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                        <!-- Icon Badge -->
                        <div
                            class="flex-shrink-0 w-10 h-10 sm:w-12 sm:h-12 rounded-lg bg-gradient-to-br from-red-100 to-red-50 flex items-center justify-center border border-red-200 shadow-sm"
                        >
                            <Icon icon="heroicons:shopping-cart" class="w-5 h-5 sm:w-6 sm:h-6 text-red-600" />
                        </div>

                        <!-- Purchase Info -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-baseline gap-1.5 flex-wrap">
                                <p class="text-sm sm:text-base font-bold text-gray-900 tabular-nums">
                                    {{ payment.creditPurchase.total_hours }}h
                                </p>
                                <p class="text-xs sm:text-sm text-gray-500 font-medium">Purchase</p>
                            </div>
                            <p class="text-xs sm:text-sm text-gray-600 truncate mt-1">
                                {{ payment.creditPurchase.customer?.name }}
                            </p>
                        </div>
                    </div>

                    <!-- Right Section: Price + Actions -->
                    <div class="flex items-center gap-2 sm:gap-3 flex-shrink-0">
                        <!-- Price Display - Desktop -->
                        <div class="text-right hidden sm:block pr-2 border-r border-gray-200">
                            <p class="text-lg sm:text-xl font-bold text-gray-900 tabular-nums">
                                {{
                                    formatCurrency(
                                        payment.creditPurchase.total_price,
                                        payment.creditPurchase.currency_code
                                    )
                                }}
                            </p>
                        </div>

                        <!-- Action Buttons - Desktop -->
                        <div class="hidden sm:flex items-center gap-0.5">
                            <button
                                @click.stop="openApprovalModal(payment, 'reject')"
                                class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2"
                                title="Reject request"
                                aria-label="Reject request"
                            >
                                <Icon icon="heroicons:x-mark" class="w-5 h-5" />
                            </button>

                            <button
                                @click.stop="openApprovalModal(payment, 'approve')"
                                class="p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                                title="Approve request"
                                aria-label="Approve request"
                            >
                                <Icon icon="heroicons:check-circle" class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Expand Button -->
                        <button
                            @click.stop="togglePaymentExpansion(payment.id)"
                            class="p-2 sm:p-2.5 text-gray-600 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 border border-transparent hover:border-blue-200"
                            title="View details"
                            aria-label="Expand details"
                        >
                            <Icon
                                icon="heroicons:chevron-down"
                                class="w-5 h-5 sm:w-5 sm:h-5 group-hover:translate-y-0.5 transition-transform duration-200"
                            />
                        </button>
                    </div>

                    <!-- Price Display - Mobile -->
                    <div class="sm:hidden text-right -mr-1">
                        <p class="text-sm font-bold text-gray-900 tabular-nums">
                            {{
                                formatCurrency(payment.creditPurchase.total_price, payment.creditPurchase.currency_code)
                            }}
                        </p>
                    </div>
                </div>

                <!-- Expanded State -->
                <div v-if="isPaymentExpanded(payment.id)">
                    <!-- Header with Collapse -->
                    <div
                        class="px-5 sm:px-6 py-4 sm:py-5 bg-gradient-to-r from-red-50 via-red-50 to-orange-50 border-b border-red-100 flex items-center justify-between gap-4 sm:gap-6"
                    >
                        <div class="flex items-center gap-3 sm:gap-4 flex-1 min-w-0">
                            <div
                                class="flex-shrink-0 w-12 h-12 rounded-lg bg-gradient-to-br from-red-100 to-red-50 flex items-center justify-center border border-red-200 shadow-sm"
                            >
                                <Icon icon="heroicons:shopping-cart" class="w-6 h-6 text-red-600" />
                            </div>

                            <div class="flex-1 min-w-0">
                                <h3 class="text-base sm:text-lg font-bold text-gray-900">
                                    {{ payment.creditPurchase.total_hours }}h Purchase
                                </h3>
                                <p class="text-xs sm:text-sm text-gray-600 truncate mt-0.5">
                                    {{ payment.creditPurchase.customer?.name }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 sm:gap-4 flex-shrink-0">
                            <div class="text-right hidden sm:block pr-3 border-r border-red-200">
                                <p class="text-xl sm:text-2xl font-bold text-gray-900 tabular-nums">
                                    {{
                                        formatCurrency(
                                            payment.creditPurchase.total_price,
                                            payment.creditPurchase.currency_code
                                        )
                                    }}
                                </p>
                            </div>

                            <button
                                @click="togglePaymentExpansion(payment.id)"
                                class="p-2 sm:p-2.5 text-gray-600 hover:text-gray-900 hover:bg-white rounded-lg transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 border border-transparent hover:border-red-200"
                                title="Collapse details"
                                aria-label="Collapse details"
                            >
                                <Icon
                                    icon="heroicons:chevron-up"
                                    class="w-5 h-5 sm:w-6 sm:h-6 group-hover:-translate-y-0.5 transition-transform duration-200"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Price Display - Mobile -->
                    <div class="sm:hidden px-5 py-3 bg-red-50 border-b border-red-100 text-right">
                        <p class="text-lg font-bold text-gray-900 tabular-nums">
                            {{
                                formatCurrency(payment.creditPurchase.total_price, payment.creditPurchase.currency_code)
                            }}
                        </p>
                    </div>

                    <!-- Details Section -->
                    <div class="p-6 space-y-6 bg-red-50">
                        <!-- Project & Consultancy Details -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                    Project Details
                                </p>
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ payment.creditPurchase.wallet?.name }}
                                </p>
                                <p class="text-sm text-gray-600">Consultancy Work</p>
                            </div>

                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                    Consultancy Rate
                                </p>
                                <p class="text-sm text-gray-900">
                                    {{
                                        formatCurrency(
                                            (Number(payment.creditPurchase.total_price) || 0) /
                                                (Number(payment.creditPurchase.total_hours) || 1),
                                            payment.creditPurchase.currency_code
                                        )
                                    }}
                                    / hour
                                </p>
                            </div>
                        </div>

                        <!-- Billing Wallet & Attachments -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2 border-t border-red-200">
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                    Billing Wallet
                                </p>
                                <div class="flex items-center gap-2">
                                    <Icon icon="heroicons:credit-card" class="w-4 h-4 text-gray-600" />
                                    <p class="text-sm text-gray-900 font-medium">
                                        {{ payment.creditPurchase.wallet?.name }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                                    Attachments
                                </p>
                                <div v-if="payment.pix_receipt_path" class="flex items-center gap-2">
                                    <Icon icon="heroicons:document-arrow-down" class="w-4 h-4 text-red-600" />
                                    <button
                                        @click="
                                            selectedPayment = payment;
                                            handleDownloadReceipt();
                                        "
                                        class="text-sm text-red-600 hover:text-red-700 font-medium"
                                    >
                                        View Receipt
                                    </button>
                                </div>
                                <div v-else class="text-sm text-gray-500">No attachments</div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="p-6 bg-white border-t border-gray-200 flex flex-col sm:flex-row gap-3">
                        <CButton
                            preset="outlined-black"
                            @click="openApprovalModal(payment, 'reject')"
                            icon="heroicons:x-mark"
                            class="flex-1"
                        >
                            Reject
                        </CButton>

                        <CButton
                            preset="success"
                            @click="openApprovalModal(payment, 'approve')"
                            icon="heroicons:check-circle"
                            class="flex-1"
                        >
                            Approve & Apply Credits
                        </CButton>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approval Modal -->
    <Teleport to="body">
        <div
            v-if="showApprovalModal && selectedPayment && approvalAction"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            @click.self="showApprovalModal = false"
        >
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md" @click.stop>
                <!-- Header -->
                <div
                    :class="[
                        'px-6 py-4 rounded-t-lg',
                        {
                            'bg-green-600': approvalAction === 'approve',
                            'bg-red-600': approvalAction === 'reject',
                        },
                    ]"
                >
                    <h2 class="text-xl font-bold text-white">
                        {{ approvalAction === 'approve' ? 'Approve Payment' : 'Reject Payment' }}
                    </h2>
                </div>

                <!-- Content -->
                <div class="px-4 py-4 space-y-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600 mb-2">
                            {{ selectedPayment.creditPurchase?.customer?.name }}
                        </p>

                        <p class="text-lg font-bold text-gray-900">
                            {{ selectedPayment.creditPurchase?.total_hours }}h for
                            {{
                                formatCurrency(
                                    selectedPayment.creditPurchase?.total_price || 0,
                                    selectedPayment.creditPurchase?.currency_code
                                )
                            }}
                        </p>
                    </div>

                    <!-- Notes Field -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ approvalAction === 'reject' ? 'Reason for Rejection' : 'Admin Notes (Optional)' }}
                        </label>

                        <textarea
                            v-model="approvalNotes"
                            :placeholder="
                                approvalAction === 'reject'
                                    ? 'Explain why this payment is being rejected...'
                                    : 'Add any notes...'
                            "
                            rows="3"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-red-600 focus:outline-none"
                        ></textarea>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 flex gap-3 justify-end rounded-b-lg border-t border-gray-200">
                    <CButton preset="outlined-black" @click="showApprovalModal = false" :disabled="isSubmitting">
                        Cancel
                    </CButton>

                    <CButton
                        :preset="approvalAction === 'approve' ? 'success' : 'danger'"
                        @click="handleApprovalSubmit"
                        :disabled="isSubmitting || (approvalAction === 'reject' && !approvalNotes.trim())"
                        :loading="isSubmitting"
                    >
                        {{ approvalAction === 'approve' ? 'Approve' : 'Reject' }}
                    </CButton>
                </div>
            </div>
        </div>
    </Teleport>
</template>
