<script setup lang="ts">
import { computed } from 'vue';
import { selectPresets } from '@/tw-ui/presets';

const props = defineProps({
    label: {
        type: String,
        default: () => 'Select',
    },
    required: {
        type: Boolean,
        default: () => false,
    },
    disabled: {
        type: Boolean,
        default: () => false,
    },
    multiple: {
        type: Boolean,
        default: () => false,
    },
    labelClasses: {
        type: [String, Object, Array],
        default: () => null,
    },
    preset: {
        type: String,
        default: 'default',
    },
});

const classes = computed(() => {
    const presets: any = selectPresets();

    let _classes: any = [presets[props.preset] ?? presets.default];

    return _classes;
});

const labelClasses = computed(() => {
    let _classes: any = ['block text-sm font-medium text-gray-700 mb-2', props?.labelClasses];

    return _classes;
});

const modelValue = defineModel<string | number | undefined>();
</script>

<template>
    <div data-component-name="CSelect">
        <label :class="labelClasses">
            <template v-if="props?.label === null">
                <slot name="label" />
            </template>
            <template v-else>
                {{ props?.label || '' }}
            </template>

            <span v-if="required" class="text-red-500 font-bold italic">*</span>
        </label>
        <select
            :class="classes"
            v-model="modelValue"
            :required="props?.required"
            :disabled="props?.disabled"
            :multiple="props?.multiple"
        >
            <slot />
        </select>
    </div>
</template>
