import type { App } from 'vue';
import { toast, type ToastOptions } from 'vue3-toastify';
import 'vue3-toastify/dist/index.css';

type ToastPluginOptions = ToastOptions;

export default {
    install(app: App, options: ToastPluginOptions = {}) {
        app.config.globalProperties.$toastSuccess = (message: string, override: ToastOptions = {}) => {
            toast.success(message, {
                ...options,
                ...override,
            });
        };

        app.config.globalProperties.$toastError = (message: string, override: ToastOptions = {}) => {
            toast.error(message, {
                ...options,
                ...override,
            });
        };

        app.config.globalProperties.$toastInfo = (message: string, override: ToastOptions = {}) => {
            toast.info(message, {
                ...options,
                ...override,
            });
        };

        app.config.globalProperties.$toastDark = (message: string, override: ToastOptions = {}) => {
            toast.dark(message, {
                ...options,
                ...override,
            });
        };

        app.config.globalProperties.$toastWarning = (message: string, override: ToastOptions = {}) => {
            toast.warning(message, {
                ...options,
                ...override,
            });
        };
    },
};
