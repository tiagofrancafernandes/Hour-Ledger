<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';
import { useCustomerData } from '@/composables/useCustomerData';
import { usePermissions } from '@/composables/usePermissions';
import { useWallets } from '@/composables/useWallets';
import { useToast } from '@/composables/useToast';
import { useTimerStore } from '@/stores/timer';
import { formatHoursDisplay } from '@/utils/timeFormatters';
import type { Wallet } from '@/types';

const router = useRouter();
const toast = useToast();
const timerStore = useTimerStore();
const { canCreateWallets, canBuyCredits, canAddEntry, canCreateTimers } = usePermissions();
const { myClient, myWallets, loading, error, myWalletsSummary, fetchMyClient, fetchMyWallets } = useCustomerData();
const { createWallet, loading: walletLoading } = useWallets();
const startingTimerId = ref<number | null>(null);

// ─── Create Wallet Modal ─────────────────────────────────────────────────────

const showCreateModal = ref(false);

const createForm = ref({
    name: '',
    description: '',
    currency_code: 'BRL',
});

const createFormErrors = ref<Record<string, string>>({});

function openCreateModal(): void {
    createForm.value = { name: '', description: '', currency_code: 'BRL' };
    createFormErrors.value = {};
    showCreateModal.value = true;
}

function closeCreateModal(): void {
    showCreateModal.value = false;
}

async function handleCreateWallet(): Promise<void> {
    createFormErrors.value = {};

    if (!createForm.value.name.trim()) {
        createFormErrors.value.name = 'Name is required';

        return;
    }

    if (!myClient.value) {
        return;
    }

    try {
        await createWallet({
            client_id: myClient.value.id,
            name: createForm.value.name.trim(),
            description: createForm.value.description.trim() || undefined,
            currency_code: createForm.value.currency_code.trim() || undefined,
        });

        toast.success('Wallet created successfully');

        closeCreateModal();
        await fetchMyWallets();
    } catch (e: any) {
        const errors = e?.response?.data?.errors;

        if (errors) {
            Object.keys(errors).forEach((field) => {
                createFormErrors.value[field] = errors[field][0];
            });
        } else {
            toast.error('Failed to create wallet');
        }
    }
}

// ─── Timer Management ────────────────────────────────────────────────────────

