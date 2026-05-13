<script setup lang="ts">
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import { Icon } from '@iconify/vue';
import type { TypeaheadOption } from '@/types';

type RefreshOptionsParams = {
    searchTerm: string;
};

type FilterOptionsParams = {
    searchTerm: string;
    options: TypeaheadOption[];
};

type SetOptionsCallback = (options: TypeaheadOption[]) => void;

interface Props {
    modelValue?: unknown;
    label?: string;
    typeahead?: boolean;
    clearable?: boolean;
    disabled?: boolean;
    required?: boolean;
    initialOptionsMode?: 'on_mount' | 'on_click';
    initialOptions?: TypeaheadOption[] | (() => TypeaheadOption[] | Promise<TypeaheadOption[]>);
    refreshOptions?: ((params: RefreshOptionsParams) => TypeaheadOption[] | Promise<TypeaheadOption[]>) | null;
    filterOptionsHandler?:
        | ((params: FilterOptionsParams, setOptions: SetOptionsCallback) => TypeaheadOption[] | void)
        | null;
    initialValue?: unknown;
    searchValue?: string;
    placeholder?: string;
    emptyText?: string;
    loadingText?: string;
    containerClasses?: any;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: undefined,
    label: undefined,
    typeahead: true,
    clearable: false,
    disabled: false,
    required: false,
    initialOptionsMode: 'on_mount',
    initialOptions: undefined,
    refreshOptions: null,
    filterOptionsHandler: null,
    initialValue: undefined,
    searchValue: '',
    placeholder: 'Select an option',
    emptyText: 'No options found',
    loadingText: 'Loading...',
});

const emit = defineEmits<{
    'update:modelValue': [value: unknown];
    search: [payload: { searchTerm: string }];
    input: [value: unknown];
    before_change: [payload: { newValue: unknown; oldValue: unknown }];
    change: [payload: { value: unknown; option: TypeaheadOption | null }];
    open: [];
    close: [];
}>();

const DEBOUNCE_MS = 300;

const isOpen = ref(false);
const isLoading = ref(false);
const searchTerm = ref(props.searchValue || '');
const loadedOptions = ref<TypeaheadOption[]>([]);
const hasLoadedInitialOptions = ref(false);
const debounceTimer = ref<ReturnType<typeof setTimeout> | null>(null);
const searchInputRef = ref<HTMLInputElement | null>(null);

const currentValue = computed(() => {
    if (props.modelValue !== undefined) {
        return props.modelValue;
    }

    return props.initialValue;
});

const selectedOption = computed(() => {
    const val = currentValue.value;

    if (val === undefined || val === null || val === '') {
        return null;
    }

    return loadedOptions.value.find((opt) => opt.value === val) ?? null;
});

const displayOptions = computed(() => {
    if (!props.typeahead || !searchTerm.value) {
        return loadedOptions.value;
    }

    if (props.filterOptionsHandler) {
        let result: TypeaheadOption[] = [];

        const setOptions: SetOptionsCallback = (opts) => {
            result = opts;
        };

        const returned = props.filterOptionsHandler(
            { searchTerm: searchTerm.value, options: loadedOptions.value },
            setOptions
        );

        return Array.isArray(returned) ? returned : result;
    }

    if (!props.refreshOptions) {
        const term = searchTerm.value.toLowerCase();

        return loadedOptions.value.filter((opt) => {
            return opt.label.toLowerCase().includes(term);
        });
    }

    return loadedOptions.value;
});

async function resolveInitialOptions(): Promise<TypeaheadOption[]> {
    if (!props.initialOptions) {
        return [];
    }

    if (Array.isArray(props.initialOptions)) {
        return props.initialOptions;
    }

    const result = await props.initialOptions();

    return Array.isArray(result) ? result : [];
}

async function loadInitialOptions() {
    if (hasLoadedInitialOptions.value) {
        return;
    }

    isLoading.value = true;

    try {
        loadedOptions.value = await resolveInitialOptions();
        hasLoadedInitialOptions.value = true;
    } finally {
        isLoading.value = false;
    }
}

async function handleOpen() {
    isOpen.value = true;

    emit('open');

    if (props.initialOptionsMode === 'on_click') {
        await loadInitialOptions();
    }

    if (props.typeahead) {
        await nextTick();
        searchInputRef.value?.focus();
    }
}

function handleClose() {
    isOpen.value = false;
    searchTerm.value = '';

    emit('close');
}

function handleTriggerClick() {
    if (props.disabled) {
        return;
    }

    if (isOpen.value) {
        handleClose();
        return;
    }

    handleOpen();
}

// When initialOptions is a reactive array (e.g. a computed), sync loadedOptions automatically.
watch(
    () => props.initialOptions,
    (newOptions) => {
        if (Array.isArray(newOptions)) {
            loadedOptions.value = newOptions;
            hasLoadedInitialOptions.value = true;
        }
    },
    { deep: false }
);

function handleSelect(option: TypeaheadOption) {
    const oldValue = currentValue.value;

    emit('before_change', { newValue: option.value, oldValue });
    emit('update:modelValue', option.value);
    emit('input', option.value);
    emit('change', { value: option.value, option });

    handleClose();
}

