<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Icon } from '@iconify/vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { useAuthResources } from '@/composables/useAuthResources';
import { IS_PRODUCTION, SHOW_DEV_HELPERS } from '@/configs/app';
import { isValidEmail } from '@/utils/data-helpers';

const router = useRouter();
const { login, loading, error } = useAuth();
const { canRegister, canRecoverPassword, fetchAuthResources } = useAuthResources();

const email = ref('');
const password = ref('');
const showPassword = ref(false);
const remember = ref(false);

async function handleSubmit(): Promise<void> {
    if (!email.value || !password.value) {
        return;
    }

    const success = await login({
        email: email.value,
        password: password.value,
        remember: remember.value,
    });

    if (success) {
        const redirectTo = (router.currentRoute.value.query.redirect as string) || '/';
        router.push(redirectTo);
    }
}

function fillUserCredentials(emailValue: string, passwordValue: string | null = null): void {
    email.value = emailValue;
    password.value = passwordValue || 'password123';
}

const demoUsers = computed(() => [
    {
        name: 'Admin User',
        email: 'admin@mail.com',
        password: 'power@123',
        role: 'admin',
    },
    {
        name: 'Cliente 1 - Usuário',
        email: 'customer1@test.com',
        password: 'password',
        role: 'customer',
    },
    {
        name: 'Cliente 2 - Usuário',
        email: 'customer2@test.com',
        password: 'password',
        role: 'customer',
    },
]);

onMounted(() => {
    fetchAuthResources();
});
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gray-50 px-4">
        <div class="w-full max-w-md">
            <!-- Card -->
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <!-- Header -->
                <div class="px-8 pt-8 pb-6 text-center border-b border-gray-100">
                    <div
                        class="w-12 h-12 bg-red-600 rounded-xl flex items-center justify-center mx-auto mb-4 shadow-sm"
                    >
                        <Icon icon="heroicons:clock" class="w-6 h-6 text-white" />
                    </div>
                    <h1 class="text-xl font-bold text-gray-900">Hours Ledger</h1>
                    <p class="mt-1 text-sm text-gray-500">Sign in to your account</p>
                </div>

                <!-- Form -->
                <div class="px-8 py-6">
                    <div
                        v-if="error"
                        class="mb-5 flex items-start gap-2.5 rounded-xl bg-red-50 border border-red-200 p-3.5 text-sm text-red-700"
                    >
                        <Icon icon="heroicons:exclamation-circle" class="w-4 h-4 mt-0.5 flex-shrink-0" />
                        {{ error }}
                    </div>

                    <form class="space-y-4" @submit.prevent="handleSubmit">
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
                            <input
                                id="email"
                                v-model="email"
                                type="email"
                                autocomplete="email"
                                required
                                placeholder="you@example.com"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder:text-gray-400 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 transition-colors"
                            />
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Password
                            </label>
                            <div class="relative">
                                <input
                                    id="password"
                                    v-model="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="current-password"
                                    required
                                    placeholder="••••••••"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 pr-10 text-sm text-gray-900 placeholder:text-gray-400 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 transition-colors"
                                />
                                <button
                                    type="button"
                                    class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                                    :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    @click="showPassword = !showPassword"
                                >
                                    <Icon
                                        :icon="showPassword ? 'heroicons:eye-slash' : 'heroicons:eye'"
                                        class="w-4 h-4"
                                    />
                                </button>
                            </div>
                        </div>

                        <div :class="['flex items-center', canRecoverPassword ? 'justify-between' : 'justify-start']">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input
                                        id="remember"
                                        v-model="remember"
                                        aria-describedby="remember"
                                        type="checkbox"
                                        class="w-4 h-4 border border-gray-300 rounded bg-gray-50 focus:ring-0 dark:bg-gray-700 dark:border-gray-600 checked:bg-red-600 cursor-pointer"
                                    />
                                </div>
                                <div class="ml-3 text-sm">
                                    <label
                                        for="remember"
                                        class="text-sm font-medium text-gray-700 mb-1.5 select-none cursor-pointer"
                                    >
                                        Remember me
                                    </label>
                                </div>
                            </div>

                            <router-link
                                v-if="canRecoverPassword"
                                to="/password-recovery"
                                class="text-sm font-medium text-red-600 hover:text-red-700 hover:underline"
                            >
                                Forgot password?
                            </router-link>
                        </div>

                        <!-- Submit -->
                        <button
                            type="submit"
                            :disabled="loading || !isValidEmail(email)"
                            class="mt-2 w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        >
                            <span v-if="loading" class="inline-flex items-center gap-2 justify-center">
                                <Icon icon="heroicons:arrow-path" class="w-4 h-4 animate-spin" />
                                Signing in...
                            </span>
                            <span v-else>Sign in</span>
                        </button>
                    </form>
                </div>

                <!-- Dev helpers -->
                <div v-if="!IS_PRODUCTION && SHOW_DEV_HELPERS" class="px-8 pb-6 pt-0">
                    <div class="border-t border-gray-100 pt-5">
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">
                            Quick access (dev only)
                        </p>
                        <div class="space-y-2">
                            <button
                                v-for="user in demoUsers"
                                :key="user.email"
                                class="w-full flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-100 hover:border-gray-300 transition-colors"
                                @click="fillUserCredentials(user.email, user.password)"
                            >
                                <span class="font-medium">{{ user.name }}</span>
                                <span class="text-xs text-gray-400 capitalize">{{ user.role }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div v-if="canRegister" class="text-center mt-6 text-sm text-gray-600">
                Don't have an account?
                <router-link to="/register" class="font-medium text-red-600 hover:text-red-700">Register</router-link>
            </div>
        </div>
    </div>
</template>
