import { getCurrentInstance } from 'vue';

export function useToast() {
    const instance = getCurrentInstance();

    if (!instance) {
        throw new Error('useToast must be called inside setup()');
    }

    const { $toastSuccess, $toastError, $toastInfo, $toastDark, $toastWarning } =
        instance.appContext.config.globalProperties;

    return {
        dark: $toastDark,
        info: $toastInfo,
        success: $toastSuccess,
        warning: $toastWarning,
        error: $toastError,
    };
}
