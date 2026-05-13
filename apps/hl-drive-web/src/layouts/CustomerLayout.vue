<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Icon } from '@iconify/vue';
import { RouterLink, useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { usePermissions } from '@/composables/usePermissions';

const router = useRouter();
const { user, logout, loading } = useAuth();
const { canViewPaymentHistory, canViewReports } = usePermissions();

const showUserMenu = ref(false);
const userMenuRef = ref<HTMLElement | null>(null);

const userInitial = () => user.value?.name?.charAt(0)?.toUpperCase() ?? '?';

function toggleUserMenu(): void {
    showUserMenu.value = !showUserMenu.value;
}

function closeUserMenu(): void {
    showUserMenu.value = false;
}

function handleClickOutside(event: MouseEvent): void {
    if (userMenuRef.value && !userMenuRef.value.contains(event.target as Node)) {
        closeUserMenu();
    }
}

async function handleLogout(): Promise<void> {
    closeUserMenu();
    await logout();
    router.push({ name: 'login' });
}

function goToProfile(): void {
    closeUserMenu();
    router.push({ name: 'profile' });
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Top Navigation -->
        <nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex h-12 items-center justify-between">
                    <!-- Logo -->
                    <div class="flex items-center gap-6">
                        <RouterLink to="/dashboard" class="flex items-center gap-2.5">
                            <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center shadow-sm">
                                <Icon icon="heroicons:clock" class="w-4 h-4 text-white" />
                            </div>
                            <span class="text-sm font-bold text-gray-900 tracking-tight">Hours Ledger</span>
                        </RouterLink>

                        <!-- Nav Links -->
                        <div class="hidden sm:flex items-center gap-1">
                            <RouterLink
                                to="/dashboard"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors"
                                active-class="text-red-600 bg-red-50 hover:bg-red-50 hover:text-red-600"
                            >
                                <Icon icon="heroicons:home" class="w-4 h-4" />
                                Dashboard
                            </RouterLink>

                            <RouterLink
                                v-if="canViewPaymentHistory"
                                to="/payments/history"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors"
                                active-class="text-red-600 bg-red-50 hover:bg-red-50 hover:text-red-600"
                            >
                                <Icon icon="heroicons:receipt-percent" class="w-4 h-4" />
                                Payment History
                            </RouterLink>

                            <RouterLink
                                v-if="canViewReports"
                                to="/reports"
                                class="flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:text-gray-900 hover:bg-gray-100 transition-colors"
                                active-class="text-red-600 bg-red-50 hover:bg-red-50 hover:text-red-600"
                            >
                                <Icon icon="heroicons:chart-bar" class="w-4 h-4" />
                                Reports
                            </RouterLink>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div ref="userMenuRef" class="relative">
                        <button
                            class="flex items-center gap-2.5 rounded-xl px-2.5 py-1.5 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                            @click="toggleUserMenu"
                        >
                            <div
                                class="w-7 h-7 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold text-xs"
                            >
                                {{ userInitial() }}
                            </div>
                            <span class="hidden sm:block font-medium text-gray-800 max-w-32 truncate">
                                {{ user?.name }}
                            </span>
                            <Icon
                                icon="heroicons:chevron-down"
                                :class="[
                                    'w-4 h-4 text-gray-400 transition-transform duration-200',
                                    { 'rotate-180': showUserMenu },
                                ]"
                            />
                        </button>

                        <!-- Dropdown -->
                        <Transition
                            enter-active-class="transition ease-out duration-100"
                            enter-from-class="transform opacity-0 scale-95"
                            enter-to-class="transform opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="transform opacity-100 scale-100"
                            leave-to-class="transform opacity-0 scale-95"
                        >
                            <div
                                v-if="showUserMenu"
                                class="absolute right-0 top-full mt-2 w-52 bg-white rounded-xl shadow-lg border border-gray-200 py-1 z-50"
                            >
                                <div class="px-4 py-2.5 border-b border-gray-100">
                                    <p class="text-xs font-semibold text-gray-900 truncate">{{ user?.name }}</p>
                                    <p class="text-xs text-gray-500 truncate mt-0.5">{{ user?.email }}</p>
                                </div>

                                <button
                                    class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                                    @click="goToProfile"
                                >
                                    <Icon icon="heroicons:user-circle" class="w-4 h-4 text-gray-400 flex-shrink-0" />
                                    Profile
                                </button>

                                <button
                                    :disabled="loading"
                                    class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 transition-colors disabled:opacity-50"
                                    @click="handleLogout"
                                >
                                    <Icon icon="heroicons:arrow-right-on-rectangle" class="w-4 h-4 flex-shrink-0" />
                                    Logout
                                </button>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Content -->
        <main>
            <slot />
        </main>
    </div>
</template>
