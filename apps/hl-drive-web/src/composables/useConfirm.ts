import { ref, type Ref } from 'vue';

export interface ConfirmOptions {
    title?: string;
    message?: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'danger' | 'warning' | 'info' | 'success';
}

export interface ConfirmState extends ConfirmOptions {
    isOpen: boolean;
}

interface ConfirmCallbacks {
    resolve: (value: boolean) => void;
    reject: () => void;
}

const state: Ref<ConfirmState> = ref({
    isOpen: false,
    title: '',
    message: '',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    variant: 'info',
});

let callbacks: ConfirmCallbacks | null = null;

export function useConfirm() {
    function confirm(options: ConfirmOptions | string): Promise<boolean> {
        return new Promise((resolve, reject) => {
            if (typeof options === 'string') {
                state.value = {
                    isOpen: true,
                    title: 'Confirm action',
                    message: options,
                    confirmText: 'Confirm',
                    cancelText: 'Cancel',
                    variant: 'info',
                };
            } else {
                state.value = {
                    isOpen: true,
                    title: options.title || 'Confirm action',
                    message: options.message || 'Are you sure you want to continue?',
                    confirmText: options.confirmText || 'Confirm',
                    cancelText: options.cancelText || 'Cancel',
                    variant: options.variant || 'info',
                };
            }

            callbacks = { resolve, reject };
        });
    }

    function handleConfirm(): void {
        if (callbacks) {
            callbacks.resolve(true);
            callbacks = null;
        }

        state.value.isOpen = false;
    }

    function handleCancel(): void {
        if (callbacks) {
            callbacks.resolve(false);
            callbacks = null;
        }

        state.value.isOpen = false;
    }

    return {
        state,
        confirm,
        handleConfirm,
        handleCancel,
    };
}
