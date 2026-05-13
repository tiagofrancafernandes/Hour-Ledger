<script setup lang="ts">
import { computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useUserRegistration } from '@/composables/useUserRegistration';
import { useAuth } from '@/composables/useAuth';
import { useAuthResources } from '@/composables/useAuthResources';
import { isValidEmail } from '@/utils/data-helpers';

const router = useRouter();
const authStore = useAuth();
const { canRegister, fetchAuthResources } = useAuthResources();

const {
    currentStep,
    email,
    name,
    password,
    passwordConfirmation,
    verificationCode,
    isLoading,
    error,
    requestRegistration,
    verifyEmail,
    completeRegistration,
    reset,
} = useUserRegistration();

const isStep1 = computed(() => currentStep.value === 'email');
const isStep2 = computed(() => currentStep.value === 'verify');
const isStep3 = computed(() => currentStep.value === 'complete');
const isStep4 = computed(() => currentStep.value === 'success');

const hasErrors = computed(() => error.value !== null);

async function handleStep1Submit(): Promise<void> {
    if (!email.value || !name.value || !password.value || !passwordConfirmation.value) {
        return;
    }

    await requestRegistration();
}

async function handleStep2Submit(): Promise<void> {
    if (!verificationCode.value) {
        return;
    }

    await verifyEmail();
}

async function handleStep3Submit(): Promise<void> {
    try {
        const result = await completeRegistration();

        if (result) {
            const { token, user } = result;
            localStorage.setItem('auth_token', token);
            authStore.user = user;

            setTimeout(() => {
                router.push('/dashboard');
            }, 1500);
        }
    } catch (_) {
        // Error is already handled in composable
    }
}

function handleGoBack(): void {
    if (isStep2.value || isStep3.value) {
        reset();
    }
}