function handleClear(event: MouseEvent | KeyboardEvent) {
    event.stopPropagation();

    const oldValue = currentValue.value;

    emit('before_change', { newValue: null, oldValue });
    emit('update:modelValue', null);
    emit('input', null);
    emit('change', { value: null, option: null });
}

watch(searchTerm, (newTerm) => {
    emit('search', { searchTerm: newTerm });

    if (!props.typeahead || !props.refreshOptions) {
        return;
    }

    if (debounceTimer.value) {
        clearTimeout(debounceTimer.value);
    }

    isLoading.value = true;

    debounceTimer.value = setTimeout(async () => {
        try {
            const results = await props.refreshOptions!({ searchTerm: newTerm });

            loadedOptions.value = Array.isArray(results) ? results : [];
        } catch {
            loadedOptions.value = [];
        } finally {
            isLoading.value = false;
        }
    }, DEBOUNCE_MS);
});

onMounted(async () => {
    if (props.initialOptionsMode === 'on_mount') {
        await loadInitialOptions();
    }
});
</script>

<template>
    <div class="relative" :class="containerClasses || []">
        <!-- Label -->
        <label v-if="label" class="block text-sm font-medium text-gray-700 mb-1.5">
            {{ label }}
            <span v-if="required" class="text-red-500 font-bold italic">*</span>
        </label>

        <!-- Trigger -->
        <button
            type="button"
            :disabled="disabled"
            :class="[
                'w-full flex items-center justify-between gap-2',
                'rounded-lg border bg-white px-3 py-2',
                'text-sm text-left transition-colors outline-none',
                {
                    'border-red-500 ring-1 ring-red-500': isOpen,
                    'border-gray-300 hover:border-gray-400 focus:border-red-500 focus:ring-1 focus:ring-red-500':
                        !isOpen && !disabled,
                    'bg-gray-50 text-gray-400 cursor-not-allowed opacity-60': disabled,
                },
            ]"
            @click="handleTriggerClick"
        >
            <span :class="{ 'text-gray-400': !selectedOption }">
                {{ selectedOption ? selectedOption.label : placeholder }}
            </span>

            <div class="flex items-center gap-1 shrink-0">
                <!-- Clear button -->
                <span
                    v-if="clearable && selectedOption"
                    role="button"
                    tabindex="0"
                    class="text-gray-400 hover:text-gray-600 transition-colors"
                    @click="handleClear"
                    @keydown.enter="handleClear"
                >
                    <Icon icon="heroicons:x-mark" class="w-3.5 h-3.5" />
                </span>

                <Icon
                    icon="heroicons:chevron-down"
                    class="w-4 h-4 text-gray-400 transition-transform duration-200"
                    :class="{ 'rotate-180': isOpen }"
                />
            </div>
        </button>

        <!-- Backdrop -->
        <Transition name="fade">
            <div v-if="isOpen" class="fixed inset-0 z-30" @click="handleClose" />
        </Transition>

        <!-- Dropdown -->
        <Transition name="scale-fade">
            <div
                v-if="isOpen"
                class="absolute top-full left-0 right-0 z-40 mt-1 rounded-lg border border-gray-200 bg-white shadow-lg overflow-hidden"
            >
                <!-- Search input (typeahead mode only) -->
                <div v-if="typeahead" class="border-b border-gray-100 p-2">
                    <div class="relative">
                        <Icon
                            icon="heroicons:magnifying-glass"
                            class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400"
                        />
                        <input
                            ref="searchInputRef"
                            v-model="searchTerm"
                            type="text"
                            class="w-full rounded-md border border-gray-200 py-1.5 pl-8 pr-3 text-sm outline-none focus:border-red-400 focus:ring-1 focus:ring-red-400 placeholder:text-gray-400"
                            placeholder="Search..."
                        />
                    </div>
                </div>

                <!-- Loading state -->
                <div v-if="isLoading" class="p-3 text-center text-sm text-gray-500">
                    {{ loadingText }}
                </div>

                <!-- Empty state -->
                <div v-else-if="displayOptions.length === 0" class="p-3 text-center text-sm text-gray-500 bg-gray-50">
                    {{ emptyText }}
                </div>

                <!-- Options list -->
                <ul v-else class="max-h-60 overflow-y-auto divide-y divide-gray-50">
                    <li
                        v-for="option in displayOptions"
                        :key="String(option.value)"
                        :class="{ 'bg-red-50': option.value === currentValue }"
                    >
                        <button
                            type="button"
                            class="w-full px-3 py-2.5 text-left text-sm"
                            :class="{
                                'font-medium text-red-700': option.value === currentValue,
                                'text-gray-800 hover:bg-gray-50': option.value !== currentValue,
                            }"
                            @click="handleSelect(option)"
                        >
                            {{ option.label }}
                        </button>
                    </li>
                </ul>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 150ms ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.scale-fade-enter-active,
.scale-fade-leave-active {
    transition:
        opacity 150ms ease,
        transform 150ms ease;
}

.scale-fade-enter-from,
.scale-fade-leave-to {
    opacity: 0;
    transform: translateY(-4px) scale(0.97);
}
</style>
