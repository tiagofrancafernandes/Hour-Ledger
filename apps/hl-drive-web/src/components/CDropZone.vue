<template>
    <div
        data-component-name="CDropZone"
        class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition-colors cursor-pointer"
        :class="{
            'border-blue-500 bg-blue-50': isDragging,
            'pointer-events-none': props?.disabled,
        }"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleFileDrop"
        @click="handleFileInputClick"
        :disabled="props?.disabled"
    >
        <input
            ref="fileInput"
            type="file"
            :accept="acceptAttribute"
            class="hidden"
            @change="handleFileSelect"
            :disabled="props?.disabled"
        />

        <div v-if="selectedFile && selectedFile?.name" class="flex items-center justify-center gap-2">
            <Icon icon="mdi:file-document-outline" class="w-6 h-6 text-blue-600" />
            <span class="text-sm text-gray-700">{{ selectedFile.name }}</span>
            <button type="button" class="text-red-600 hover:text-red-800" @click.stop="clearFile">
                <Icon icon="mdi:close-circle-outline" class="w-5 h-5" />
            </button>
        </div>

        <div v-else>
            <Icon icon="mdi:file-upload-outline" class="w-12 h-12 mx-auto text-gray-400 mb-2" />
            <p :class="labelClasses">{{ label }}</p>
            <p class="text-xs text-gray-500 mt-1 flex flex-col content-center justify-center-safe">
                <span v-if="acceptAttribute">Accepted files: {{ acceptAttribute }}</span>
                <span v-if="maxSize">(max size: {{ maxSizeMb }}MB)</span>
            </p>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed, watch, type Ref } from 'vue';
import { useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';
import { useImport } from '@/composables/useImport';
import { useWallets } from '@/composables/useWallets';
import { useToast } from '@/composables/useToast';
import type { Wallet } from '@/types';

const emit = defineEmits(['mounted', 'unmounted', 'change', 'cleared']);

const router = useRouter();
const toast = useToast();

const props = defineProps({
    label: {
        type: String,
        default: () => 'Click or drag files here',
    },
    accept: {
        type: [String, Array],
        default: () => null,
    },
    maxSize: {
        type: Number,
        default: () => null,
    },
    required: {
        type: Boolean,
        default: () => false,
    },
    disabled: {
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
    let _classes: any = [];

    return _classes;
});

const labelClasses = computed(() => {
    let _classes: any = ['text-sm text-gray-600', props?.labelClasses];

    return _classes;
});

const label = computed(() => {
    return props?.label || 'Click or drag a file here or click to select';
});

const maxSize = computed(() => {
    let _maxSise: any = props?.maxSize;

    if (!_maxSise || _maxSise <= 0) {
        return null;
    }

    _maxSise = Number(_maxSise);

    return !isNaN(_maxSise) && _maxSise > 0 ? _maxSise : null;
});

const accept = computed(() => {
    if (!props?.accept) {
        return null;
    }

    let _accept: any[] = [];

    if (typeof props?.accept === 'string') {
        _accept.push(
            ...String(props?.accept)
                .trim()
                .split(',')
                .map((i: string) => i.trim())
        );
    }

    if (Array.isArray(props?.accept)) {
        _accept = props?.accept;
    }

    let regexCheck = /^[a-zA-Z0-9]+([._-][a-zA-Z0-9]+)*$/i;

    return _accept
        .filter((v: any) => typeof v === 'string')
        .filter((v: any) => v.trim())
        .filter((v: any) => v.trim().replace(/^[.*]+/, ''))
        .filter((v: any) => !v.includes(' '))
        .filter(Boolean)
        .map((v: any) => v.trim().replaceAll(/^\*/g, ''))
        .map((v: any) => v.trim().replaceAll(/^([\.\ ]){0,}/gi, ''))
        .filter((v: any) => regexCheck.test(v))
        .filter(Boolean);
});

const acceptAttribute = computed<string | undefined>(() => {
    let _accept: any[] = accept.value || [];

    if (!_accept.length) {
        return undefined;
    }

    // expected lik  accept=".csv,.xlsx"

    return _accept
        .map((v: any) =>
            v
                .trim()
                .replaceAll(/^\*/g, '')
                .replaceAll(/^([\.]){0,}/gi, '')
        )
        .map((v: any) => '.' + v.trim().replaceAll(/^([\.\ ]){0,}/gi, ''))
        .join(',');
});

const modelValue = defineModel<File | undefined>();

const selectedFile: Ref<File | null> = ref(null);
const fileInput: Ref<HTMLInputElement | null> = ref(null);
const isDragging: Ref<boolean> = ref(false);
const uploadError: Ref<string | null> = ref(null);

watch(
    () => selectedFile.value,
    (newValue, oldValue) => {
        emit('change', {
            target: fileInput.value,
            elementEvent: null,
            file: newValue,
            value: newValue,
            oldValue,
        });
    }
    // { deep: true }
);

function handleFileSelect(event: Event): void {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        validateAndSetFile(file);
    }

    modelValue.value = file;
    emit('change', {
        target,
        elementEvent: event,
        file: modelValue.value,
        value: modelValue.value,
    });
}

function handleFileInputClick(event: Event): void {
    fileInput.value?.click();
}

function handleFileDrop(event: DragEvent): void {
    isDragging.value = false;

    const file = event.dataTransfer?.files?.[0];

    if (file) {
        validateAndSetFile(file);
    }
}

function hasAllowedExtension(filename: string, allowedExtensions: string[]): boolean {
    const lowerName = filename.toLowerCase();

    return allowedExtensions.some((ext) => {
        return lowerName === ext.toLowerCase() || lowerName.endsWith(`.${ext.toLowerCase()}`);
    });
}

const maxSizeMb = computed(() => {
    let _maxSize = maxSize.value;
    return maxSize.value ? (_maxSize && _maxSize > 0 ? _maxSize : 10) * 1024 * 1024 : null;
});

function validateAndSetFile(file: File): void {
    uploadError.value = null;
    const allowedTypes = [
        'text/csv',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    let allowedExtensions = accept.value || [];

    // if (!allowedTypes.includes(file.type) && !file.name.endsWith('.csv') && !file.name.endsWith('.xlsx')) {
    if (allowedExtensions.length && !hasAllowedExtension(file.name, allowedExtensions)) {
        uploadError.value = `Allowed extensions: ${allowedExtensions.join(',')}`;
        return;
    }

    if (maxSizeMb.value && file.size > maxSizeMb.value) {
        uploadError.value = `File cannot exceed ${maxSizeMb.value}MB.`;
        return;
    }

    selectedFile.value = file;
}

function clearFile(): void {
    selectedFile.value = null;
    uploadError.value = null;

    if (fileInput.value) {
        fileInput.value.value = '';
    }

    /*
    // Changed to use watch
    emit('change', {
        target: fileInput.value,
        elementEvent: null,
        file: modelValue.value,
        value: modelValue.value,
    });
    */

    emit('cleared', {
        target: fileInput.value,
        elementEvent: null,
        file: modelValue.value,
        value: modelValue.value,
    });
}

onMounted(() => {
    emit('mounted');
});
</script>
