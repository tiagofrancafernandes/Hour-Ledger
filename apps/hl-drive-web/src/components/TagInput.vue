<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Icon } from '@iconify/vue';
import { useTags } from '@/composables/useTags';
import type { Tag } from '@/types';

interface Props {
    modelValue?: number[];
    availableTags?: Tag[];
    placeholder?: string;
    allowCreate?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    availableTags: () => [],
    placeholder: 'Search tags...',
    allowCreate: true,
});

const emit = defineEmits<{
    'update:modelValue': [value: number[]];
}>();

const { tags: fetchedTags, fetchTags, createTag } = useTags();

const inputValue = ref('');
const showDropdown = ref(false);
const isCreating = ref(false);
const inputRef = ref<HTMLInputElement | null>(null);
const dropdownContainer = ref<HTMLDivElement | null>(null);

const allKnownTags = computed((): Tag[] => {
    const map = new Map<number, Tag>();

    for (const tag of props.availableTags) {
        map.set(tag.id, tag);
    }

    for (const tag of fetchedTags.value) {
        map.set(tag.id, tag);
    }

    return Array.from(map.values());
});

const selectedTagObjects = computed(() => {
    return allKnownTags.value.filter((tag) => props.modelValue.includes(tag.id));
});

const filteredTags = computed(() => {
    const search = inputValue.value.toLowerCase().trim();

    return allKnownTags.value.filter((tag) => {
        if (props.modelValue.includes(tag.id)) {
            return false;
        }

        if (!search) {
            return true;
        }

        return tag.name.toLowerCase().includes(search);
    });
});

const exactMatchExists = computed(() => {
    const search = inputValue.value.toLowerCase().trim();

    if (!search) {
        return true;
    }

    return allKnownTags.value.some((tag) => tag.name.toLowerCase() === search);
});

function emitChange(newValue: number[]): void {
    emit('update:modelValue', newValue);
}

function addTag(tag: Tag): void {
    if (props.modelValue.includes(tag.id)) {
        return;
    }

    emitChange([...props.modelValue, tag.id]);

    inputValue.value = '';
}

function removeTag(tagId: number): void {
    emitChange(props.modelValue.filter((id) => id !== tagId));
}

async function handleCreateTag(tagName: string): Promise<void> {
    if (!tagName || !props.allowCreate) {
        return;
    }

    const existing = allKnownTags.value.find((tag) => tag.name.toLowerCase() === tagName.toLowerCase());

    if (existing) {
        if (!props.modelValue.includes(existing.id)) {
            addTag(existing);
        }

        inputValue.value = '';

        return;
    }

    isCreating.value = true;

    try {
        const newTag = await createTag(tagName);

        addTag(newTag);

        inputValue.value = '';
    } catch (error) {
        console.error('Failed to create tag:', error);
    } finally {
        isCreating.value = false;
    }
}

async function handleInput(): Promise<void> {
    const value = inputValue.value;
    const lastChar = value.slice(-1);

    if (lastChar === ',') {
        inputValue.value = value.slice(0, -1).trim();

        await handleCreateTag(inputValue.value);

        return;
    }

    showDropdown.value = true;

    await fetchTags(value.trim());
}

async function handleKeydown(event: KeyboardEvent): Promise<void> {
    if (event.key === 'Enter') {
        event.preventDefault();

        const trimmed = inputValue.value.trim();

        if (!trimmed) {
            return;
        }

        if (filteredTags.value.length === 1 && filteredTags.value[0]) {
            addTag(filteredTags.value[0]);

            return;
        }

        if (props.allowCreate && !exactMatchExists.value) {
            await handleCreateTag(trimmed);

            return;
        }

        const match = allKnownTags.value.find((tag) => tag.name.toLowerCase() === trimmed.toLowerCase());

        if (match) {
            addTag(match);
        }
    }

    if (event.key === 'Escape') {
        showDropdown.value = false;
        inputValue.value = '';
    }

    if (event.key === 'Backspace' && !inputValue.value && selectedTagObjects.value.length > 0) {
        const lastTag = selectedTagObjects.value[selectedTagObjects.value.length - 1];

        if (lastTag) {
            removeTag(lastTag.id);
        }
    }
}

function calculateInputBounding(): void {
    const input = inputRef.value;
    const dropdown = dropdownContainer.value;

    if (!input || !dropdown) {
        return;
    }

    const rect = input.getBoundingClientRect();

    dropdown.style.top = `${rect.bottom}px`;
    dropdown.style.left = `${rect.left}px`;
    dropdown.style.width = `${rect.width}px`;
}

function handleFocus(): void {
    showDropdown.value = true;
    calculateInputBounding();
}

function handleBlur(): void {
    setTimeout(() => {
        showDropdown.value = false;
    }, 200);
}

