<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import { RouterView, useRoute } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { useTimerStore } from '@/stores/timer';
import { useConfirm } from '@/composables/useConfirm';
import TimerFloatingBalloon from '@/components/TimerFloatingBalloon.vue';
import TimerActiveModal from '@/components/TimerActiveModal.vue';
import ConfirmModal from '@/components/ConfirmModal.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import CustomerLayout from '@/layouts/CustomerLayout.vue';
import { ENABLED_SPEED_INSIGHTS } from './configs/app';

const route = useRoute();
const { isAuthenticated, isCustomer } = useAuth();
const timerStore = useTimerStore();
const { state: confirmState, handleConfirm, handleCancel } = useConfirm();

const showTimerModal = ref(false);
const eventTimerStore = ref<any>(null);

const showLayout = computed(() => {
    return isAuthenticated.value && route.name !== 'login';
});

const showAdminLayout = computed(() => {
    return showLayout.value && !isCustomer.value;
});

const showCustomerLayout = computed(() => {
    return showLayout.value && isCustomer.value;
});

function openTimerModal(eventData: any): void {
    eventTimerStore.value = eventData?.timerStore ?? null;
    showTimerModal.value = true;
}

function closeTimerModal(): void {
    showTimerModal.value = false;
}

// Initialize timer store on fresh login (after mount)
watch(isAuthenticated, async (newValue, oldValue) => {
    if (newValue && !oldValue) {
        await timerStore.initialize();
    }

    if (!newValue && oldValue) {
        timerStore.cleanup();
    }
});

onMounted(async () => {
    if (isAuthenticated.value) {
        await timerStore.initialize();
    }
});

onUnmounted(() => {
    timerStore.cleanup();
});
</script>

<template>
    <div>
        <!-- Admin Layout (sidebar + header) -->
        <AdminLayout v-if="showAdminLayout">
            <RouterView />
        </AdminLayout>

        <!-- Customer Layout (top nav) -->
        <CustomerLayout v-else-if="showCustomerLayout">
            <RouterView />
        </CustomerLayout>

        <!-- Auth / public routes (no layout) -->
        <RouterView v-else />

        <!-- Global Overlays -->

        <TimerFloatingBalloon v-if="isAuthenticated" :timerStore="timerStore" @open-modal="openTimerModal" />

        <TimerActiveModal
            v-if="showTimerModal"
            :show="true"
            :timerStore="eventTimerStore ?? timerStore"
            @close="closeTimerModal"
        />

        <ConfirmModal
            v-model:is-open="confirmState.isOpen"
            :title="confirmState.title"
            :message="confirmState.message"
            :confirm-text="confirmState.confirmText"
            :cancel-text="confirmState.cancelText"
            :variant="confirmState.variant"
            @confirm="handleConfirm"
            @cancel="handleCancel"
        />

        <!-- BEGIN SpeedInsights -->
        <SpeedInsights v-if="ENABLED_SPEED_INSIGHTS" />
        <!-- END SpeedInsights -->
    </div>
</template>
