<template>
    <Teleport to="body">
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            @click.self="handleCancel"
        >
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md transform transition-all" @click.stop>
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div
                            :class="[
                                'flex-shrink-0 w-11 h-11 rounded-xl flex items-center justify-center',
                                {
                                    'bg-red-100': variant === 'danger',
                                    'bg-amber-100': variant === 'warning',
                                    'bg-sky-100': variant === 'info',
                                    'bg-green-100': variant === 'success',
                                },
                            ]"
                        >
                            <Icon
                                :icon="icon"
                                :class="[
                                    'w-5 h-5',
                                    {
                                        'text-red-600': variant === 'danger',
                                        'text-amber-600': variant === 'warning',
                                        'text-sky-600': variant === 'info',
                                        'text-green-600': variant === 'success',
                                    },
                                ]"
                            />
                        </div>

                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">
                                {{ title }}
                            </h3>

                            <p class="text-sm text-gray-600">
                                {{ message }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-4 flex gap-3 justify-end rounded-b-2xl border-t border-gray-100">
                    <CButton preset="outlined-black" @click="handleCancel">
                        {{ cancelText }}
                    </CButton>

                    <CButton :preset="confirmPreset" @click="handleConfirm">
                        {{ confirmText }}
                    </CButton>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Icon } from '@iconify/vue';

export interface ConfirmModalProps {
    isOpen: boolean;
    title?: string;
    message?: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'danger' | 'warning' | 'info' | 'success';
}

const props = withDefaults(defineProps<ConfirmModalProps>(), {
    title: 'Confirm action',
    message: 'Are you sure you want to continue?',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    variant: 'info',
});

const emit = defineEmits<{
    confirm: [];
    cancel: [];
    'update:isOpen': [value: boolean];
}>();

const icon = computed(() => {
    const icons = {
        danger: 'mdi:alert-circle-outline',
        warning: 'mdi:alert-outline',
        info: 'mdi:information-outline',
        success: 'mdi:check-circle-outline',
    };

    return icons[props.variant];
});

const confirmPreset = computed(() => {
    const presets = {
        danger: 'danger',
        warning: 'warning',
        info: 'primary',
        success: 'success',
    };

    return presets[props.variant];
});

function handleConfirm(): void {
    emit('confirm');
    emit('update:isOpen', false);
}

function handleCancel(): void {
    emit('cancel');
    emit('update:isOpen', false);
}
</script>
