<script setup lang="ts">
const props = defineProps({
    title: {
        type: String,
        default: () => null,
    },
    description: {
        type: String,
        default: () => null,
    },
});
</script>

<template>
    <div class="flex items-start justify-between mb-6 gap-4">
        <div>
            <template v-if="$slots?.title">
                <h1 class="text-2xl font-bold text-gray-900">
                    <slot name="title" />
                </h1>
            </template>
            <template v-else>
                <h1 class="text-2xl font-bold text-gray-900">{{ props.title || '' }}</h1>
            </template>

            <template v-if="$slots?.description">
                <p class="mt-1 text-sm text-gray-500">
                    <slot name="description" />
                </p>
            </template>
            <template v-else-if="props.description">
                <p class="mt-1 text-sm text-gray-500">{{ props.description }}</p>
            </template>
        </div>

        <div data-slot="actions" class="flex items-center gap-2 flex-shrink-0">
            <slot name="actions" />
        </div>
    </div>
</template>
