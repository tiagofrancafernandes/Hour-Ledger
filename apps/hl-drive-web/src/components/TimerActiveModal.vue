<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useTimerStore } from '@/stores/timer';
import { useConfirm } from '@/composables/useConfirm';
import { Icon } from '@iconify/vue';
import { useAuth } from '@/composables/useAuth';

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    close: [];
}>();

const {
    // user,
    isAuthenticated,
} = useAuth();

const cycleIsAsc = ref(false);

const timerStore = useTimerStore();
const { confirm } = useConfirm();

const intervalId = ref<number | null>(null);

const hasTimer = computed(() => timerStore.hasActiveTimer);

const localTime = ref(0);
const timer = computed(() => timerStore.activeTimer);
const isRunning = computed(() => timerStore.isRunning);
const isPaused = computed(() => timerStore.isPaused);

function formattedTimeState() {
    // return timerStore.formatTimeState(timer.value, localTime.value);

    if (!timer.value) {
        // timerStore.activeTimer
        return '00:00:00';
    }

    const totalSeconds = localTime.value;

    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = totalSeconds % 60;

    return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
}

const formattedTime = computed(formattedTimeState);

async function togglePlayPause(): Promise<void> {
    if (isRunning.value) {
        await timerStore.pauseTimer();
    } else if (isPaused.value) {
        await timerStore.resumeTimer();
    }
}

async function handleStop(): Promise<void> {
    const confirmed = await confirm({
        title: 'Stop Timer',
        message: 'Are you sure you want to stop this timer? You can confirm or edit cycles later.',
        confirmText: 'Yes, Stop',
        cancelText: 'Cancel',
        variant: 'warning',
    });

    if (confirmed) {
        await timerStore.stopTimer();
        emit('close');
    }
}

async function handleCancel(): Promise<void> {
    const confirmed = await confirm({
        title: 'Cancel Timer',
        message: 'Are you sure you want to cancel this timer? All data will be lost.',
        confirmText: 'Yes, Cancel',
        cancelText: 'No',
        variant: 'danger',
    });

    if (confirmed) {
        await timerStore.cancelTimer();
        emit('close');
    }
}

const orderedCycleItems = computed(() => {
    let _items = (timer.value || {}).cycles || [];

    const list = [..._items];

    return list.sort((a, b) => {
        if (a?.id < b?.id) {
            return cycleIsAsc.value ? -1 : 1;
        }

        if (a?.id > b?.id) {
            return cycleIsAsc.value ? 1 : -1;
        }

        return 0;
    });
});

function handleClose(): void {
    emit('close');
}

watch(
    timer,
    (newTimer) => {
        if (!newTimer) {
            emit('close');
        }
    },
    { deep: true }
);

function updateLocalTime(): void {
    if (!timer.value) {
        return;
    }

    localTime.value = timer.value.total_seconds;

    if (isRunning.value) {
        localTime.value += 1;
        timerStore.setStoredFormattedTime(formattedTimeState());
        return;
    }

    timerStore.setStoredFormattedTime(formattedTimeState());
}

function startLocalTimer(): void {
    if (intervalId.value !== null) {
        return;
    }

    updateLocalTime();

    intervalId.value = window.setInterval(() => {
        if (isRunning.value) {
            localTime.value += 1;
            timerStore.setStoredFormattedTime(formattedTimeState());
        }
    }, 1000);
}

function stopLocalTimer(): void {
    if (intervalId.value !== null) {
        clearInterval(intervalId.value);
        intervalId.value = null;
    }
}

onMounted(async () => {
    console.log('onMounted', 'formattedTime.value', formattedTime.value, 'formattedTimeState()', formattedTimeState());

    if (hasTimer.value) {
        startLocalTimer();
    }

    if (isAuthenticated) {
        await timerStore.initialize();
        // await timerStore.fetchActiveTimer();
        // await timerStore.fetchTimers();
    }

    setTimeout(() => {
        updateLocalTime();

        console.log(
            'onMounted settimout',
            'formattedTime.value',
            formattedTime.value,
            'formattedTimeState()',
            formattedTimeState()
        );
    }, 3000);
});

onUnmounted(() => {
    stopLocalTimer();
});
</script>

