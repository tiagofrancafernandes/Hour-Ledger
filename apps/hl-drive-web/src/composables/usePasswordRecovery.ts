import { ref } from 'vue';
import api from '@/services/api';
import { useToast } from './useToast';

type RecoveryStep = 'request' | 'verify' | 'reset' | 'success';

const currentStep = ref<RecoveryStep>('request');
const email = ref('');
const verificationCode = ref('');
const verificationToken = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const isLoading = ref(false);
const error = ref<string | null>(null);

export function usePasswordRecovery() {
    const toast = useToast();

    async function requestRecovery(): Promise<void> {
        isLoading.value = true;
        error.value = null;

        try {
            await api.post('/auth/password-recovery/request', {
                email: email.value,
            });

            currentStep.value = 'verify';
            toast.info('If the email exists, a recovery link will be sent');
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Recovery request failed';
            toast.error(error.value);
        } finally {
            isLoading.value = false;
        }
    }

    async function verifyToken(): Promise<void> {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await api.post<any>('/auth/password-recovery/verify', {
                email: email.value,
                code: verificationCode.value,
            });

            verificationToken.value = response.token || response.data?.token;
            currentStep.value = 'reset';
            toast.success('Token verified successfully');
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Token verification failed';
            toast.error(error.value);
        } finally {
            isLoading.value = false;
        }
    }

    async function resetPassword(): Promise<void> {
        isLoading.value = true;
        error.value = null;

        try {
            await api.post('/auth/password-recovery/reset', {
                email: email.value,
                code: verificationCode.value,
                password: password.value,
                password_confirmation: passwordConfirmation.value,
            });

            currentStep.value = 'success';
            toast.success('Password has been reset successfully');
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Password reset failed';
            toast.error(error.value);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    function reset(): void {
        currentStep.value = 'request';
        email.value = '';
        verificationCode.value = '';
        verificationToken.value = '';
        password.value = '';
        passwordConfirmation.value = '';
        error.value = null;
    }

    return {
        currentStep,
        email,
        verificationCode,
        verificationToken,
        password,
        passwordConfirmation,
        isLoading,
        error,
        requestRecovery,
        verifyToken,
        resetPassword,
        reset,
    };
}
