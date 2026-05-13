<script setup lang="ts">
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import AppSidebar from '@/components/layout/AppSidebar.vue';
import AppHeader from '@/components/layout/AppHeader.vue';

const route = useRoute();

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

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto">
                <slot />
            </main>
        </div>
    </div>
</template>
