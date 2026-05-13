<script setup lang="ts">
import { computed, ref } from 'vue';
import { selectPresets, selectLabelPresets } from '@/tw-ui/presets';
import { useSlots, useAttrs } from 'vue';

const attrs = useAttrs();

const props = defineProps({
    label: {
        type: String,
    },
    type: {
        type: String,
        default: () => 'password',
    },
    name: {
        type: String,
    },
    id: {
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
        // default: 'default',
    },
    error: {
        type: String,
    },
});

const inputId =
    props?.id ||
    'password_' +
        btoa(props?.name || Math.random().toString().slice(2, 8))
            .trim()
            .toLowerCase()
            .replaceAll('=', '');

const showPassword = ref(false);

const error = computed(() => {
    return typeof props?.error === 'string' ? props?.error?.trim() : '';
});

const classes = computed(() => {
    const presets: any = selectPresets();

    let _classes: any = [
        props.class,
        props.inputClasses,
        props.preset ? (presets[props.preset] ?? presets.default) : null,
        'w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 pr-10 text-sm text-gray-900 placeholder:text-gray-400 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 transition-colors',
    ];

    if (error.value) {
        _classes.push('border-red-500');
    }

    return _classes;
});

const label = computed(() => {
    return props?.label || '';
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
    <div data-component-name="CPasswodInput" :class="containerClasses">
        <template v-if="$slots.label || label">
            <label :class="labelClasses" :for="inputId">
                <template v-if="label === null">
                    <slot name="label" />
                </template>
                <template v-else>
                    {{ label || '' }}
                </template>

                <span v-if="required" class="text-red-500 font-bold italic">*</span>
            </label>
        </template>

        <div class="relative">
            <input
                :id="inputId"
                v-model="modelValue"
                :name="props.name"
                v-bind="{
                    ...attrs,
                    class: classes,
                    type: showPassword ? 'text' : 'password',
                    required: props?.required,
                    disabled: props?.disabled,
                }"
                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 pr-10 text-sm text-gray-900 placeholder:text-gray-400 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500 transition-colors"
            />
            <button
                type="button"
                class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                @click="showPassword = !showPassword"
                tabindex="-1"
                aria-pressed="false"
            >
                <Icon :icon="showPassword ? 'heroicons:eye-slash' : 'heroicons:eye'" class="w-4 h-4" />
            </button>
        </div>

        <p v-if="error" class="mt-1 text-xs text-red-600">
            {{ error }}
        </p>
    </div>
</template>
