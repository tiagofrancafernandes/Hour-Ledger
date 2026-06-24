<template>
    <div class="relative">
        <!-- Trigger Button -->
        <button
            @click="toggleDropdown"
            type="button"
            class="flex items-center gap-2 px-3 py-2 rounded-lg border border-neutral-200 bg-white text-neutral-900 hover:bg-neutral-50 transition-colors"
            :disabled="loading"
        >
            <!-- Avatar -->
            <div
                v-if="activeTenant"
                class="flex items-center justify-center w-8 h-8 rounded-full bg-blue-600 text-white font-semibold text-sm"
            >
                {{ getInitials(activeTenant.name) }}
            </div>

            <!-- Placeholder Avatar -->
            <div v-else class="flex items-center justify-center w-8 h-8 rounded-full bg-neutral-300">
                <Icon icon="fa7-solid:building" class="text-neutral-600" />
            </div>

            <!-- Tenant Name or Placeholder -->
            <span class="font-medium text-sm max-w-xs truncate">
                {{ activeTenant?.name || 'No tenant selected' }}
            </span>

            <!-- Loading Spinner -->
            <Icon
                v-if="loading"
                icon="fa7-solid:spinner"
                class="w-4 h-4 text-neutral-500 animate-spin ml-auto"
            />

            <!-- Dropdown Icon -->
            <Icon
                v-else
                icon="fa7-solid:chevron-down"
                class="w-4 h-4 text-neutral-500 ml-auto"
            />
        </button>

        <!-- Dropdown Menu -->
        <div
            v-if="isOpen"
            class="absolute top-full mt-2 right-0 w-64 bg-white border border-neutral-200 rounded-lg shadow-lg z-50"
        >
            <!-- Loading State -->
            <div v-if="loading" class="flex items-center justify-center gap-2 p-4">
                <Icon icon="fa7-solid:spinner" class="w-4 h-4 animate-spin" />
                <span class="text-sm text-neutral-600">Loading tenants...</span>
            </div>

            <!-- Error State -->
            <div
                v-else-if="error && tenants.length === 0"
                class="flex items-center gap-2 p-4 text-red-600 bg-red-50"
            >
                <Icon icon="fa7-solid:circle-exclamation" class="w-4 h-4 flex-shrink-0" />
                <span class="text-sm">{{ error }}</span>
            </div>

            <!-- Empty State -->
            <div v-else-if="tenants.length === 0" class="p-4 text-center text-neutral-500">
                <p class="text-sm">No tenants available</p>
            </div>

            <!-- Tenant List -->
            <div v-else class="max-h-80 overflow-y-auto">
                <button
                    v-for="tenant in tenants"
                    :key="tenant.id"
                    @click="selectTenantHandler(tenant.id)"
                    type="button"
                    :disabled="tenant.status !== 'active'"
                    class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-blue-50 transition-colors border-b border-neutral-100 last:border-b-0"
                    :class="getTenantButtonClasses(tenant)"
                >
                    <!-- Avatar -->
                    <div
                        class="flex items-center justify-center w-8 h-8 rounded-full font-semibold text-sm flex-shrink-0"
                        :class="getAvatarClasses(tenant)"
                    >
                        {{ getInitials(tenant.name) }}
                    </div>

                    <!-- Tenant Info -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-neutral-900 truncate">
                            {{ tenant.name }}
                        </p>

                        <!-- Status Badge -->
                        <div class="flex items-center gap-1 mt-1">
                            <Icon
                                icon="fa7-solid:circle-small"
                                :class="getStatusIconClass(tenant.status)"
                            />
                            <span class="text-xs capitalize" :class="getStatusTextClass(tenant.status)">
                                {{ tenant.status }}
                            </span>
                        </div>
                    </div>

                    <!-- Active Indicator -->
                    <div v-if="activeTenant?.id === tenant.id" class="flex-shrink-0">
                        <Icon icon="fa7-solid:check" class="w-4 h-4 text-blue-600" />
                    </div>
                </button>
            </div>
        </div>

        <!-- Backdrop -->
        <div
            v-if="isOpen"
            @click="closeDropdown"
            class="fixed inset-0 z-40"
        ></div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { useTenant } from '@/composables/useTenant';
import type { Tenant } from '@/stores/tenant';

const { activeTenant, tenants, loading, error, selectTenant, fetchTenants } = useTenant();

const isOpen = ref(false);

function getInitials(name: string | undefined): string {
    if (!name) {
        return '?';
    }

    const parts = name.trim().split(/\s+/);

    if (parts.length === 0) {
        return '?';
    }

    if (parts.length === 1) {
        return parts[0].substring(0, 1).toUpperCase();
    }

    return (parts[0].substring(0, 1) + parts[parts.length - 1].substring(0, 1)).toUpperCase();
}

function getStatusTextClass(status: string): string {
    const classMap: Record<string, string> = {
        active: 'text-green-600',
        suspended: 'text-yellow-600',
        deleted: 'text-red-600',
    };

    return classMap[status] || 'text-neutral-500';
}

function getStatusIconClass(status: string): string {
    const classMap: Record<string, string> = {
        active: 'text-green-600',
        suspended: 'text-yellow-600',
        deleted: 'text-red-600',
    };

    return classMap[status] || 'text-neutral-500';
}

function getAvatarClasses(tenant: Tenant): string {
    if (activeTenant.value?.id === tenant.id) {
        return 'bg-blue-600 text-white';
    }

    if (tenant.status === 'suspended') {
        return 'bg-yellow-100 text-yellow-600';
    }

    if (tenant.status === 'deleted') {
        return 'bg-red-100 text-red-600';
    }

    return 'bg-neutral-200 text-neutral-700';
}

function getTenantButtonClasses(tenant: Tenant): string {
    if (tenant.status !== 'active') {
        return 'opacity-60 cursor-not-allowed';
    }

    if (activeTenant.value?.id === tenant.id) {
        return 'bg-blue-50';
    }

    return '';
}

function toggleDropdown(): void {
    isOpen.value = !isOpen.value;
}

function closeDropdown(): void {
    isOpen.value = false;
}

function selectTenantHandler(tenantId: number): void {
    selectTenant(tenantId);
    closeDropdown();
}

onMounted(() => {
    // Load tenants on component mount
    if (tenants.value.length === 0) {
        fetchTenants();
    }

    // Close dropdown when clicking outside
    document.addEventListener('click', closeDropdown);

    // Listen for tenant changed events
    window.addEventListener('tenant-changed', closeDropdown);
});

onUnmounted(() => {
    document.removeEventListener('click', closeDropdown);
    window.removeEventListener('tenant-changed', closeDropdown);
});
</script>

<style scoped>
button:disabled {
    cursor: not-allowed;
}
</style>
