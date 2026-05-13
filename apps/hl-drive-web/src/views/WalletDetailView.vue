<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useWallets } from '@/composables/useWallets';
import { useLedger } from '@/composables/useLedger';
import { useTags } from '@/composables/useTags';
import { usePermissions } from '@/composables/usePermissions';
import { useAuth } from '@/composables/useAuth';
import { useToast } from '@/composables/useToast';
import { formatHoursDisplay, splitDecimalHours, combineDualTimeInput } from '@/utils/timeFormatters';
import TagInput from '@/components/TagInput.vue';
import WalletEditModal from '@/components/WalletEditModal.vue';
import CCreditPurchaseModal from '@/components/CCreditPurchaseModal.vue';
import type {
    LedgerEntryForm,
    User,
    WalletWithBalance,
    ClientUserForm,
    UpdateClientUserForm,
    TimezoneConfig,
    TypeaheadOption,
    Wallet,
} from '@/types';

import { useTimerStore } from '@/stores/timer';
import { useDate } from '@/composables/useDate';
import { getTimezoneList, TZ_DEFAULT } from '@/utils/date-helpers';
const timerStore = useTimerStore();
const startingTimerId = ref<number | null>(null);

const route = useRoute();
const router = useRouter();
const toast = useToast();
const auth = useAuth();
const walletId = Number(route.params.id);
const routeAction: any = route.query?.action || null;

const {
    wallet,
    entries,
    loading: walletLoading,
    error: walletError,
    pagination,
    fetchWallet,
    fetchWalletEntries,
    toggleInternalNoteVisibility,
    isInternalNoteVisible,
    hasInternalNotePermission,
} = useWallets();

const { createEntry, loading: entryLoading } = useLedger();
const { fetchTags } = useTags();
const {
    canBuyCredits,
    canAddCredits,
    canAddDebits,
    canAddAdjustments,
    canManageWallets,
    canManageClients,
    hasPermission,
} = usePermissions();

const canAddEntry = computed(() => {
    return canAddCredits.value || canAddDebits.value || canAddAdjustments.value;
});

const canEditWallet = computed(() => {
    // Customers cannot edit wallets
    if (auth.isCustomer.value) {
        return false;
    }

    return true;
});

const showEntryModal = ref(false);
const showEditModal = ref(false);
const showBuyCreditsModal = ref(false);
// getTimezone -> useAuth
const { targetTimezone, resolveTimezone } = useDate();
const entryForm = ref<LedgerEntryForm>({
    wallet_id: walletId,
    type: 'debit',
    hours: 0,
    title: '',
    description: '',
    reference_date: new Date().toISOString().split('T')[0],
    reference_date_timezone: targetTimezone.value || TZ_DEFAULT,
    tags: [],
});

// Separate hours and minutes inputs for better UX
const entryFormHours = ref(0);
const entryFormMinutes = ref(0);

// Sync dual time input to decimal hours
watch([entryFormHours, entryFormMinutes], ([h, m]) => {
    entryForm.value.hours = combineDualTimeInput(h, m);
});

const currentBalance = computed(() => wallet.value?.balance || '0');

onMounted(async () => {
    try {
        await fetchWallet(walletId);

        // Verify ownership for customers
        if (auth.isCustomer.value && wallet.value) {
            if (!auth.canAccessWallet(wallet.value)) {
                toast.error('You do not have access to this wallet');
                router.push({ name: 'customer-dashboard' });

                return;
            }
        }

        await Promise.all([fetchWalletEntries(walletId), fetchTags()]);

        handleRouteAction();
    } catch (e) {
        toast.error('Failed to load wallet');
    }
});

async function handleCreateEntry() {
    if (entryForm.value.hours <= 0) {
        return;
    }

    try {
        const response = await createEntry(entryForm.value);

        closeEntryModal();

        if (wallet.value) {
            wallet.value.balance = response.new_balance;
        }

        fetchWalletEntries(walletId);
    } catch {
        // Error handled in composable
    }
}

function openEntryModal(): void {
    const { hours, minutes } = splitDecimalHours(entryForm.value.hours);

    entryFormHours.value = hours;
    entryFormMinutes.value = minutes;
    showEntryModal.value = true;
}

function closeEntryModal(): void {
    showEntryModal.value = false;
    entryForm.value = {
        wallet_id: walletId,
        type: 'debit',
        hours: 0,
        title: '',
        description: '',
        reference_date: new Date().toISOString().split('T')[0],
        tags: [],
    };
    entryFormHours.value = 0;
    entryFormMinutes.value = 0;
}

