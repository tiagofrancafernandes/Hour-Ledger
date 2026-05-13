<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useTimerStore } from '@/stores/timer';
import type { Timer, TimerCycleForm } from '@/types';

const props = defineProps<{
    show: boolean;
    timer: Timer | null;
}>();

const emit = defineEmits<{
    close: [];
    confirmed: [];
}>();

const timerStore = useTimerStore();

const loading = ref(false);
const cycles = ref<TimerCycleForm[]>([]);

const totalHours = computed(() => {
    if (!props.timer) {
        return '0.00';
    }

    return props.timer.total_hours.toFixed(2);
});

const formattedDuration = computed(() => {
    if (!props.timer) {
        return '00:00:00';
    }

    return props.timer.formatted_duration;
});

const canSubmit = computed(() => {
    return !loading.value && props.timer !== null;
});

function initializeCycles(): void {
    if (!props.timer) {
        return;
    }

    cycles.value = props.timer.cycles.map((cycle) => ({
        id: cycle.id,
        started_at: cycle.started_at,
        ended_at: cycle.ended_at,
    }));
}

async function handleConfirm(): Promise<void> {
    if (!canSubmit.value || !props.timer) {
        return;
    }

    loading.value = true;

    try {
        await timerStore.confirmTimerById(props.timer.id, cycles.value);

        emit('confirmed');
        emit('close');
    } catch {
        // error already saved in timerStore.error, displayed in template
    } finally {
        loading.value = false;
    }
}

function handleClose(): void {
    if (!loading.value) {
        emit('close');
    }
}

function formatDateTime(dateTimeString: string): string {
    const date = new Date(dateTimeString);

    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function calculateCycleDuration(cycle: TimerCycleForm): string {
    if (!cycle.ended_at) {
        return 'Running...';
    }

    const start = new Date(cycle.started_at).getTime();
    const end = new Date(cycle.ended_at).getTime();
    const durationMs = end - start;
    const durationSeconds = Math.floor(durationMs / 1000);

    const hours = Math.floor(durationSeconds / 3600);
    const minutes = Math.floor((durationSeconds % 3600) / 60);
    const seconds = durationSeconds % 60;

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

// Initialize cycles when modal is shown with a timer
watch(
    () => [props.show, props.timer],
    ([newShow, newTimer]) => {
        if (newShow && newTimer) {
            initializeCycles();
        }
    },
    { immediate: true }
);
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="handleClose">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-screen overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Confirm Timer</h2>
                <button
                    class="text-gray-400 hover:text-gray-600 transition-colors"
                    :disabled="loading"
                    @click="handleClose"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-6">
                <!-- Timer Summary -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Wallet</p>
                            <p class="font-medium text-gray-900">
                                {{ timer?.wallet?.client?.name }} - {{ timer?.wallet?.name }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Time</p>
                            <p class="text-2xl font-bold text-red-600 font-mono">
                                {{ formattedDuration }}
                            </p>
                            <p class="text-sm text-gray-500">{{ totalHours }} hours</p>
                        </div>
                    </div>
                    <div v-if="timer?.title" class="mt-3">
                        <p class="text-sm text-gray-600">Title</p>
                        <p class="font-medium text-gray-900">{{ timer.title }}</p>
                    </div>
                    <div v-if="timer?.description" class="mt-3">
                        <p class="text-sm text-gray-600">Description</p>
                        <p class="text-gray-900">{{ timer.description }}</p>
                    </div>
                </div>

                <!-- Cycles List -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 mb-3">Time Cycles</h3>
                    <div class="space-y-2">
                        <div
                            v-for="(cycle, index) in cycles"
                            :key="cycle.id || index"
                            class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
                        >
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900">Cycle {{ index + 1 }}</p>
                                <p class="text-xs text-gray-600">
                                    {{ formatDateTime(cycle.started_at) }}
                                    →
                                    {{ cycle.ended_at ? formatDateTime(cycle.ended_at) : 'Running' }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-gray-900 font-mono">
                                    {{ calculateCycleDuration(cycle) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Warning -->
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex">
                        <svg
                            class="w-5 h-5 text-yellow-600 mt-0.5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                            />
                        </svg>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-yellow-800">Confirm Timer</h4>
                            <p class="text-sm text-yellow-700 mt-1">
                                This will create a ledger entry for {{ totalHours }} hours and mark this timer as
                                confirmed. This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div v-if="timerStore.error" class="p-3 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-600">{{ timerStore.error }}</p>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <CButton type="button" preset="lightgray-md" :disabled="loading" @click="handleClose">
                        Cancel
                    </CButton>
                    <CButton
                        type="submit"
                        :preset="canSubmit ? 'green-md' : 'lightgray-md'"
                        :class="['transition-colors']"
                        :disabled="!canSubmit"
                        @click="handleConfirm"
                    >
                        {{ loading ? 'Confirming...' : 'Confirm Timer' }}
                    </CButton>
                </div>
            </div>
        </div>
    </div>
</template>
