import { ref } from 'vue';
import api from '@/services/api';
import { useToast } from './useToast';

type RegistrationStep = 'email' | 'verify' | 'complete' | 'success';

const currentStep = ref<RegistrationStep>('email');
const email = ref('');
const name = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const verificationCode = ref('');
const verificationToken = ref('');
const isLoading = ref(false);
const error = ref<string | null>(null);

export function useUserRegistration() {
    const toast = useToast();

    async function requestRegistration(): Promise<void> {
        isLoading.value = true;
        error.value = null;

        try {
            await api.post('/auth/register', {
                name: name.value,
                email: email.value,
                password: password.value,
                password_confirmation: passwordConfirmation.value,
            });

            currentStep.value = 'verify';
            toast.info('Verification email has been sent');
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Registration failed';
            toast.error(error.value);
        } finally {
            isLoading.value = false;
        }
    }

    async function verifyEmail(): Promise<void> {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await api.post<any>('/auth/register/verify', {
                email: email.value,
                code: verificationCode.value,
            });

            verificationToken.value = response.token || response.data?.token;
            currentStep.value = 'complete';
            toast.success('Email verified successfully');
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Verification failed';
            toast.error(error.value);
        } finally {
            isLoading.value = false;
        }
    }

    async function completeRegistration(): Promise<any> {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await api.post<any>('/auth/register/complete', {
                name: name.value,
                email: email.value,
                password: password.value,
                password_confirmation: passwordConfirmation.value,
                verification_token: verificationToken.value,
            });

            currentStep.value = 'success';

            return {
                token: response.token || response.data?.token,
                user: response.user || response.data?.user,
            };
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Registration completion failed';
            toast.error(error.value);
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    function reset(): void {
        currentStep.value = 'email';
        email.value = '';
        name.value = '';
        password.value = '';
        passwordConfirmation.value = '';
        verificationCode.value = '';
        verificationToken.value = '';
        error.value = null;
    }

    return {
        currentStep,
        email,
        name,
        password,
        passwordConfirmation,
        verificationCode,
        verificationToken,
        isLoading,
        error,
        requestRegistration,
        verifyEmail,
        completeRegistration,
        reset,
    };
}