function isManualEntryQueryActive(value: unknown): boolean {
    if (value === undefined || value === null) {
        return false;
    }

    if (Array.isArray(value)) {
        return value.some((item) => isManualEntryQueryActive(item));
    }

    const normalized = String(value).toLowerCase().trim();

    return ['1', 'true', 'manual'].includes(normalized);
}

function clearManualEntryQuery(): void {
    if (!route.query.manual_entry) {
        return;
    }

    const updatedQuery = { ...route.query };
    delete updatedQuery.manual_entry;

    void router.replace({ query: updatedQuery });
}

watch(
    () => route.query.manual_entry,
    (value) => {
        if (!isManualEntryQueryActive(value)) {
            return;
        }

        if (!canAddEntry.value) {
            toast.error('Você não tem permissão para adicionar entradas manualmente.');
            clearManualEntryQuery();
            return;
        }

        openEntryModal();
        clearManualEntryQuery();
    },
    { immediate: true }
);

function formatBalance(balance: string): string {
    const num = parseFloat(balance);

    return formatHoursDisplay(num, true);
}

function getBalanceColor(balance: string): string {
    const num = parseFloat(balance);

    if (num > 0) {
        return 'text-green-600';
    }

    if (num < 0) {
        return 'text-red-600';
    }

    return 'text-gray-600';
}

// ─── Timer Management ────────────────────────────────────────────────────────

async function handleStartTimer(wallet: WalletWithBalance, event: Event): Promise<void> {
    event.stopPropagation();

    startingTimerId.value = wallet.id;

    try {
        const success = await timerStore.startTimer({
            wallet_id: wallet.id,
        });

        if (success) {
            toast.success('Timer started successfully');
        } else {
            const errorMessage = timerStore.error || 'Failed to start timer';

            toast.error(errorMessage);
        }
    } catch (err: any) {
        const errorMessage = err?.response?.data?.message || err?.message || 'Failed to start timer';

        toast.error(errorMessage);
    } finally {
        startingTimerId.value = null;
    }
}

function formatHours(hours: string): string {
    const num = parseFloat(hours);

    return formatHoursDisplay(num, true);
}

function getHoursColor(hours: string): string {
    const num = parseFloat(hours);

    if (num > 0) {
        return 'text-green-600';
    }

    return 'text-red-600';
}

function formatDate(date: string | null): string {
    if (!date) {
        return '-';
    }

    return new Date(date).toLocaleDateString();
}

function handleRouteAction(): void {
    let _routeAction: any = typeof routeAction === 'string' ? routeAction?.trim() : null;

    if (!_routeAction) {
        return;
    }

    if (['buy_credits'].includes(_routeAction)) {
        showBuyCreditsModal.value = Boolean(wallet.value?.credit_purchase_allowed && canBuyCredits.value);
        return;
    }
}

function handleWalletUpdated(): void {
    // Refresh wallet data after update
    fetchWallet(walletId);
}

// ─── Navigation ─────────────────────────────────────────────────────────────

function goToWallet(wallet: Wallet, query: any = undefined): void {
    router.push({ name: 'wallet-detail', query: query || {}, params: { id: wallet.id } });
}

function goToReports(): void {
    router.push({ name: 'reports' });
}

function goPaymentHistory(wallet: Wallet | null, query: any = undefined): void {
    query = query || {};

    router.push({
        name: 'payment-history',
        query,
    });
}

function handleCreditPurchaseSuccess(): void {
    // Refresh wallet data and entries after credit purchase
    fetchWallet(walletId);
    fetchWalletEntries(walletId);
    toast.success('Credit purchase created successfully!');
    // router.push()

    goPaymentHistory(null, { wallet_id: walletId });
}

function onValueFormHours(event: Event) {
    if (!event?.target) {
        return;
    }

    let max = Number((event?.target as any)?.getAttribute('max')) || null;
    /** @ts-ignore */
    let value = (event?.target || (null as any))?.value || 0;

    console.log('onValueFormHours', value);

    value = typeof value === 'string' ? value.replace(/\D/g, '') : '';

    if (!value || value === '') {
        console.log('onValueFormHours if', value);
        entryFormHours.value = 0;
        /** @ts-ignore */
        event.target.value = entryFormHours.value;
        return;
    }

    value = parseInt(value, 10);

    if (!max || max <= 0) {
        entryFormHours.value = value;
        /** @ts-ignore */
        event.target.value = entryFormHours.value;
        return;
    }

    entryFormHours.value = value >= max ? max : value;
    /** @ts-ignore */
    event.target.value = entryFormHours.value;
}

