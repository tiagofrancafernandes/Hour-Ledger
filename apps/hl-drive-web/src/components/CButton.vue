<script setup lang="ts">
import { computed } from 'vue';
import { buttonPresets } from '@/tw-ui/presets';

const props = defineProps({
    label: {
        type: String,
        default: () => null,
    },
    preset: {
        type: String,
        default: 'primary',
    },
    icon: {
        type: String,
    },
    rightIcon: {
        type: String,
    },
    flex: {
        type: Boolean,
        default: () => false,
    },
});

const classes = computed(() => {
    const presets: any = buttonPresets();

    let hasIcon = props.icon || props.rightIcon;

    let _classes: any = [
        'inline-flex items-center align-center',
        presets[props.preset] ?? presets.blue,
        {
            'justify-center': !hasIcon,
            'justify-between gap-x-2': hasIcon,
            'flex-1': props.flex,
        },
    ];

    if (props.icon || props.rightIcon) {
        _classes = [..._classes];
    }

    return _classes;
});
</script>

<template>
    <button :class="classes" data-component-name="CButton">
        <template v-if="props.icon">
            <UIcon :icon="props.icon" />
        </template>
        <template v-if="props?.label === null">
            <slot />
        </template>
        <template v-if="props.rightIcon">
            <UIcon :icon="props.rightIcon" />
        </template>
    </button>
</template>