async function handleStartTimer(wallet: Wallet, event: Event): Promise<void> {
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

function goBuyCredits(wallet: Wallet): void {
    if (!wallet?.id) {
        toast.error('Invalid wallet');
        return;
    }

    if (!wallet?.credit_purchase_allowed) {
        toast.error('This wallet does not allow the purchase of credits');
        return;
    }

    let query = { action: 'buy_credits' };

    goToWallet(wallet, query);
}

// ─── Formatters ─────────────────────────────────────────────────────────────

function formatBalance(balance: string | number): string {
    const num = typeof balance === 'string' ? parseFloat(balance) : balance;

    return formatHoursDisplay(num, false);
}

function formatMonthYear(dateString: string | null | undefined): string {
    if (!dateString) {
        return '-';
    }

    const date = new Date(dateString);

    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
}

function getClientSinceDate(): string {
    if (myClient.value?.customer_since) {
        return formatMonthYear(myClient.value.customer_since);
    }

    return formatMonthYear(myClient.value?.created_at);
}

// ─── Init ────────────────────────────────────────────────────────────────────

onMounted(async () => {
    try {
        await fetchMyClient();
        await fetchMyWallets();
    } catch (e) {
        toast.error('Failed to load client data');
    }
});
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <UIPageHeader title="My Dashboard" description="View your wallet data and reports.">
            <template v-slot:actions>
                <CButton preset="primary" icon="mdi:chart-line" @click="goToReports">Reports</CButton>
            </template>
        </UIPageHeader>

        <!-- Loading State -->
        <div v-if="loading" class="flex justify-center py-12">
            <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-300 border-t-red-600"></div>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="rounded-lg border border-red-300 bg-red-50 p-4 text-red-800">
            {{ error }}
        </div>

        <!-- Main Content -->
        <div v-else class="space-y-8">
            <!-- Client Info Section -->
            <div v-if="myClient" class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-2xl font-bold text-gray-900">{{ myClient.name }}</h2>

                <div class="space-y-2 text-gray-700">
                    <p v-if="myClient.notes">
                        <strong>Notes:</strong>
                        {{ myClient.notes }}
                    </p>

                    <p>
                        <strong>Client since:</strong>
                        {{ getClientSinceDate() }}
                    </p>
                </div>
            </div>

            <!-- Wallets Summary Section -->
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-600">Total Wallets</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ myWalletsSummary.totalWallets }}</p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-600">Total Balance</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ formatBalance(myWalletsSummary.totalBalance) }}h
                    </p>
                </div>

                <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-sm text-gray-600">Average per Wallet</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{
                            formatBalance(
                                myWalletsSummary.totalWallets > 0
                                    ? myWalletsSummary.totalBalance / myWalletsSummary.totalWallets
                                    : 0
                            )
                        }}h
                    </p>
                </div>
            </div>

            <!-- Wallets List Section -->
            <div class="space-y-4">
                <!-- Section header -->
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900">My Wallets</h3>

                    <CButton v-if="canCreateWallets" preset="primary" icon="mdi:plus" @click="openCreateModal">
                        Add Wallet
                    </CButton>
                </div>

                <!-- Empty State -->
                <div v-if="myWallets.length === 0" class="rounded-lg border border-gray-200 bg-gray-50 p-8 text-center">
                    <Icon icon="mdi:wallet-outline" class="mx-auto mb-3 text-5xl text-gray-300" />
                    <p class="text-gray-600">No wallets available</p>
                </div>

                <!-- Wallets Grid -->
                <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="wallet in myWallets"
                        :key="wallet.id"
                        class="flex flex-col rounded-lg border border-gray-200 bg-white shadow-sm transition hover:shadow-md"
                    >
                        <!-- Card Body -->
                        <div class="flex flex-1 items-start gap-3 p-4">
                            <!-- Wallet Icon -->
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50">
                                <Icon icon="mdi:wallet" class="text-xl text-red-600" />
                            </div>

                            <!-- Info -->
                            <div class="min-w-0 flex-1">
                                <h4 class="truncate font-semibold text-gray-900">{{ wallet.name }}</h4>

                                <p v-if="wallet.description" class="mt-0.5 truncate text-xs text-gray-500">
                                    {{ wallet.description }}
                                </p>

                                <div class="mt-2 flex items-baseline gap-1">
                                    <span
                                        :class="[
                                            'text-lg font-bold',
                                            {
                                                'text-green-600': parseFloat(wallet.balance) >= 0,
                                                'text-red-600': parseFloat(wallet.balance) < 0,
                                            },
                                        ]"
                                    >
                                        {{ formatBalance(wallet.balance) }}h
                                    </span>

                                    <span class="text-xs text-gray-400">balance</span>
                                </div>

                                <p v-if="wallet.currency_code" class="mt-0.5 text-xs text-gray-400">
                                    {{ wallet.currency_code }}
                                </p>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="flex gap-2 border-t border-gray-100 px-4 py-3">
                            <CButton
                                v-if="canCreateTimers"
                                preset="primary"
                                class="flex-1 justify-center text-xs"
                                icon="mdi:play-outline"
                                :disabled="startingTimerId === wallet.id"
                                @click="handleStartTimer(wallet, $event)"
                            >
                                {{ startingTimerId === wallet.id ? 'Starting...' : 'Start Timer' }}
                            </CButton>

                            <CButton
                                v-if="canBuyCredits && wallet.credit_purchase_allowed"
                                preset="outlined-red"
                                class="flex-1 justify-center text-xs"
                                icon="mdi:credit-card-plus-outline"
                                @click="goBuyCredits(wallet)"
                            >
                                Buy Credits
                            </CButton>

                            <CButton
                                preset="outlined-black"
                                class="flex-1 justify-center text-xs"
                                icon="mdi:eye-outline"
                                @click="goToWallet(wallet)"
                            >
                                Details
                            </CButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Wallet Modal -->
    <Teleport to="body">
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
            @click.self="closeCreateModal"
        >
            <div class="w-full max-w-md rounded-xl bg-white shadow-xl">
                <!-- Modal Header -->
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">Add Wallet</h2>

                    <button
                        type="button"
                        class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600"
                        @click="closeCreateModal"
                    >
                        <Icon icon="mdi:close" class="text-xl" />
                    </button>
                </div>

                <!-- Modal Body -->
                <form class="space-y-4 px-6 py-5" @submit.prevent="handleCreateWallet">
                    <!-- Name -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">
                            Name
                            <span class="text-red-500">*</span>
                        </label>

                        <CInput
                            v-model="createForm.name"
                            placeholder="e.g. Development Hours"
                            :class="{ 'border-red-500': createFormErrors.name }"
                        />

                        <p v-if="createFormErrors.name" class="mt-1 text-xs text-red-600">
                            {{ createFormErrors.name }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>

                        <CTextarea v-model="createForm.description" placeholder="Optional description" rows="3" />
                    </div>

                    <!-- Currency Code -->
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Currency</label>

                        <CInput
                            v-model="createForm.currency_code"
                            placeholder="e.g. BRL, USD"
                            :class="{ 'border-red-500': createFormErrors.currency_code }"
                        />

                        <p v-if="createFormErrors.currency_code" class="mt-1 text-xs text-red-600">
                            {{ createFormErrors.currency_code }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-2">
                        <CButton type="button" preset="outlined-black" @click="closeCreateModal">Cancel</CButton>

                        <CButton type="submit" preset="primary" :disabled="walletLoading" icon="mdi:check">
                            {{ walletLoading ? 'Creating...' : 'Create Wallet' }}
                        </CButton>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