function onValueFormMinutes(event: Event) {
    if (!event?.target) {
        return;
    }

    let max = Number((event?.target as any)?.getAttribute('max')) || null;
    /** @ts-ignore */
    let value = (event?.target || (null as any))?.value || 0;

    console.log('onValueFormMinutes', value);

    value = typeof value === 'string' ? value.replace(/\D/g, '') : '';

    if (!value || value === '') {
        console.log('onValueFormMinutes if', value);
        entryFormMinutes.value = 0;
        /** @ts-ignore */
        event.target.value = entryFormMinutes.value;
        return;
    }

    value = parseInt(value, 10);

    if (!max || max <= 0) {
        entryFormMinutes.value = value;
        /** @ts-ignore */
        event.target.value = entryFormMinutes.value;
        return;
    }

    entryFormMinutes.value = value >= max ? max : value;
    /** @ts-ignore */
    event.target.value = entryFormMinutes.value;
}

const timezoneList = computed(() => {
    return getTimezoneList((tzList: Record<string, TimezoneConfig>): { label: string; value: string }[] => {
        // return [{label: 'string', value: 'string'}];

        return Object.entries(tzList)
            .map((i) => i[1])
            .map((i) => ({
                ...i,
                label: `${i.label} (${i.offset})`,
                value: i.timezone_id,
            }));
    });
});

