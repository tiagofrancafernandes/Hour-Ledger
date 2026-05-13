<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useTimerStore } from '@/stores/timer';
import { useAuthStore } from '@/stores/auth';
import { useConfirm } from '@/composables/useConfirm';
import { useClients } from '@/composables/useClients';
import { useWallets } from '@/composables/useWallets';
import { useUsers } from '@/composables/useUsers';
import TimerStartModal from '@/components/TimerStartModal.vue';
import TimerConfirmModal from '@/components/TimerConfirmModal.vue';
import TimerEntriesModal from '@/components/TimerEntriesModal.vue';
import ManualEntryModal from '@/components/ManualEntryModal.vue';
import { api } from '@/services/api';
import type { Timer, TypeaheadOption } from '@/types';

const route = useRoute();
const router = useRouter();
const timerStore = useTimerStore();
const authStore = useAuthStore();
const { confirm } = useConfirm();
const { clients, fetchClients } = useClients();
const { wallets, fetchWallets } = useWallets();
const { users, fetchUsers, searchUsers } = useUsers();

const callAction = ref<any>(null);
const showStartModal = ref(false);
const showConfirmModal = ref(false);
const showEntriesModal = ref(false);
const selectedTimer = ref<Timer | null>(null);
const filterStatus = ref<string>('all');
const listAll = ref(false);
const showManualEntryModal = ref(false);
const selectedClientId = ref<string | number | null>(null);
const selectedWalletId = ref<string | number | null>(null);
const selectedUserId = ref<string | number | null>(null);

const canCreateTimer = computed(() => authStore.can('timer.create'));
const canConfirmTimer = computed(() => authStore.can('timer.confirm'));
const canDeleteTimer = computed(() => authStore.can('timer.delete'));
const canViewAnyTimer = computed(() => authStore.can('timer.view_any'));

const clientOptions = computed(() => {
    return clients.value.map((client) => ({
        value: client.id,
        label: `${client.name}${client.notes ? ` (${client.notes})` : ''}`,
    }));
});

const filteredWallets = computed(() => {
    if (!selectedClientId.value) {
        return [];
    }

    return wallets.value.filter((wallet) => wallet.client_id === selectedClientId.value);
});

const walletOptions = computed(() => {
    return filteredWallets.value.map((wallet) => ({
        value: wallet.id,
        label: wallet.name,
    }));
});

const userOptions = computed(() => {
    return users.value.map((user) => ({
        value: user.id,
        label: user.name,
    }));
});

async function refreshClientOptions(query: string): Promise<void> {
    await fetchClients(1, query);
}

async function refreshWalletOptions(query: string): Promise<void> {
    if (!selectedClientId.value) {
        return;
    }

    await fetchWallets();
}

async function refreshUserOptions(query: string): Promise<void> {
    await searchUsers(query);
}

function isOwnTimer(timer: Timer): boolean {
    return timer.user_id === authStore.user?.id;
}

const filteredTimers = computed(() => {
    let result = timerStore.timers;

    // Filter by status
    if (filterStatus.value !== 'all') {
        result = result.filter((timer) => timer.status === filterStatus.value);
    }

    // Filter by client
    if (selectedClientId.value) {
        result = result.filter((timer) => timer.wallet?.client_id === selectedClientId.value);
    }

    // Filter by wallet
    if (selectedWalletId.value) {
        result = result.filter((timer) => timer.wallet_id === selectedWalletId.value);
    }

    // Filter by user
    if (selectedUserId.value) {
        result = result.filter((timer) => timer.user_id === selectedUserId.value);
    }

    return result;
});

const statusCounts = computed(() => {
    const counts = {
        all: timerStore.timers.length,
        running: 0,
        paused: 0,
        stopped: 0,
        confirmed: 0,
        cancelled: 0,
    };

    timerStore.timers.forEach((timer) => {
        if (timer.status in counts) {
            counts[timer.status as keyof typeof counts]++;
        }
    });

    return counts;
});

function getStatusColor(status: string): string {
    const colors = {
        running: 'bg-green-100 text-green-800',
        paused: 'bg-yellow-100 text-yellow-800',
        stopped: 'bg-blue-100 text-blue-800',
        confirmed: 'bg-gray-100 text-gray-800',
        cancelled: 'bg-red-100 text-red-800',
    };

    return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800';
}

function getStatusLabel(status: string): string {
    const labels = {
        running: 'Running',
        paused: 'Paused',
        stopped: 'Stopped',
        confirmed: 'Confirmed',
        cancelled: 'Cancelled',
    };

    return labels[status as keyof typeof labels] || status;
}

function handleConfirmTimer(timer: Timer): void {
    selectedTimer.value = timer;
    showConfirmModal.value = true;
}

function handleViewEntries(timer: Timer): void {
    selectedTimer.value = timer;
    showEntriesModal.value = true;
}

