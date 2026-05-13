<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { Icon } from '@iconify/vue';
import { useRouter } from 'vue-router';
import { useAuth } from '@/composables/useAuth';
import { useAuthStore } from '@/stores/auth';
import { useTimerStore } from '@/stores/timer';
import ManualEntryModal from '@/components/ManualEntryModal.vue';
import TimerStartModal from '@/components/TimerStartModal.vue';

const props = defineProps<{
    sidebarCollapsed: boolean;
}>();

const emit = defineEmits<{
    'toggle-sidebar': [];
    'toggle-mobile': [];
}>();

const router = useRouter();
const { user, logout, loading } = useAuth();
const authStore = useAuthStore();
const timerStore = useTimerStore();

const showUserMenu = ref(false);
const showTimerMenu = ref(false);
const userMenuRef = ref<HTMLElement | null>(null);
const timerMenuRef = ref<HTMLElement | null>(null);

const showManualEntryModal = ref(false);
const showStartTimerModal = ref(false);

const canCreateTimer = computed(() => authStore.can('timer.create'));
const hasActiveTimer = computed(() => timerStore.hasActiveTimer);
const isTimerRunning = computed(() => timerStore.isRunning);

const userInitial = () => user.value?.name?.charAt(0)?.toUpperCase() ?? '?';

function toggleUserMenu(): void {
    showUserMenu.value = !showUserMenu.value;
    showTimerMenu.value = false;
}

function closeUserMenu(): void {
    showUserMenu.value = false;
}

function toggleTimerMenu(): void {
    showTimerMenu.value = !showTimerMenu.value;
    showUserMenu.value = false;
}

function closeTimerMenu(): void {
    showTimerMenu.value = false;
}

function openManualEntryModal(): void {
    closeTimerMenu();
    showManualEntryModal.value = true;
}

function openStartTimerModal(): void {
    closeTimerMenu();
    showStartTimerModal.value = true;
}

function closeModals(): void {
    showManualEntryModal.value = false;
    showStartTimerModal.value = false;
}

function handleClickOutside(event: MouseEvent): void {
    if (userMenuRef.value && !userMenuRef.value.contains(event.target as Node)) {
        closeUserMenu();
    }

    if (timerMenuRef.value && !timerMenuRef.value.contains(event.target as Node)) {
        closeTimerMenu();
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
    <header
        class="bg-white dark:bg-gray-800/30 dark:text-white border-b border-gray-200 dark:border-gray-700 h-12 flex items-center px-4 gap-3 flex-shrink-0 z-10 shadow-sm"
    >
        <!-- Desktop: Sidebar toggle -->
        <button
            class="hidden lg:flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors"
            :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            @click="emit('toggle-sidebar')"
        >
            <Icon icon="heroicons:bars-3" class="w-5 h-5" />
        </button>

        <!-- Mobile: Hamburger -->
        <button
            class="flex lg:hidden items-center justify-center w-9 h-9 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors"
            aria-label="Open navigation menu"
            @click="emit('toggle-mobile')"
        >
            <Icon icon="heroicons:bars-3" class="w-5 h-5" />
        </button>

        <!-- Spacer -->
        <div class="flex-1" />

        <!-- Timer actions: Desktop (md+) -->
        <div v-if="canCreateTimer" class="hidden md:flex items-center gap-2">
            <button
                class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm text-gray-700 hover:bg-gray-100 transition-colors font-medium"
                @click="openManualEntryModal"
            >
                <Icon icon="hugeicons:add-circle" class="w-4 h-4" />
                Manual Entry
            </button>

            <button
                v-if="!hasActiveTimer"
                class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm text-white bg-red-600 hover:bg-red-700 transition-colors font-medium"
                @click="openStartTimerModal"
            >
                <Icon icon="mdi:timer-plus" class="w-4 h-4" />
                Start Timer
            </button>

            <button
                v-else
                class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm text-red-600 bg-red-50 hover:bg-red-100 transition-colors font-medium"
                @click="$router.push('/timers')"
            >
                <Icon icon="mdi:timer" :class="['w-4 h-4', { 'animate-pulse': isTimerRunning }]" />
                Running
            </button>
        </div>

        <!-- Timer menu: Mobile (< md) -->
        <div v-if="canCreateTimer" ref="timerMenuRef" class="md:hidden relative">
            <button
                class="flex items-center justify-center w-9 h-9 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors"
                @click="toggleTimerMenu"
                aria-label="Timer options"
            >
                <Icon icon="mdi:timer" class="w-5 h-5" />
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
                    v-if="showTimerMenu"
                    class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-200 py-1 z-50"
                >
                    <button
                        class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                        @click="openManualEntryModal"
                    >
                        <Icon icon="hugeicons:add-circle" class="w-4 h-4 text-gray-400 flex-shrink-0" />
                        Manual Entry
                    </button>

                    <button
                        v-if="!hasActiveTimer"
                        class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 transition-colors"
                        @click="openStartTimerModal"
                    >
                        <Icon icon="mdi:timer-plus" class="w-4 h-4 flex-shrink-0" />
                        Start Timer
                    </button>

                    <button
                        v-else
                        class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50 transition-colors"
                        @click="
                            $router.push('/timers');
                            closeTimerMenu();
                        "
                    >
                        <Icon
                            icon="mdi:timer"
                            :class="['w-4 h-4 flex-shrink-0', { 'animate-pulse': isTimerRunning }]"
                        />
                        View Running
                    </button>
                </div>
            </Transition>
        </div>

        <!-- Right area -->
        <div ref="userMenuRef" class="relative">
            <button
                class="flex items-center gap-2.5 rounded-xl px-2.5 py-1.5 text-sm text-gray-700 hover:bg-gray-100 transition-colors"
                @click="toggleUserMenu"
            >
                <!-- Avatar -->
                <div
                    class="w-7 h-7 rounded-full bg-red-100 flex items-center justify-center text-red-600 font-bold text-xs flex-shrink-0"
                >
                    {{ userInitial() }}
                </div>

                <span class="hidden sm:block font-medium text-gray-800 max-w-32 truncate">
                    {{ user?.name }}
                </span>

                <Icon
                    icon="heroicons:chevron-down"
                    :class="[
                        'w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0',
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
                    <!-- User info -->
                    <div class="px-4 py-2.5 border-b border-gray-100">
                        <p class="text-xs font-semibold text-gray-900 truncate">{{ user?.name }}</p>
                        <p class="text-xs text-gray-500 truncate mt-0.5">{{ user?.email }}</p>
                    </div>

                    <!-- Profile link -->
                    <button
                        class="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-gray-700 hover:bg-gray-50 transition-colors"
                        @click="goToProfile"
                    >
                        <Icon icon="heroicons:user-circle" class="w-4 h-4 text-gray-400 flex-shrink-0" />
                        Profile
                    </button>

                    <!-- Logout -->
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
    </header>

    <!-- Modals -->
    <ManualEntryModal :show="showManualEntryModal" @close="showManualEntryModal = false" @entry-created="closeModals" />

    <TimerStartModal :show="showStartTimerModal" @close="showStartTimerModal = false" @started="closeModals" />
</template>