function refreshTzListOptions({ searchTerm }: { searchTerm: string | null }): TypeaheadOption[] {
    const items = (timezoneList.value || []) as TypeaheadOption[];

    searchTerm = typeof searchTerm === 'string' ? searchTerm.trim().toLowerCase() : '';

    return searchTerm
        ? items.filter((i: any) => {
              return JSON.stringify(i, null, 0).toLowerCase().includes(searchTerm);
          })
        : items;
}
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <button
            class="mb-4 inline-flex items-center gap-1 text-sm font-medium text-gray-600 hover:text-gray-900 transition-colors"
            @click="router.back()"
        >
            ← Back
        </button>

        <div v-if="walletError" class="mb-4 rounded-lg bg-red-100 p-4 text-red-700">
            {{ walletError }}
        </div>

        <div v-if="walletLoading && !wallet" class="py-8 text-center">Loading...</div>

        <div v-else-if="wallet">
            <div class="mb-6 rounded-lg bg-white p-6 shadow">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-gray-500">{{ wallet.client?.name }}</p>
                        <h1 class="text-2xl font-bold text-gray-900">{{ wallet.name }}</h1>
                        <p v-if="wallet.description" class="mt-1 text-gray-600">
                            {{ wallet.description }}
                        </p>
                        <p v-if="wallet.hourly_rate_reference" class="mt-1 text-sm text-gray-500">
                            Rate: {{ wallet.currency_code || 'USD' }} {{ wallet.hourly_rate_reference }}/h
                        </p>
                        <!-- Internal Note display (permission + toggle) - Hidden for customers -->
                        <div
                            v-if="!auth.isCustomer.value && hasInternalNotePermission() && wallet.internal_note"
                            class="mt-4"
                        >
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-sm font-semibold text-gray-700">Internal Note</label>
                                <button
                                    @click="toggleInternalNoteVisibility(wallet.id)"
                                    class="p-1 text-gray-500 hover:text-gray-700 transition-colors"
                                    :title="isInternalNoteVisible(wallet.id) ? 'Hide' : 'Show'"
                                >
                                    <svg
                                        v-if="isInternalNoteVisible(wallet.id)"
                                        class="w-5 h-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                        />
                                    </svg>
                                    <svg v-else class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.953 9.953 0 012.223-3.412"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 3l18 18"
                                        />
                                    </svg>
                                </button>
                            </div>

                            <div
                                v-if="isInternalNoteVisible(wallet.id)"
                                class="p-3 bg-gray-50 rounded border border-gray-200 text-sm"
                            >
                                {{ wallet.internal_note }}
                            </div>
                            <div v-else class="text-gray-500 italic text-sm">Internal note hidden</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="mb-4 flex justify-end gap-2">
                            <div class="ml-auto flex items-center gap-2">
                                <CButton
                                    class="inline-flex items-center gap-1 text-sm"
                                    icon="mdi:play-outline"
                                    :disabled="startingTimerId === wallet.id"
                                    @click="handleStartTimer(wallet, $event)"
                                >
                                    {{ startingTimerId === wallet.id ? 'Starting...' : 'Start Timer' }}
                                </CButton>

                                <CButton
                                    v-if="canEditWallet || canManageWallets"
                                    preset="gray"
                                    @click="showEditModal = true"
                                    icon="hugeicons:pencil-edit-01"
                                >
                                    Edit
                                </CButton>
                            </div>
                            <CButton
                                v-if="wallet?.credit_purchase_allowed && canBuyCredits"
                                preset="green"
                                @click="showBuyCreditsModal = true"
                                icon="hugeicons:add-money-circle"
                            >
                                Buy Credits
                            </CButton>
                        </div>
                        <p class="text-sm text-gray-500">Current Balance</p>
                        <p class="text-3xl font-bold" :class="getBalanceColor(currentBalance)">
                            {{ formatBalance(currentBalance) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-900">Ledger Entries</h2>
                <CButton v-if="canAddEntry" @click="openEntryModal" icon="hugeicons:add-circle">Add Entry</CButton>
            </div>

            <div class="overflow-hidden rounded-xl bg-white border border-gray-200 shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Date
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Title
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                Tags
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                Hours
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="entry in entries" :key="entry.id">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                <!-- {{ formatDate(entry.reference_date) }} -->
                                <DateDisplay
                                    :value="entry.reference_date"
                                    :pattern="!true ? 'br-date' : 'iso-date'"
                                    :format="{
                                        year: 'numeric',
                                        month: '2-digit',
                                        day: '2-digit',
                                        // hour: '2-digit',
                                        // minute: '2-digit',
                                        // second: '2-digit',
                                    }"
                                />
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <div>{{ entry.title || '-' }}</div>
                                <div v-if="entry.description" class="text-xs text-gray-500">
                                    {{ entry.description }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-for="tag in entry.tags"
                                        :key="tag.id"
                                        class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600"
                                    >
                                        {{ tag.name }}
                                    </span>
                                </div>
                            </td>
                            <td
                                class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium"
                                :class="getHoursColor(entry.hours)"
                            >
                                {{ formatHours(entry.hours) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="!entries.length" class="py-8 text-center text-gray-500">No entries yet.</div>

            <div v-if="pagination.lastPage > 1" class="mt-4 flex items-center justify-center gap-2">
                <button
                    :disabled="pagination.currentPage === 1"
                    class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    @click="fetchWalletEntries(walletId, pagination.currentPage - 1)"
                >
                    Previous
                </button>
                <span class="text-sm text-gray-500">
                    Page {{ pagination.currentPage }} of {{ pagination.lastPage }}
                </span>
                <button
                    :disabled="pagination.currentPage === pagination.lastPage"
                    class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-sm font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
                    @click="fetchWalletEntries(walletId, pagination.currentPage + 1)"
                >
                    Next
                </button>
            </div>
        </div>

        <!-- Edit Wallet Modal -->
        <WalletEditModal
            :show="showEditModal"
            :wallet="wallet"
            @close="showEditModal = false"
            @updated="handleWalletUpdated"
        />

        <!-- Credit Purchase Modal -->
        <CCreditPurchaseModal
            :show="showBuyCreditsModal"
            :wallet="wallet"
            @close="showBuyCreditsModal = false"
            @success="handleCreditPurchaseSuccess"
        />

        <!-- Add Entry Modal -->
        <div v-if="showEntryModal" class="fixed inset-0 flex items-center justify-center bg-black/50 pt-4 z-40">
            <div class="w-full mx-2 md:mx-auto max-w-2xl rounded-lg bg-white p-2">
                <h2 class="mb-4 text-lg font-semibold">Add Ledger Entry</h2>

                <div class="max-h-[70vh] md:max-h-[60vh] overflow-y-auto px-2 rounded">
                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium text-gray-700">Type</label>
                        <div class="flex gap-3">
                            <!-- Debit Card -->
                            <label v-if="canAddDebits" class="flex-1 cursor-pointer">
                                <input v-model="entryForm.type" type="radio" value="debit" class="sr-only" />
                                <div
                                    :class="[
                                        'flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-all',
                                        entryForm.type === 'debit'
                                            ? 'border-red-500 bg-red-50'
                                            : 'border-gray-200 bg-white hover:border-red-300',
                                    ]"
                                >
                                    <svg
                                        class="h-6 w-6 text-red-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13 13h8m-8-6h8M5 5a2 2 0 012-2h.01A2 2 0 019 5v.01A2 2 0 017.01 9H7a2 2 0 01-2-2V5z"
                                        />
                                    </svg>
                                    <div class="text-center">
                                        <p class="text-sm font-semibold text-gray-900">Debit</p>
                                        <p class="text-xs text-gray-600">Consume hours</p>
                                    </div>
                                </div>
                            </label>

                            <!-- Credit Card -->
                            <label v-if="canAddCredits" class="flex-1 cursor-pointer">
                                <input v-model="entryForm.type" type="radio" value="credit" class="sr-only" />
                                <div
                                    :class="[
                                        'flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-all',
                                        entryForm.type === 'credit'
                                            ? 'border-green-500 bg-green-50'
                                            : 'border-gray-200 bg-white hover:border-green-300',
                                    ]"
                                >
                                    <svg
                                        class="h-6 w-6 text-green-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 4v16m8-8H4"
                                        />
                                    </svg>
                                    <div class="text-center">
                                        <p class="text-sm font-semibold text-gray-900">Credit</p>
                                        <p class="text-xs text-gray-600">Add hours</p>
                                    </div>
                                </div>
                            </label>

                            <!-- Adjustment Card -->
                            <label v-if="canAddAdjustments" class="flex-1 cursor-pointer">
                                <input v-model="entryForm.type" type="radio" value="adjustment" class="sr-only" />
                                <div
                                    :class="[
                                        'flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-all',
                                        entryForm.type === 'adjustment'
                                            ? 'border-amber-500 bg-amber-50'
                                            : 'border-gray-200 bg-white hover:border-amber-300',
                                    ]"
                                >
                                    <svg
                                        class="h-6 w-6 text-amber-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 7h6m0 10v-3m-3 3v.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <div class="text-center">
                                        <p class="text-sm font-semibold text-gray-900">Adjustment</p>
                                        <p class="text-xs text-gray-600">Correction</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <template v-if="entryForm.type">
                        <div class="mb-4 overflow-visible">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tags</label>
                            <TagInput v-model="entryForm.tags" placeholder="Add tags..." :allow-create="true" />
                        </div>

                        <div class="flex flex-col md:flex-row gap-3 mb-4">
                            <div class="mb-4 w-full flex flex-col justify-around">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Reference Date</label>
                                <input
                                    v-model="entryForm.reference_date"
                                    type="date"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                                />
                            </div>

                            <CTypeahead
                                containerClasses="mb-4 w-full"
                                v-model="entryForm.reference_date_timezone"
                                :initialValue="entryForm.reference_date_timezone || targetTimezone || TZ_DEFAULT"
                                label="Timezone"
                                placeholder="Search timezone..."
                                clearable
                                :initial-options="timezoneList"
                                :refresh-options="refreshTzListOptions"
                                empty-text="No clients found"
                                loading-text="Loading clients..."
                            />
                        </div>

                        <div class="flex flex-col md:flex-row gap-3 mb-4">
                            <div class="mb-4 w-full">
                                <label class="mb-2 block text-sm font-medium text-gray-700">Time</label>
                                <div class="flex gap-3">
                                    <div class="flex-1">
                                        <label class="mb-1 block text-xs text-gray-600">Hours</label>
                                        <input
                                            :value="entryFormHours"
                                            @blur="onValueFormHours"
                                            @keypress="onValueFormHours"
                                            @input="onValueFormHours"
                                            type="number"
                                            min="0"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                                            placeholder="0"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <label class="mb-1 block text-xs text-gray-600">Minutes</label>
                                        <input
                                            :value="entryFormMinutes"
                                            @blur="onValueFormMinutes"
                                            @keypress="onValueFormMinutes"
                                            @input="onValueFormMinutes"
                                            type="number"
                                            min="0"
                                            max="59"
                                            step="5"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                                            placeholder="0"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Title</label>
                            <input
                                v-model="entryForm.title"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                            />
                        </div>

                        <div class="mb-4">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                            <textarea
                                v-model="entryForm.description"
                                rows="2"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                            ></textarea>
                        </div>
                    </template>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        class="rounded-lg bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200"
                        @click="closeEntryModal"
                    >
                        Cancel
                    </button>
                    <button
                        :disabled="!entryForm.type || entryLoading"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50 transition-colors"
                        @click="handleCreateEntry"
                    >
                        {{ entryLoading ? 'Saving...' : 'Save' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