function focusInput(): void {
    inputRef.value?.focus();
    calculateInputBounding();
}

onMounted(async () => {
    await fetchTags('');
});
</script>

<template>
    <div class="relative">
        <!-- Main container -->
        <div
            class="rounded-lg border border-gray-300 bg-white transition-colors focus-within:border-red-500 focus-within:ring-2 focus-within:ring-red-500/20"
            @click="focusInput"
        >
            <!-- Selected tags row (horizontal scroll) -->
            <div
                v-if="selectedTagObjects.length > 0"
                class="flex gap-1.5 overflow-x-auto px-3 pt-2.5 pb-2.5 scrollbar-thin border-b border-red-400"
            >
                <span
                    v-for="tag in selectedTagObjects"
                    :key="tag.id"
                    class="inline-flex shrink-0 items-center gap-1 rounded-full bg-red-100 px-2.5 py-2 text-xs font-medium text-red-700"
                >
                    {{ tag.name }}
                    <button
                        type="button"
                        class="flex items-center rounded-full text-red-500 transition-colors hover:text-red-800 focus:outline-none"
                        :aria-label="`Remove tag ${tag.name}`"
                        @click.stop="removeTag(tag.id)"
                    >
                        <Icon icon="heroicons:x-mark" class="h-3 w-3" />
                    </button>
                </span>
            </div>

            <!-- Search input row -->
            <div class="flex items-center gap-2 px-3 py-2">
                <Icon icon="heroicons:magnifying-glass" class="h-4 w-4 shrink-0 text-gray-400" />

                <input
                    ref="inputRef"
                    v-model="inputValue"
                    type="text"
                    class="flex-1 bg-transparent text-sm text-gray-800 placeholder-gray-400 outline-none"
                    :placeholder="selectedTagObjects.length === 0 ? placeholder : 'Add more...'"
                    :disabled="isCreating"
                    @input="handleInput"
                    @keydown="handleKeydown"
                    @focus="handleFocus"
                    @blur="handleBlur"
                />

                <Icon
                    v-if="isCreating"
                    icon="heroicons:arrow-path"
                    class="h-4 w-4 shrink-0 animate-spin text-red-500"
                />
            </div>
        </div>

        <!-- Dropdown -->
        <Transition name="scale-fade">
            <div
                v-show="
                    showDropdown && (filteredTags.length > 0 || (allowCreate && inputValue.trim() && !exactMatchExists))
                "
                ref="dropdownContainer"
                class="fixed z-50 mt-1 max-h-56 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg"
            >
                <!-- Heading -->
                <div class="border-b border-gray-100 px-3 py-1.5">
                    <span class="text-xs font-medium text-gray-400 uppercase tracking-wide">
                        {{ inputValue.trim() ? 'Results' : 'Suggestions' }}
                    </span>
                </div>

                <!-- Tag options -->
                <div v-if="filteredTags.length > 0" class="p-1">
                    <button
                        v-for="tag in filteredTags"
                        :key="tag.id"
                        type="button"
                        class="flex w-full items-center gap-2 rounded-md px-3 py-1.5 text-sm text-gray-700 transition-colors hover:bg-red-50 hover:text-red-700"
                        @mousedown.prevent="addTag(tag)"
                    >
                        <Icon icon="heroicons:tag" class="h-3.5 w-3.5 text-gray-400" />
                        {{ tag.name }}
                    </button>
                </div>

                <!-- Empty search result -->
                <div v-else-if="inputValue.trim() && !allowCreate" class="px-4 py-3 text-center text-sm text-gray-400">
                    No tags found
                </div>

                <!-- Create new tag option -->
                <div v-if="allowCreate && inputValue.trim() && !exactMatchExists" class="border-t border-gray-100 p-1">
                    <button
                        type="button"
                        class="flex w-full items-center gap-2 rounded-md px-3 py-1.5 text-sm text-red-600 transition-colors hover:bg-red-50"
                        @mousedown.prevent="handleCreateTag(inputValue.trim())"
                    >
                        <Icon icon="heroicons:plus" class="h-3.5 w-3.5" />
                        Create
                        <strong class="ml-1">"{{ inputValue.trim() }}"</strong>
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Helper text -->
        <p v-if="allowCreate" class="mt-1 text-xs text-gray-400">
            Press
            <kbd class="rounded bg-gray-100 px-1 py-0.5 font-mono text-xs text-gray-500">Enter</kbd>
            or
            <kbd class="rounded bg-gray-100 px-1 py-0.5 font-mono text-xs text-gray-500">,</kbd>
            to create a new tag &middot;
            <kbd class="rounded bg-gray-100 px-1 py-0.5 font-mono text-xs text-gray-500">Backspace</kbd>
            to remove last
        </p>
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
