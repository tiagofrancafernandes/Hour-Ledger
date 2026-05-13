import { ref } from 'vue';
import api from '@/services/api';
import { useToast } from './useToast';

const currentPassword = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const isLoading = ref(false);
const error = ref<string | null>(null);
const success = ref(false);

export function useChangePassword() {
    const toast = useToast();

    async function changePassword(): Promise<void> {
        isLoading.value = true;
        error.value = null;
        success.value = false;

        try {
            await api.post('/auth/change-password', {
                current_password: currentPassword.value,
                password: password.value,
                password_confirmation: passwordConfirmation.value,
            });

            success.value = true;
            toast.success('Password has been changed successfully');
            reset();
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Password change failed';
            toast.error(error.value);
        } finally {
            isLoading.value = false;
        }
    }

    function reset(): void {
        currentPassword.value = '';
        password.value = '';
        passwordConfirmation.value = '';
        error.value = null;
        success.value = false;
    }

    return {
        currentPassword,
        password,
        passwordConfirmation,
        isLoading,
        error,
        success,
        changePassword,
        reset,
    };
}
