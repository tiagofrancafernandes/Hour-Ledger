<script setup lang="ts">
import { computed } from 'vue';
import { selectPresets, selectLabelPresets } from '@/tw-ui/presets';
import { useSlots, useAttrs } from 'vue';

const attrs = useAttrs();

const props = defineProps({
    label: {
        type: String,
    },
    type: {
        type: String,
        default: () => 'text',
    },
    name: {
        type: String,
    },
    required: {
        type: Boolean,
        default: () => false,
    },
    disabled: {
        type: Boolean,
        default: () => false,
    },
    class: {
        type: [String, Object, Array],
        default: () => null,
    },
    inputClasses: {
        type: [String, Object, Array],
        default: () => null,
    },
    labelClasses: {
        type: [String, Object, Array],
        default: () => null,
    },
    containerClasses: {
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

    let _classes: any = [props.class, props.inputClasses, presets[props.preset] ?? presets.default];

    return _classes;
});

const label = computed(() => {
    return props?.label || props?.name;
});

const labelClasses = computed(() => {
    let _classes: any = [
        // 'block text-sm font-medium text-gray-700 mb-2',
        // 'block text-sm font-medium text-gray-700 mb-1',
        // selectLabelPresets()?.default,
        selectLabelPresets()?.mb1,
        props?.labelClasses,
    ];

    return _classes;
});

const containerClasses = computed(() => {
    let containerClasses = props?.containerClasses || [];
    containerClasses = typeof containerClasses === 'string' ? containerClasses.split(' ') : containerClasses;
    let _classes: any[] = Array.isArray(containerClasses) ? containerClasses : [containerClasses];

    let hasWidth = _classes.some((v) => typeof v === 'string' && v.startsWith('w-'));

    if (!hasWidth) {
        _classes.unshift('w-full');
    }

    return _classes;
});

const modelValue = defineModel<string | number | undefined>();
</script>

<template>
    <div data-component-name="CInput" :class="containerClasses">
        <template v-if="$slots.label || label">
            <label :class="labelClasses">
                <template v-if="label === null">
                    <slot name="label" />
                </template>
                <template v-else>
                    {{ label || '' }}
                </template>

                <span v-if="required" class="text-red-500 font-bold italic">*</span>
            </label>
        </template>
        <input
            v-model="modelValue"
            :name="props.name"
            v-bind="{
                ...attrs,
                class: classes,
                type: type,
                required: props?.required,
                disabled: props?.disabled,
            }"
        />
    </div>
</template>
