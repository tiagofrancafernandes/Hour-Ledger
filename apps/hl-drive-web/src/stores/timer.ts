import { defineStore } from 'pinia';
import { computed, ref, type ComputedRef, type Ref } from 'vue';
import { api } from '@/services/api';
import type { Timer, TimerForm, TimerCycleForm, PaginatedResponse } from '@/types';
import { debounce } from 'radash';

export const useTimerStore = defineStore('timer', () => {
    // State
    const activeTimer = ref<Timer | null>(null);
    const timers = ref<Timer[]>([]);
    const currentTimer = ref<Timer | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const pollInterval = ref<number | null>(null);
    const storedFormattedTime = ref('00:00:00');

    // Getters
    const hasActiveTimer = computed(() => activeTimer.value !== null);
    const isRunning = computed(() => activeTimer.value?.status === 'running');
    const isPaused = computed(() => activeTimer.value?.status === 'paused');

    // Actions
    async function fetchActiveTimer(): Promise<void> {
        try {
            const response = await api.get<Timer | null>('/timers/active');

            if (response && Object.keys(response).length > 0) {
                activeTimer.value = response;
            } else {
                activeTimer.value = null;
            }
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to fetch active timer';
            activeTimer.value = null;
        }
    }

    async function fetchTimers(status?: string, listAll?: boolean): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const params: Record<string, unknown> = {};

            if (status) {
                params.status = status;
            }

            if (listAll) {
                params.list_all = true;
            }

            const response = await api.get<PaginatedResponse<Timer>>('/timers', params);

            timers.value = response.data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to fetch timers';
            timers.value = [];
        } finally {
            loading.value = false;
        }
    }

    async function fetchTimer(id: number): Promise<Timer | null> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<Timer>(`/timers/${id}`);

            currentTimer.value = response;

            return response;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to fetch timer';

            return null;
        } finally {
            loading.value = false;
        }
    }

    async function startTimer(form: TimerForm): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<Timer>('/timers', form);

            activeTimer.value = response;

            startPolling();

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to start timer';

            return false;
        } finally {
            loading.value = false;
        }
    }

    async function pauseTimer(): Promise<boolean> {
        if (!activeTimer.value) {
            return false;
        }

        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<Timer>(`/timers/${activeTimer.value.id}/pause`, {});

            activeTimer.value = response;

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to pause timer';

            return false;
        } finally {
            loading.value = false;
        }
    }

    async function resumeTimer(): Promise<boolean> {
        if (!activeTimer.value) {
            return false;
        }

        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<Timer>(`/timers/${activeTimer.value.id}/resume`, {});

            activeTimer.value = response;

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to resume timer';

            return false;
        } finally {
            loading.value = false;
        }
    }

    async function stopTimer(): Promise<boolean> {
        if (!activeTimer.value) {
            return false;
        }

        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<Timer>(`/timers/${activeTimer.value.id}/stop`, {});

            activeTimer.value = response;

            stopPolling();

            await fetchActiveTimer();

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to stop timer';

            return false;
        } finally {
            loading.value = false;
        }
    }

    async function cancelTimer(): Promise<boolean> {
        if (!activeTimer.value) {
            return false;
        }

        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<Timer>(`/timers/${activeTimer.value.id}/cancel`, {});

            activeTimer.value = null;

            stopPolling();

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to cancel timer';

            return false;
        } finally {
            loading.value = false;
        }
    }

    async function confirmTimer(cycles?: TimerCycleForm[]): Promise<boolean> {
        if (!activeTimer.value) {
            return false;
        }

        loading.value = true;
        error.value = null;

        try {
            const payload = cycles ? { cycles } : {};
            const response = await api.post<Timer>(`/timers/${activeTimer.value.id}/confirm`, payload);

            activeTimer.value = null;

            stopPolling();

            return true;
        } catch (err: any) {
            const errorMessage = err?.response?.data?.message || err?.message || 'Failed to confirm timer';

            error.value = errorMessage;

            console.error('Timer confirm error:', {
                error: err,
                response: err?.response?.data,
                timer: activeTimer.value,
                cycles,
            });

            throw new Error(errorMessage, { cause: err });
        } finally {
            loading.value = false;
        }
    }

    async function updateTimer(id: number, data: Partial<TimerForm>): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put<Timer>(`/timers/${id}`, data);

            if (activeTimer.value?.id === id) {
                activeTimer.value = response;
            }

            currentTimer.value = response;

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to update timer';

            return false;
        } finally {
            loading.value = false;
        }
    }

    async function updateTimerCycles(id: number, cycles: TimerCycleForm[]): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put<Timer>(`/timers/${id}/cycles`, { cycles });

            if (activeTimer.value?.id === id) {
                activeTimer.value = response;
            }

            currentTimer.value = response;

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to update timer cycles';

            return false;
        } finally {
            loading.value = false;
        }
    }

    async function confirmTimerById(id: number, cycles?: TimerCycleForm[]): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const payload = cycles ? { cycles } : {};

            await api.post<Timer>(`/timers/${id}/confirm`, payload);

            if (activeTimer.value?.id === id) {
                activeTimer.value = null;
                stopPolling();
            }

            await fetchTimers();

            return true;
        } catch (err: any) {
            const errorMessage = err?.response?.data?.message || err?.message || 'Failed to confirm timer';

            error.value = errorMessage;

            throw new Error(errorMessage, { cause: err });
        } finally {
            loading.value = false;
        }
    }

    async function deleteTimer(id: number): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            await api.delete(`/timers/${id}`);

            timers.value = timers.value.filter((t) => t.id !== id);

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to delete timer';

            return false;
        } finally {
            loading.value = false;
        }
    }

    async function pauseTimerById(id: number): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<Timer>(`/timers/${id}/pause`, {});

            if (activeTimer.value?.id === id) {
                activeTimer.value = response;
            }

            await fetchTimers();

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to pause timer';

            return false;
        } finally {
            loading.value = false;
        }
    }

    async function resumeTimerById(id: number): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<Timer>(`/timers/${id}/resume`, {});

            if (activeTimer.value?.id === id) {
                activeTimer.value = response;
            }

            await fetchTimers();

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to resume timer';

            return false;
        } finally {
            loading.value = false;
        }
    }

    async function stopTimerById(id: number): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<Timer>(`/timers/${id}/stop`, {});

            if (activeTimer.value?.id === id) {
                activeTimer.value = response;
                stopPolling();

                await fetchActiveTimer();
            }

            await fetchTimers();

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to stop timer';

            return false;
        } finally {
            loading.value = false;
        }
    }

    async function cancelTimerById(id: number): Promise<boolean> {
        loading.value = true;
        error.value = null;

        try {
            await api.post<Timer>(`/timers/${id}/cancel`, {});

            if (activeTimer.value?.id === id) {
                activeTimer.value = null;
                stopPolling();
            }

            await fetchTimers();

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to cancel timer';

            return false;
        } finally {
            loading.value = false;
        }
    }

    function startPolling(): void {
        if (pollInterval.value !== null) {
            return;
        }

        pollInterval.value = window.setInterval(() => {
            fetchActiveTimer();
        }, 5000);
    }

    function stopPolling(): void {
        if (pollInterval.value !== null) {
            clearInterval(pollInterval.value);
            pollInterval.value = null;
        }
    }

    function clearError(): void {
        error.value = null;
    }

    async function initialize(): Promise<void> {
        await fetchActiveTimer();
        fetchTimers();

        if (hasActiveTimer.value && (isRunning.value || isPaused.value)) {
            startPolling();
        }
    }

    function cleanup(): void {
        stopPolling();
        activeTimer.value = null;
        timers.value = [];
        currentTimer.value = null;
        error.value = null;
    }

    function getStoredFormattedTime() {
        return storedFormattedTime.value;
    }

    function setStoredFormattedTime(value: string = '00:00:00') {
        debounce({ delay: 600 }, () => {
            storedFormattedTime.value = value;
        });
    }

    function ressetStoredFormattedTime() {
        storedFormattedTime.value = '00:00:00';
    }

    function formatTimeState(timer: any = null, localTime: any) {
        if (!timer) {
            return '00:00:00';
        }

        const totalSeconds = localTime;

        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    }

    const computedStoredFormattedTime = computed(getStoredFormattedTime);

    return {
        // State
        activeTimer,
        timers,
        currentTimer,
        loading,
        error,
        storedFormattedTime,
        computedStoredFormattedTime,

        // Getters
        hasActiveTimer,
        isRunning,
        isPaused,

        // Actions
        fetchActiveTimer,
        fetchTimers,
        fetchTimer,
        startTimer,
        pauseTimer,
        resumeTimer,
        stopTimer,
        cancelTimer,
        confirmTimer,
        updateTimer,
        updateTimerCycles,
        deleteTimer,
        pauseTimerById,
        resumeTimerById,
        stopTimerById,
        cancelTimerById,
        confirmTimerById,
        initialize,
        cleanup,
        clearError,
        startPolling,
        stopPolling,
        setStoredFormattedTime,
        getStoredFormattedTime,
        ressetStoredFormattedTime,
        formatTimeState,
    };
});

// Export type for use in components
export type TimerStore = ReturnType<typeof useTimerStore>;