async function handlePauseTimer(timer: Timer): Promise<void> {
    await timerStore.pauseTimerById(timer.id);
    await loadTimers();
}

async function handleResumeTimer(timer: Timer): Promise<void> {
    await timerStore.resumeTimerById(timer.id);
    await loadTimers();
}

async function handleStopTimer(timer: Timer): Promise<void> {
    const confirmed = await confirm({
        title: 'Stop Timer',
        message: 'Are you sure you want to stop this timer? You can confirm or edit cycles later.',
        confirmText: 'Yes, Stop',
        cancelText: 'Cancel',
        variant: 'warning',
    });

    if (!confirmed) {
        return;
    }

    await timerStore.stopTimerById(timer.id);
    await loadTimers();
}

async function handleCancelTimer(timer: Timer): Promise<void> {
    const confirmed = await confirm({
        title: 'Cancel Timer',
        message: 'Are you sure you want to cancel this timer? All data will be lost.',
        confirmText: 'Yes, Cancel',
        cancelText: 'No',
        variant: 'danger',
    });

    if (!confirmed) {
        return;
    }

    await timerStore.cancelTimerById(timer.id);
    await loadTimers();
}

async function handleDeleteTimer(timer: Timer): Promise<void> {
    const confirmed = await confirm({
        title: 'Delete Timer',
        message: 'Are you sure you want to delete this timer?',
        confirmText: 'Yes, Delete',
        cancelText: 'Cancel',
        variant: 'danger',
    });

    if (!confirmed) {
        return;
    }

    await timerStore.deleteTimer(timer.id);
    await loadTimers();
}

function handleTimerConfirmed(): void {
    loadTimers();
}

async function loadTimers(): Promise<void> {
    await timerStore.fetchTimers(undefined, listAll.value);
}

watch(listAll, (newValue) => {
    if (!newValue) {
        selectedUserId.value = null;
    }

    loadTimers();
});

watch(selectedClientId, () => {
    selectedWalletId.value = null;
});

function openManualEntryModal(): void {
    showManualEntryModal.value = true;
}

function closeManualEntryModal(): void {
    showManualEntryModal.value = false;
}

function handleManualEntryCreated(): void {
    closeManualEntryModal();
    void loadTimers();
}

