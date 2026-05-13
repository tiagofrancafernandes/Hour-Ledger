<script setup lang="ts">
import { computed, type PropType } from 'vue';
import type { Timer } from '@/types';

interface TimerCycle {
    id?: string | number;
    started_at: string;
    ended_at?: string | null;
}

const props = defineProps<{
    show: boolean;
    timer: Timer | null;
}>();

const emit = defineEmits<{
    close: [];
}>();

const cycles = computed(() => {
    return props.timer?.cycles || [];
});

const totalHours = computed(() => {
    return props.timer?.total_hours || 0;
});

const formattedDuration = computed(() => {
    return props.timer?.formatted_duration || '00:00:00';
});

function formatDateTime(dateString: string): string {
    const date = new Date(dateString);

    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
}

function calculateCycleDuration(cycle: TimerCycle): string {
    const start = new Date(cycle.started_at).getTime();
    const end = cycle.ended_at ? new Date(cycle.ended_at).getTime() : new Date().getTime();
    const seconds = Math.floor((end - start) / 1000);

    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}
</script>

<template>
    <Teleport v-if="show" to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4 py-6">
            <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-xl">
                <!-- Header -->
                <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Timer</p>
                        <h2 class="text-xl font-semibold text-gray-900">View Entries</h2>
                    </div>
                    <button
                        type="button"
                        class="text-gray-400 hover:text-gray-700 transition-colors"
                        @click="emit('close')"
                    >
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 6l8 8m0-8l-8 8"
                            />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-6">
                    <!-- Timer Summary -->
                    <div v-if="timer" class="bg-gray-50 rounded-lg p-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Wallet</p>
                                <p class="font-medium text-gray-900">
                                    {{ timer.wallet?.client?.name }} - {{ timer.wallet?.name }}
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
                        <div v-if="timer.title" class="mt-3">
                            <p class="text-sm text-gray-600">Title</p>
                            <p class="font-medium text-gray-900">{{ timer.title }}</p>
                        </div>
                        <div v-if="timer.description" class="mt-3">
                            <p class="text-sm text-gray-600">Description</p>
                            <p class="text-gray-900">{{ timer.description }}</p>
                        </div>
                    </div>

                    <!-- Cycles List -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Time Cycles</h3>
                        <div v-if="cycles.length === 0" class="text-center py-6">
                            <p class="text-gray-500">No cycles recorded yet</p>
                        </div>
                        <div v-else class="space-y-2">
                            <div
                                v-for="(cycle, index) in cycles"
                                :key="cycle.id || index"
                                class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                            >
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">Cycle {{ index + 1 }}</p>
                                    <p class="text-xs text-gray-600 mt-1">
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
                </div>

                <!-- Footer -->
                <div class="sticky bottom-0 bg-gray-50 border-t border-gray-200 px-6 py-4 flex justify-end">
                    <CButton preset="gray" @click="emit('close')">Close</CButton>
                </div>
            </div>
        </div>
    </Teleport>
</template>