onMounted(() => {
    fetchAuthResources();
});
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Create Account</h1>
                <p class="text-gray-600">Join us today and get started</p>
            </div>

            <!-- Card -->
            <div v-if="!canRegister" class="bg-white rounded-xl border border-gray-200 shadow-sm p-8">
                <div class="text-center py-8">
                    <Icon icon="heroicons:exclamation-circle" class="w-12 h-12 text-red-600 mx-auto mb-4" />
                    <h2 class="text-lg font-semibold text-gray-900 mb-2">Registration Disabled</h2>
                    <p class="text-gray-600 mb-6">
                        User registration is currently not available. Please contact administrator for assistance.
                    </p>
                    <router-link to="/login">
                        <CButton preset="outlined-black" class="w-full">Back to Login</CButton>
                    </router-link>
                </div>
            </div>

            <div v-else class="bg-white rounded-xl border border-gray-200 shadow-sm p-8">
                <!-- Step Indicator -->
                <div class="flex items-center gap-2 mb-8">
                    <div
                        :class="[
                            'flex-1 h-2 rounded-full transition-colors',
                            isStep1 || isStep2 || isStep3 || isStep4 ? 'bg-red-600' : 'bg-gray-200',
                        ]"
                    />
                    <div
                        :class="[
                            'flex-1 h-2 rounded-full transition-colors',
                            isStep2 || isStep3 || isStep4 ? 'bg-red-600' : 'bg-gray-200',
                        ]"
                    />
                    <div
                        :class="[
                            'flex-1 h-2 rounded-full transition-colors',
                            isStep3 || isStep4 ? 'bg-red-600' : 'bg-gray-200',
                        ]"
                    />
                </div>

                <!-- Error Message -->
                <div v-if="hasErrors" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm font-medium text-red-800">
                        {{ error }}
                    </p>
                </div>

                <!-- Step 1: Email & Basic Info -->
                <div v-if="isStep1" class="space-y-6">
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-1">Step 1 of 3</p>
                        <h2 class="text-lg font-semibold text-gray-900">Account Information</h2>
                    </div>

                    <CInput v-model="name" type="text" label="Full Name" placeholder="John Doe" :disabled="isLoading" />

                    <CInput
                        v-model="email"
                        type="email"
                        label="Email Address"
                        placeholder="you@example.com"
                        :disabled="isLoading"
                    />

                    <CPasswodInput
                        v-model="password"
                        type="password"
                        label="Password"
                        placeholder="••••••••"
                        :disabled="isLoading"
                    />

                    <CPasswodInput
                        v-model="passwordConfirmation"
                        type="password"
                        label="Confirm Password"
                        placeholder="••••••••"
                        :disabled="isLoading"
                    />

                    <div class="flex gap-3 pt-4">
                        <router-link to="/login" class="flex-1">
                            <CButton preset="outlined-black" class="w-full" :disabled="isLoading">
                                Sign In Instead
                            </CButton>
                        </router-link>
                        <CButton
                            class="flex-1"
                            :disabled="
                                isLoading ||
                                !isValidEmail(email) ||
                                !name ||
                                !password ||
                                !passwordConfirmation ||
                                String(password || '').length < 6 ||
                                String(passwordConfirmation || '').length < 6 ||
                                password !== passwordConfirmation
                            "
                            @click="handleStep1Submit"
                        >
                            <span v-if="!isLoading">Continue</span>
                            <span v-else>Sending...</span>
                        </CButton>
                    </div>
                </div>

                <!-- Step 2: Email Verification -->
                <div v-if="isStep2" class="space-y-6">
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-1">Step 2 of 3</p>
                        <h2 class="text-lg font-semibold text-gray-900">Verify Email</h2>
                    </div>

                    <p class="text-sm text-gray-600">
                        We've sent a 6-digit verification code to
                        <strong>{{ email }}</strong>
                    </p>

                    <CInput
                        v-model="verificationCode"
                        type="text"
                        label="Verification Code"
                        placeholder="000000"
                        maxlength="6"
                        :disabled="isLoading"
                        class="font-mono text-center text-lg tracking-widest"
                    />

                    <div class="flex gap-3 pt-4">
                        <CButton preset="outlined-black" class="flex-1" :disabled="isLoading" @click="handleGoBack">
                            Back
                        </CButton>
                        <CButton class="flex-1" :disabled="isLoading || !verificationCode" @click="handleStep2Submit">
                            <span v-if="!isLoading">Verify</span>
                            <span v-else>Verifying...</span>
                        </CButton>
                    </div>
                </div>

                <!-- Step 3: Confirmation -->
                <div v-if="isStep3" class="space-y-6">
                    <div>
                        <p class="text-sm font-medium text-gray-700 mb-1">Step 3 of 3</p>
                        <h2 class="text-lg font-semibold text-gray-900">Account Created!</h2>
                    </div>

                    <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm font-medium text-green-800">
                            Email verified successfully. Your account is ready!
                        </p>
                    </div>

                    <div class="space-y-2 text-sm text-gray-600">
                        <p>
                            <strong>Name:</strong>
                            {{ name }}
                        </p>
                        <p>
                            <strong>Email:</strong>
                            {{ email }}
                        </p>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <CButton preset="outlined-black" class="flex-1" :disabled="isLoading" @click="handleGoBack">
                            Back
                        </CButton>
                        <CButton class="flex-1" :disabled="isLoading" @click="handleStep3Submit">
                            <span v-if="!isLoading">Complete Setup</span>
                            <span v-else>Setting up...</span>
                        </CButton>
                    </div>
                </div>

                <!-- Step 4: Success -->
                <div v-if="isStep4" class="space-y-6 text-center">
                    <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome aboard!</h2>
                        <p class="text-gray-600">
                            Your account has been successfully created. Redirecting to dashboard...
                        </p>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-6 text-sm text-gray-600">
                Already have an account?
                <router-link to="/login" class="font-medium text-red-600 hover:text-red-700 hover:underline">
                    Sign in
                </router-link>
            </div>
        </div>
    </div>
</template>
