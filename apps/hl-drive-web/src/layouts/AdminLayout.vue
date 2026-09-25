<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { useRoute, RouterLink } from 'vue-router';
import { Icon } from '@iconify/vue';
import AppSidebar from '@/components/layout/AppSidebar.vue';
import AppHeader from '@/components/layout/AppHeader.vue';
import { useSubscription } from '@/composables/useSubscription';
import { useAuth } from '@/composables/useAuth';

const route = useRoute();
const auth = useAuth();
const { banner, fetchSummary } = useSubscription();

const sidebarCollapsed = ref(false);
const mobileOpen = ref(false);

function toggleSidebar(): void {
    sidebarCollapsed.value = !sidebarCollapsed.value;
}

function toggleMobile(): void {
    mobileOpen.value = !mobileOpen.value;
}

function closeMobile(): void {
    mobileOpen.value = false;
}

onMounted(async () => {
    if (auth.isAuthenticated.value && !auth.isCustomer.value) {
        await fetchSummary();
    }
});

// Close mobile sidebar on route change
watch(() => route.path, closeMobile);
</script>

<template>
    <div class="flex h-screen bg-gray-50 dark:bg-gray-900 overflow-hidden">
        <!-- Mobile Backdrop -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="mobileOpen"
                class="fixed inset-0 z-20 bg-black/50 lg:hidden"
                aria-hidden="true"
                @click="closeMobile"
            />
        </Transition>

        <!-- Sidebar -->
        <!-- Desktop: always visible, shrinks/expands in-place -->
        <!-- Mobile: slide-over from left -->
        <div
            :class="[
                'fixed inset-y-0 left-0 z-30 lg:relative lg:z-auto lg:translate-x-0 transition-transform duration-300 ease-in-out',
                { 'translate-x-0': mobileOpen, '-translate-x-full': !mobileOpen },
            ]"
            class="flex flex-shrink-0"
        >
            <AppSidebar :collapsed="sidebarCollapsed" @toggle-collapse="toggleSidebar" />
        </div>

        <!-- Main Content -->
        <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
            <!-- Top Header -->
            <AppHeader
                :sidebar-collapsed="sidebarCollapsed"
                @toggle-sidebar="toggleSidebar"
                @toggle-mobile="toggleMobile"
            />

            <!-- Subscription Status Warning Banner -->
            <div
                v-if="banner?.show_banner"
                :class="[
                    'px-4 py-2.5 flex items-center justify-between text-sm font-medium z-10 transition-colors shadow-sm',
                    banner.type === 'danger' ? 'bg-red-600 text-white' : 'bg-amber-400 text-amber-950',
                ]"
            >
                <div class="flex items-center gap-2 truncate">
                    <Icon
                        :icon="banner.type === 'danger' ? 'heroicons:exclamation-triangle' : 'heroicons:clock'"
                        class="w-5 h-5 flex-shrink-0"
                    />
                    <span class="truncate">{{ banner.message }}</span>
                </div>
                <RouterLink
                    to="/billing/payment"
                    :class="[
                        'px-3 py-1 rounded text-xs font-bold transition whitespace-nowrap ml-4',
                        banner.type === 'danger'
                            ? 'bg-white text-red-700 hover:bg-gray-100'
                            : 'bg-gray-900 text-white hover:bg-gray-800',
                    ]"
                >
                    Ajustar Pagamento
                </RouterLink>
            </div>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
