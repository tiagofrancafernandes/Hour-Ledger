<script setup lang="ts">
// client/components/ClientSwitcher.vue
// This component is UI-only.
// No persistence, no API calls, no side effects.

interface ClientItem {
    id: string;
    name: string;
    env: string;
}

const props = defineProps<{
    current: ClientItem;
    clients: ClientItem[];
}>();

const emit = defineEmits<{
    (e: 'select', client: ClientItem): void;
    (e: 'create'): void;
}>();

const search = ref('');

const filteredClients = computed(() => {
    if (!search.value) {
        return props.clients;
    }

    return props.clients.filter((client) => client.name.toLowerCase().includes(search.value.toLowerCase()));
});
</script>

<template>
    <UDropdown :ui="{ width: 'w-80' }">
        <template #default>
            <button class="flex items-center gap-2 text-sm font-medium text-gray-900 hover:text-gray-700">
                <!-- Client -->
                <span class="flex items-center gap-2">
                    <span
                        class="h-8 w-8 rounded-md bg-red-600 text-white flex items-center justify-center text-sm font-semibold"
                    >
                        {{ current.name.charAt(0).toLowerCase() }}
                    </span>

                    <span>{{ current.name }}</span>
                </span>

                <span class="text-gray-300">/</span>

                <!-- Environment -->
                <span class="flex items-center gap-1">
                    <span
                        class="h-8 w-8 rounded-md bg-red-600 text-white flex items-center justify-center text-sm font-semibold"
                    >
                        {{ current.env.charAt(0).toLowerCase() }}
                    </span>

                    <span>{{ current.env }}</span>
                </span>

                <UIcon name="i-heroicons-chevron-up-down" class="h-4 w-4 text-gray-400" />
            </button>
        </template>

        <template #content>
            <div class="p-3 space-y-3">
                <!-- Search -->
                <UInput v-model="search" placeholder="Search client..." size="sm" icon="i-heroicons-magnifying-glass" />

                <!-- Clients -->
                <div class="space-y-1">
                    <p class="px-1 text-xs text-gray-500">Clients</p>

                    <button
                        v-for="client in filteredClients"
                        :key="client.id"
                        class="w-full flex items-center justify-between gap-2 rounded-md px-3 py-2 text-sm hover:bg-gray-100 text-left"
                        @click="emit('select', client)"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="h-6 w-6 rounded-md bg-red-600 text-white flex items-center justify-center text-xs font-semibold"
                            >
                                {{ client.name.charAt(0).toLowerCase() }}
                            </span>

                            <span>{{ client.name }}</span>
                        </div>

                        <span class="text-xs text-gray-500">
                            {{ client.env }}
                        </span>
                    </button>
                </div>

                <!-- Create -->
                <div class="border-t border-gray-200 pt-2">
                    <button
                        class="w-full flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md"
                        @click="emit('create')"
                    >
                        <UIcon name="i-heroicons-plus" class="h-4 w-4" />
                        Create new client
                    </button>
                </div>
            </div>
        </template>
    </UDropdown>
</template>
