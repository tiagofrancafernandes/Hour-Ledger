<template>
    <Teleport to="body">
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            @click.self="handleClose"
        >
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl transform transition-all" @click.stop>
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900">
                            {{ row ? `Edit Row #${row.row_number}` : 'New Row' }}
                        </h2>

                        <button class="text-gray-400 hover:text-gray-600" @click="handleClose">
                            <Icon icon="mdi:close" class="w-6 h-6" />
                        </button>
                    </div>
                </div>

                <form @submit.prevent="handleSubmit">
                    <div class="p-6 space-y-4">
                        <div
                            v-if="row && row.validation_errors && row.validation_errors.length > 0"
                            class="bg-red-50 border border-red-200 rounded-lg p-4"
                        >
                            <div class="flex items-start gap-2">
                                <Icon
                                    icon="mdi:alert-circle-outline"
                                    class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5"
                                />
                                <div>
                                    <h4 class="text-sm font-medium text-red-900 mb-1">Validation Errors:</h4>
                                    <ul class="list-disc list-inside text-sm text-red-800 space-y-1">
                                        <li v-for="(validationError, index) in row.validation_errors" :key="index">
                                            {{ validationError }}
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Reference Date *</label>

                            <CInput v-model="formData.reference_date" type="date" required :max="today" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Start Time</label>

                                <CInput v-model="formData.start_time" type="datetime-local" />

                                <p class="mt-1 text-xs text-gray-500">
                                    Optional. If both times are provided, hours will be calculated.
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">End Time</label>

                                <CInput v-model="formData.end_time" type="datetime-local" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Time</label>

                            <div class="flex gap-3">
                                <div class="flex-1">
                                    <label class="mb-1 block text-xs text-gray-600">Hours</label>
                                    <input
                                        v-model.number="formRowHours"
                                        type="number"
                                        min="0"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                                        placeholder="0"
                                    />
                                </div>
                                <div class="flex-1">
                                    <label class="mb-1 block text-xs text-gray-600">Minutes</label>
                                    <input
                                        v-model.number="formRowMinutes"
                                        type="number"
                                        min="0"
                                        max="59"
                                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                                        placeholder="0"
                                    />
                                </div>
                            </div>

                            <p class="mt-1 text-xs text-gray-500">
                                Optional if start/end times are provided. Otherwise required for credit/adjustment
                                types.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Input Type *</label>

                                <CSelect v-model="formData.input_type" required>
                                    <option value="debit">Debit (consume hours)</option>
                                    <option value="credit">Credit (add hours)</option>
                                    <option value="adjustment">Adjustment</option>
                                </CSelect>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>

                            <CInput v-model="formData.title" type="text" required maxlength="255" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>

                            <CTextarea v-model="formData.description" rows="3" />
                        </div>

                        <div>
                            <div class="space-y-2">
                                <div class="flex gap-2">
                                    <input
                                        v-model="newTag"
                                        type="text"
                                        placeholder="Type a tag and press Enter"
                                        class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        @keypress.enter.prevent="addTag"
                                    />

                                    <CButton type="button" preset="outlined-black" size="sm" @click="addTag">
                                        <Icon icon="mdi:plus" class="w-4 h-4" />
                                    </CButton>
                                </div>

                                <div v-if="formData.tags && formData.tags.length > 0" class="flex flex-wrap gap-2">
                                    <span
                                        v-for="(tag, index) in formData.tags"
                                        :key="index"
                                        class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm"
                                    >
                                        {{ tag }}
                                        <button
                                            type="button"
                                            class="text-gray-500 hover:text-gray-700"
                                            @click="removeTag(index)"
                                        >
                                            <Icon icon="mdi:close-circle" class="w-4 h-4" />
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-6 py-4 flex gap-3 justify-end rounded-b-lg">
                        <CButton type="button" preset="outlined-black" @click="handleClose">Cancel</CButton>

                        <CButton type="submit" preset="primary" :disabled="saving">
                            <Icon v-if="saving" icon="mdi:loading" class="w-5 h-5 animate-spin" />
                            <span v-else>{{ row ? 'Save' : 'Add' }}</span>
                        </CButton>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, computed, type Ref } from 'vue';
import { Icon } from '@iconify/vue';
import { splitDecimalHours, combineDualTimeInput } from '@/utils/timeFormatters';
import type { ImportPlanRow, ImportRowForm } from '@/types';

export interface ImportRowEditModalProps {
    isOpen: boolean;
    row?: ImportPlanRow | null;
    planId?: number;
}

const props = withDefaults(defineProps<ImportRowEditModalProps>(), {
    row: null,
    planId: undefined,
});

const emit = defineEmits<{
    close: [];
    save: [data: ImportRowForm];
    'update:isOpen': [value: boolean];
}>();

const formData: Ref<ImportRowForm> = ref({
    reference_date: '',
    start_time: null,
    end_time: null,
    hours: null,
    title: '',
    description: '',
    input_type: 'debit',
    tags: [],
});

// Separate hours and minutes inputs
const formRowHours = ref(0);
const formRowMinutes = ref(0);

// Sync dual time input to decimal hours
watch([formRowHours, formRowMinutes], ([h, m]) => {
    if (h === 0 && m === 0) {
        formData.value.hours = null;

        return;
    }

    formData.value.hours = combineDualTimeInput(h, m);
});

const newTag: Ref<string> = ref('');
const saving: Ref<boolean> = ref(false);

const today = computed(() => {
    const date = new Date();
    return date.toISOString().split('T')[0];
});

watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            if (props.row) {
                formData.value = {
                    reference_date: props.row.reference_date,
                    start_time: props.row.start_time || null,
                    end_time: props.row.end_time || null,
                    hours: props.row.hours || null,
                    title: props.row.title,
                    description: props.row.description || '',
                    input_type: props.row.input_type || 'debit',
                    tags: props.row.tags ? [...props.row.tags] : [],
                };

                // Split hours into separate fields
                if (props.row.hours) {
                    const { hours, minutes } = splitDecimalHours(props.row.hours);
                    formRowHours.value = hours;
                    formRowMinutes.value = minutes;
                } else {
                    formRowHours.value = 0;
                    formRowMinutes.value = 0;
                }
            } else {
                formData.value = {
                    reference_date: today.value ?? '',
                    start_time: null,
                    end_time: null,
                    hours: null,
                    title: '',
                    description: '',
                    input_type: 'debit',
                    tags: [],
                };
                formRowHours.value = 0;
                formRowMinutes.value = 0;
            }

            newTag.value = '';
        }
    }
);

function addTag(): void {
    const tag = newTag.value.trim();

    if (!tag) {
        return;
    }

    if (!formData.value.tags) {
        formData.value.tags = [];
    }

    if (!formData.value.tags.includes(tag)) {
        formData.value.tags.push(tag);
    }

    newTag.value = '';
}

function removeTag(index: number): void {
    if (formData.value.tags) {
        formData.value.tags.splice(index, 1);
    }
}

function handleClose(): void {
    emit('close');
    emit('update:isOpen', false);
}

function handleSubmit(): void {
    emit('save', formData.value);
}
</script>
