<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { useTimerStore } from '@/stores/timer';
import { useConfirm } from '@/composables/useConfirm';
import { useClients } from '@/composables/useClients';
import { useWallets } from '@/composables/useWallets';
import type { TypeaheadOption } from '@/types';

const props = defineProps({
    timerStore: {
        type: Object,
        defaul: () => null,
    },
});

const emit = defineEmits(['openModal']);

const timerStore = props.timerStore || useTimerStore();
const { confirm } = useConfirm();
const { clients, fetchClients } = useClients();
const { wallets, fetchWallets } = useWallets();

const expanded = ref(true);
const localTime = ref(0);
const intervalId = ref<number | null>(null);

const showStartTimerModal = ref(false);
const selectedClientId = ref<number | null>(null);
const selectedWalletId = ref<number | null>(null);

const hasTimer = computed(() => timerStore.hasActiveTimer);
const timer = computed(() => timerStore.activeTimer);
const isRunning = computed(() => timerStore.isRunning);
const isPaused = computed(() => timerStore.isPaused);

// Typeahead options
const clientOptions = computed<TypeaheadOption[]>(() => {
    return clients.value.map((client) => ({
        value: client.id,
        label: `${client.name}${client.notes ? ` - ${client.notes}` : ''}`,
    }));
});

const filteredWallets = computed(() => {
    if (!selectedClientId.value) {
        return wallets.value;
    }

    return wallets.value.filter((wallet) => wallet.client_id === selectedClientId.value);
});

const walletOptions = computed<TypeaheadOption[]>(() => {
    return filteredWallets.value.map((w) => ({
        value: w.id,
        label: w.name,
    }));
});

async function refreshClientOptions({ searchTerm }: { searchTerm: string }): Promise<TypeaheadOption[]> {
    await fetchClients(1, searchTerm || '');

    return clientOptions.value;
}

async function refreshWalletOptions({ searchTerm }: { searchTerm: string }): Promise<TypeaheadOption[]> {
    await fetchWallets(selectedClientId.value || undefined, 1);

    return walletOptions.value;
}