<template>
    <div
        v-if="show && timer"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
        @click.self="handleClose"
    >
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 max-h-screen overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <div class="flex items-center gap-3">
                    <div
                        :class="[
                            'w-3 h-3 rounded-full',
                            {
                                'bg-green-500 animate-pulse': isRunning,
                                'bg-yellow-500': isPaused,
                            },
                        ]"
                    ></div>
                    <h2 class="text-xl font-semibold text-gray-900">
                        {{ isRunning ? 'Timer Running' : 'Timer Paused' }}
                    </h2>
                </div>
                <button class="text-gray-400 hover:text-gray-600 transition-colors" @click="handleClose">
                    <Icon icon="mdi:close" class="w-6 h-6" />
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-6">
                <!-- Timer Display -->
                <div class="text-center">
                    <div class="text-5xl font-bold text-gray-900 font-mono mb-2">
                        {{ formattedTimeState() }}
                    </div>
                    <div class="text-sm text-gray-500">{{ timer.total_hours.toFixed(2) }} hours</div>
                </div>

                <!-- Timer Info -->
                <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                    <div>
                        <p class="text-xs text-gray-600 mb-1">Wallet</p>
                        <p class="font-medium text-gray-900">
                            {{ timer.wallet?.client?.name }} - {{ timer.wallet?.name }}
                        </p>
                    </div>
                    <div v-if="timer.title">
                        <p class="text-xs text-gray-600 mb-1">Title</p>
                        <p class="font-medium text-gray-900">{{ timer.title }}</p>
                    </div>
                    <div v-if="timer.description">
                        <p class="text-xs text-gray-600 mb-1">Description</p>
                        <p class="text-gray-900">{{ timer.description }}</p>
                    </div>
                    <div v-if="timer.tags && timer.tags.length > 0">
                        <p class="text-xs text-gray-600 mb-2">Tags</p>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in timer.tags"
                                :key="tag.id"
                                class="px-2 py-1 bg-gray-100 text-gray-700 text-xs rounded"
                            >
                                {{ tag.name }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Cycles -->
                <div v-if="timer.cycles && timer.cycles.length > 0">
                    <div class="text-sm font-medium text-gray-900 mb-2 flex justify-between">
                        <CButton preset="lightgray" @click.stop.prevent="cycleIsAsc = !cycleIsAsc">Asc/Desc</CButton>

                        <span>Cycles ({{ timer.cycles.length }})</span>
                    </div>
                    <div class="space-y-2 max-h-32 overflow-y-auto">
                        <div
                            av-for="(cycle, index) in timer.cycles"
                            v-for="(cycle, index) in orderedCycleItems"
                            :key="cycle.id"
                            class="flex items-center justify-between p-2 bg-gray-50 rounded text-xs"
                        >
                            <span class="text-gray-600">#{{ cycle?.id || index + 1 }}</span>
                            <span class="font-mono text-gray-900">
                                {{
                                    new Date(cycle.started_at).toLocaleTimeString('pt-BR', {
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    })
                                }}
                                -
                                {{
                                    cycle.ended_at
                                        ? new Date(cycle.ended_at).toLocaleTimeString('pt-BR', {
                                              hour: '2-digit',
                                              minute: '2-digit',
                                          })
                                        : 'Running'
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Controls -->
                <div class="grid grid-cols-2 gap-3">
                    <CButton :preset="isRunning ? 'yellow-md' : 'green-md'" class="w-full" @click="togglePlayPause">
                        <Icon :icon="isRunning ? 'mdi:pause' : 'mdi:play'" class="w-5 h-5 mr-2" />
                        {{ isRunning ? 'Pause' : 'Resume' }}
                    </CButton>
                    <CButton preset="orange-md" class="w-full" @click="handleStop">
                        <Icon icon="mdi:stop" class="w-5 h-5 mr-2" />
                        Stop
                    </CButton>
                </div>

                <!-- Cancel Button -->
                <CButton preset="outlined-red" class="w-full" @click="handleCancel">
                    <Icon icon="mdi:cancel" class="w-5 h-5 mr-2" />
                    Cancel Timer
                </CButton>
            </div>
        </div>
    </div>
</template>