function formatDate(dateString: string): string {
    const date = new Date(dateString);

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function checkCallAction() {
    if (callAction.value === 'startTimer') {
        showStartModal.value = true;
    }
}

function startTimerAction() {
    showStartModal.value = true;
}

onMounted(async () => {
    await loadTimers();
    await fetchClients(1, '');
    await fetchWallets();

    if (canViewAnyTimer.value) {
        await fetchUsers();
    }

    callAction.value = (route.query.call as string) || null;

    if (typeof document !== 'undefined') {
        document.addEventListener('callAction::startTimer', startTimerAction);
    }

    checkCallAction();
});

onUnmounted(() => {
    if (typeof document !== 'undefined') {
        document.removeEventListener('callAction::startTimer', startTimerAction);
    }
});
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <UIPageHeader title="Timers" description="Track and manage your time entries.">
            <template v-slot:actions>
                <div class="flex items-center gap-4">
                    <CButton
                        v-if="canCreateTimer"
                        preset="gray"
                        icon="hugeicons:add-circle"
                        @click="openManualEntryModal"
                    >
                        Manual Entry
                    </CButton>

                    <CButton
                        v-if="canCreateTimer"
                        preset="primary"
                        icon="mdi:timer-plus"
                        @click="showStartModal = true"
                    >
                        Start Timer
                    </CButton>
                </div>
            </template>
        </UIPageHeader>

        <div class="w-full">
            <!-- Filter Tabs -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
                <div class="flex overflow-x-auto">
                    <button
                        :class="[
                            'px-6 py-3 text-sm font-medium transition-colors whitespace-nowrap',
                            {
                                'text-red-600 border-b-2 border-red-600': filterStatus === 'all',
                                'text-gray-600 hover:text-gray-900': filterStatus !== 'all',
                            },
                        ]"
                        @click="filterStatus = 'all'"
                    >
                        All ({{ statusCounts.all }})
                    </button>
                    <button
                        :class="[
                            'px-6 py-3 text-sm font-medium transition-colors whitespace-nowrap',
                            {
                                'text-red-600 border-b-2 border-red-600': filterStatus === 'running',
                                'text-gray-600 hover:text-gray-900': filterStatus !== 'running',
                            },
                        ]"
                        @click="filterStatus = 'running'"
                    >
                        Running ({{ statusCounts.running }})
                    </button>
                    <button
                        :class="[
                            'px-6 py-3 text-sm font-medium transition-colors whitespace-nowrap',
                            {
                                'text-red-600 border-b-2 border-red-600': filterStatus === 'paused',
                                'text-gray-600 hover:text-gray-900': filterStatus !== 'paused',
                            },
                        ]"
                        @click="filterStatus = 'paused'"
                    >
                        Paused ({{ statusCounts.paused }})
                    </button>
                    <button
                        :class="[
                            'px-6 py-3 text-sm font-medium transition-colors whitespace-nowrap',
                            {
                                'text-red-600 border-b-2 border-red-600': filterStatus === 'stopped',
                                'text-gray-600 hover:text-gray-900': filterStatus !== 'stopped',
                            },
                        ]"
                        @click="filterStatus = 'stopped'"
                    >
                        Stopped ({{ statusCounts.stopped }})
                    </button>
                    <button
                        :class="[
                            'px-6 py-3 text-sm font-medium transition-colors whitespace-nowrap',
                            {
                                'text-red-600 border-b-2 border-red-600': filterStatus === 'confirmed',
                                'text-gray-600 hover:text-gray-900': filterStatus !== 'confirmed',
                            },
                        ]"
                        @click="filterStatus = 'confirmed'"
                    >
                        Confirmed ({{ statusCounts.confirmed }})
                    </button>
                    <button
                        :class="[
                            'px-6 py-3 text-sm font-medium transition-colors whitespace-nowrap',
                            {
                                'text-red-600 border-b-2 border-red-600': filterStatus === 'cancelled',
                                'text-gray-600 hover:text-gray-900': filterStatus !== 'cancelled',
                            },
                        ]"
                        @click="filterStatus = 'cancelled'"
                    >
                        Cancelled ({{ statusCounts.cancelled }})
                    </button>
                </div>

                <!-- Client and Wallet Filters -->
                <div class="border-t border-gray-200 p-4">
                    <div
                        :class="[
                            'grid gap-4',
                            canViewAnyTimer ? 'grid-cols-1 md:grid-cols-3' : 'grid-cols-1 md:grid-cols-2',
                        ]"
                    >
                        <CTypeahead
                            v-model="selectedClientId"
                            label="Filter by Client"
                            placeholder="Search client..."
                            clearable
                            :initial-options="clientOptions"
                            :refresh-options="refreshClientOptions"
                            empty-text="No clients found"
                            loading-text="Loading clients..."
                        />

                        <CTypeahead
                            v-model="selectedWalletId"
                            label="Filter by Wallet"
                            placeholder="Select wallet"
                            clearable
                            :initial-options="walletOptions"
                            :refresh-options="refreshWalletOptions"
                            empty-text="No wallets available"
                            loading-text="Loading wallets..."
                            :disabled="!selectedClientId"
                        />

                        <CTypeahead
                            v-if="canViewAnyTimer && listAll"
                            v-model="selectedUserId"
                            label="Filter by User"
                            placeholder="Search user..."
                            clearable
                            :initial-options="userOptions"
                            :refresh-options="refreshUserOptions"
                            empty-text="No users found"
                            loading-text="Loading users..."
                        />
                    </div>
                </div>

                <div class="flex justify-between w-full">
                    <div class="flex md:justify-start w-full p-3">
                        <CButton icon="mdi:reload" preset="outlined-black" @click="loadTimers" class="py-1">
                            Refresh list
                        </CButton>
                    </div>

                    <div class="flex justify-end w-full truncate">
                        <div :class="['px-6 py-3 text-sm font-medium transition-colors whitespace-nowrap']">
                            <!-- List All toggle - admin only -->
                            <label v-if="canViewAnyTimer" class="flex cursor-pointer items-center gap-2 select-none">
                                <span class="text-sm text-gray-600">List all users</span>

                                <button
                                    type="button"
                                    role="switch"
                                    :aria-checked="listAll"
                                    :class="[
                                        'relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200',
                                        {
                                            'bg-red-600': listAll,
                                            'bg-gray-200': !listAll,
                                        },
                                    ]"
                                    @click="listAll = !listAll"
                                >
                                    <span
                                        :class="[
                                            'pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-200',
                                            {
                                                'translate-x-5': listAll,
                                                'translate-x-0': !listAll,
                                            },
                                        ]"
                                    />
                                </button>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loading State -->
            <div
                v-if="timerStore.loading"
                class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center"
            >
                <p class="text-gray-600">Loading timers...</p>
            </div>

            <!-- Empty State -->
            <div
                v-else-if="filteredTimers.length === 0"
                class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center"
            >
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <p class="text-gray-600 mb-2">No timers found</p>
                <p class="text-gray-500 text-sm">
                    {{ filterStatus === 'all' ? 'Start tracking your time' : `No ${filterStatus} timers` }}
                </p>
            </div>

            <!-- Timers List -->
            <div v-else class="space-y-4">
                <div
                    v-for="timer in filteredTimers"
                    :key="timer.id"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
                >
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <!-- Status Badge -->
                            <span
                                :class="[
                                    'inline-block px-3 py-1 text-xs font-semibold rounded-full mb-3',
                                    getStatusColor(timer.status),
                                ]"
                            >
                                {{ getStatusLabel(timer.status) }}
                            </span>

                            <!-- Timer Info -->
                            <div class="mb-3">
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                    {{ timer.title || 'Untitled Timer' }}
                                </h3>
                                <p class="text-sm text-gray-600">
                                    {{ timer.wallet?.client?.name }} - {{ timer.wallet?.name }}
                                </p>
                                <p v-if="timer.user" class="mt-1 text-xs text-gray-500">
                                    <span class="font-medium">User:</span>
                                    {{ timer.user.name }}
                                    <span v-if="isOwnTimer(timer)" class="ml-1 text-green-600">(you)</span>
                                </p>
                                <p v-if="timer.description" class="text-sm text-gray-700 mt-2">
                                    {{ timer.description }}
                                </p>
                            </div>

                            <!-- Tags -->
                            <div v-if="timer.tags && timer.tags.length > 0" class="flex flex-wrap gap-2 mb-3">
                                <span
                                    v-for="tag in timer.tags"
                                    :key="tag.id"
                                    class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded"
                                >
                                    {{ tag.name }}
                                </span>
                            </div>

                            <!-- Time Info -->
                            <div class="flex items-center gap-6 text-sm">
                                <div>
                                    <span class="text-gray-600">Duration:</span>
                                    <span class="font-mono font-semibold text-gray-900 ml-2">
                                        {{ timer.formatted_duration }}
                                    </span>
                                    <span class="text-gray-500 ml-1">({{ timer.total_hours }}h)</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Created:</span>
                                    <span class="text-gray-900 ml-2">{{ formatDate(timer.created_at) }}</span>
                                </div>
                                <div v-if="timer.confirmed_at">
                                    <span class="text-gray-600">Confirmed:</span>
                                    <span class="text-gray-900 ml-2">{{ formatDate(timer.confirmed_at) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex flex-col gap-2 ml-4">
                            <!-- View Entries button (always visible) -->
                            <CButton
                                preset="outlined-black"
                                size="sm"
                                @click="handleViewEntries(timer)"
                                class="whitespace-nowrap"
                            >
                                View Entries
                            </CButton>

                            <!-- Own timer actions only -->
                            <template v-if="isOwnTimer(timer)">
                                <!-- Running timer actions -->
                                <div v-if="timer.status === 'running'" class="flex gap-2">
                                    <CButton preset="yellow-sm" @click="handlePauseTimer(timer)">Pause</CButton>
                                    <CButton preset="orange-sm" @click="handleStopTimer(timer)">Stop</CButton>
                                    <CButton preset="red-sm" @click="handleCancelTimer(timer)">Cancel</CButton>
                                </div>

                                <!-- Paused timer actions -->
                                <div v-if="timer.status === 'paused'" class="flex gap-2">
                                    <CButton preset="green-sm" @click="handleResumeTimer(timer)">Resume</CButton>
                                    <CButton preset="orange-sm" @click="handleStopTimer(timer)">Stop</CButton>
                                    <CButton preset="red-sm" @click="handleCancelTimer(timer)">Cancel</CButton>
                                </div>

                                <!-- Stopped timer actions -->
                                <div v-if="timer.status === 'stopped' && canConfirmTimer" class="flex gap-2">
                                    <CButton preset="green-sm" @click="handleConfirmTimer(timer)">Confirm</CButton>
                                    <CButton preset="red-sm" @click="handleCancelTimer(timer)">Cancel</CButton>
                                </div>

                                <!-- Confirmed/Cancelled timer actions -->
                                <div
                                    v-if="
                                        (timer.status === 'confirmed' || timer.status === 'cancelled') && canDeleteTimer
                                    "
                                >
                                    <CButton preset="red-sm" @click="handleDeleteTimer(timer)">Delete</CButton>
                                </div>
                            </template>

                            <!-- View-only indicator for other users' timers -->
                            <span v-else class="text-xs text-gray-400 italic">View only</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ManualEntryModal
            :show="showManualEntryModal"
            @close="closeManualEntryModal"
            @entry-created="handleManualEntryCreated"
        />

        <!-- Modals -->
        <TimerStartModal :show="showStartModal" @close="showStartModal = false" @started="loadTimers" />

        <TimerConfirmModal
            :show="showConfirmModal"
            :timer="selectedTimer"
            @close="showConfirmModal = false"
            @confirmed="handleTimerConfirmed"
        />

        <TimerEntriesModal :show="showEntriesModal" :timer="selectedTimer" @close="showEntriesModal = false" />
    </div>
</template>