const formattedTime = computed(() => {
    if (!timer.value) {
        // timerStore.activeTimer
        return '00:00:00';
    }

    const totalSeconds = localTime.value;

    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

function toggleExpanded(): void {
    expanded.value = !expanded.value;

    if (typeof localStorage !== 'undefined') {
        localStorage.setItem('expanded', JSON.stringify(expanded.value));
    }
}

async function togglePlayPause(): Promise<void> {
    if (isRunning.value) {
        await timerStore.pauseTimer();
    } else if (isPaused.value) {
        await timerStore.resumeTimer();
    }
}

async function stopTimer(): Promise<void> {
    const confirmed = await confirm({
        title: 'Stop Timer',
        message: 'Are you sure you want to stop this timer?',
        confirmText: 'Yes, Stop',
        cancelText: 'Cancel',
        variant: 'warning',
    });

    if (confirmed) {
        await timerStore.stopTimer();
        stopLocalTimer();
    }
}

function updateLocalTime(): void {
    if (!timer.value) {
        return;
    }

    localTime.value = timer.value.total_seconds;
}

function startLocalTimer(): void {
    if (intervalId.value !== null) {
        return;
    }

    updateLocalTime();

    intervalId.value = window.setInterval(() => {
        if (isRunning.value) {
            localTime.value += 1;
            timerStore.setStoredFormattedTime(formattedTime.value);
        }
    }, 1000);
}

function stopLocalTimer(): void {
    if (intervalId.value !== null) {
        clearInterval(intervalId.value);
        intervalId.value = null;
    }
}

const showFloatingModal = computed(() => hasTimer.value && isRunning.value);

onMounted(async () => {
    if (hasTimer.value) {
        startLocalTimer();
    }

    if (typeof localStorage !== 'undefined') {
        expanded.value = ['true', '1'].includes((localStorage as any)?.getItem('expanded'));
    }

    await Promise.all([fetchClients(1, ''), fetchWallets()]);
});

onUnmounted(() => {
    stopLocalTimer();
});

watch(selectedClientId, () => {
    selectedWalletId.value = null;
    void refreshWalletOptions({ searchTerm: '' });
});

watch(hasTimer, (newValue, oldValue) => {
    if (newValue && !intervalId.value) {
        // Timer became active - start local timer
        startLocalTimer();
    } else if (!newValue && intervalId.value) {
        // Timer is no longer active - stop local timer
        stopLocalTimer();
    }
});

// Watch for timer object changes to update local time
watch(
    timer,
    (newTimer) => {
        if (newTimer) {
            localTime.value = newTimer.total_seconds;
        } else {
            localTime.value = 0;
        }
    },
    { deep: true }
);

function openModal() {
    emit('openModal', {
        timerStore,
    });
}

function openStartTimerModal(): void {
    selectedClientId.value = null;
    selectedWalletId.value = null;
    showStartTimerModal.value = true;
    void refreshClientOptions({ searchTerm: '' });
    void refreshWalletOptions({ searchTerm: '' });
}

function closeStartTimerModal(): void {
    showStartTimerModal.value = false;
}

async function handleStartTimer(): Promise<void> {
    if (!selectedWalletId.value) {
        return;
    }

    await timerStore.startTimer(selectedWalletId.value);
    closeStartTimerModal();
}
</script>

<template>
    <div
        v-if="showFloatingModal"
        :class="[
            'fixed bottom-2 right-2 z-30 bg-white border border-gray-200 rounded-lg shadow-lg transition-all duration-300',
            {
                'w-80': expanded,
                'w-16 h-16': !expanded,
            },
        ]"
    >
        <!-- Minimized State -->
        <div v-if="!expanded" class="flex items-center justify-center h-full cursor-pointer" @click="toggleExpanded">
            <div class="flex flex-col items-center">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
                <span v-show="hasTimer" class="text-xs text-gray-600 mt-1">
                    {{ formattedTime }}
                </span>
            </div>
        </div>

        <!-- Expanded State -->
        <div v-else class="p-4">
            <!-- Header -->
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div
                        :class="[
                            'w-2 h-2 rounded-full',
                            {
                                'bg-green-500 animate-pulse': isRunning,
                                'bg-yellow-500': isPaused,
                            },
                        ]"
                    ></div>
                    <span v-if="hasTimer" class="text-sm font-medium text-gray-700">
                        {{ isRunning ? 'Running' : 'Paused' }}
                    </span>
                </div>
                <button class="text-gray-400 hover:text-gray-600 transition-colors" @click="toggleExpanded">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>

            <template v-if="hasTimer">
                <!-- Timer Display -->
                <div class="text-center mb-4 truncate">
                    <div class="text-3xl font-bold text-gray-900 font-mono truncate">
                        {{ formattedTime }}
                    </div>
                    <div v-if="timer?.wallet" class="text-sm text-gray-500 mt-1 truncate">
                        {{ timer.wallet.client?.name }} - {{ timer.wallet.name }}
                    </div>
                    <div v-if="timer?.title" class="text-sm text-gray-700 mt-1 truncate">
                        {{ timer.title }}
                    </div>
                </div>
            </template>

            <!-- Controls -->
            <div v-if="!hasTimer" class="space-y-2">
                <CButton preset="dark-lg" flex class="w-full truncate" @click="openStartTimerModal">
                    Start Timer
                </CButton>
            </div>

            <div v-if="hasTimer" class="space-y-2">
                <button
                    class="w-full truncate px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-sm transition-colors"
                    @click="openModal"
                >
                    Open Full View
                </button>
                <div class="flex gap-2">
                    <button
                        :class="[
                            'flex-1 px-4 py-2 rounded-lg font-medium text-sm transition-colors truncate',
                            {
                                'bg-red-600 hover:bg-red-700 text-white': isRunning,
                                'bg-green-600 hover:bg-green-700 text-white': isPaused,
                            },
                        ]"
                        @click="togglePlayPause"
                    >
                        <span v-if="isRunning">Pause</span>
                        <span v-else>Resume</span>
                    </button>
                    <button
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium text-sm transition-colors"
                        @click="stopTimer"
                    >
                        Stop
                    </button>
                </div>
            </div>
        </div>

        <!-- Start Timer Modal -->
        <div
            v-if="showStartTimerModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-6"
            @click.self="closeStartTimerModal"
        >
            <div class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl" @click.stop>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Timer</p>
                        <h3 class="text-xl font-semibold text-gray-900">Select Wallet</h3>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-700" @click="closeStartTimerModal">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 6l8 8m0-8l-8 8"
                            />
                        </svg>
                    </button>
                </div>

                <p class="mt-2 text-sm text-gray-500">Select a wallet to start tracking time for this entry.</p>

                <div class="mt-6 space-y-4">
                    <CTypeahead
                        v-model="selectedClientId"
                        label="Client"
                        placeholder="Search by name or notes"
                        clearable
                        :initial-options="clientOptions"
                        :refresh-options="refreshClientOptions"
                        empty-text="No clients found"
                        loading-text="Loading clients..."
                    />

                    <CTypeahead
                        v-model="selectedWalletId"
                        label="Wallet"
                        placeholder="Choose wallet"
                        clearable
                        :initial-options="walletOptions"
                        :refresh-options="refreshWalletOptions"
                        empty-text="No wallets found"
                        loading-text="Loading wallets..."
                    />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <CButton preset="gray" @click="closeStartTimerModal">Cancel</CButton>
                    <CButton preset="primary" :disabled="!selectedWalletId" @click="handleStartTimer">
                        Start Timer
                    </CButton>
                </div>
            </div>
        </div>
    </div>
</template>
