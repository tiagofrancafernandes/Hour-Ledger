<template>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Import Records</h1>
            <p class="mt-2 text-gray-600">Upload a CSV or XLSX file to import multiple hour records</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">File Upload</h2>

                <form @submit.prevent="handleUpload">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Wallet</label>

                            <CSelect v-model="selectedWalletId" required>
                                <option :value="undefined">Select a wallet</option>
                                <option v-for="wallet in wallets" :key="wallet.id" :value="wallet.id">
                                    {{ wallet.name }}
                                    <span v-if="wallet.client">({{ wallet.client.name }})</span>
                                </option>
                            </CSelect>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">File (CSV or XLSX)</label>

                            <CDropZone
                                accept="csv,xlsx"
                                v-model="selectedFile"
                                @change="(ev: any) => console.log('changed', ev)"
                            />

                            <!-- the item below will be replaced by CDropZone -->
                            <div
                                v-if="false"
                                class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-500 transition-colors cursor-pointer"
                                :class="{ 'border-blue-500 bg-blue-50': isDragging }"
                                @dragover.prevent="isDragging = true"
                                @dragleave.prevent="isDragging = false"
                                @drop.prevent="handleFileDrop"
                                @click="fileInputClick"
                            >
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept=".csv,.xlsx"
                                    class="hidden"
                                    @change="handleFileSelect"
                                />

                                <div v-if="!selectedFile">
                                    <Icon icon="mdi:file-upload-outline" class="w-12 h-12 mx-auto text-gray-400 mb-2" />
                                    <p class="text-sm text-gray-600">Drag and drop a file here or click to select</p>
                                    <p class="text-xs text-gray-500 mt-1">CSV or XLSX (max. 10MB)</p>
                                </div>

                                <div v-else class="flex items-center justify-center gap-2">
                                    <Icon icon="mdi:file-document-outline" class="w-6 h-6 text-blue-600" />
                                    <span class="text-sm text-gray-700">{{ selectedFile?.name }}</span>
                                    <button
                                        type="button"
                                        class="text-red-600 hover:text-red-800"
                                        @click.stop="clearFile"
                                    >
                                        <Icon icon="mdi:close-circle-outline" class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-if="uploadError" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-800">{{ uploadError }}</p>
                        </div>

                        <div class="flex gap-3">
                            <CButton
                                type="submit"
                                preset="primary"
                                :disabled="!selectedWalletId || !selectedFile || uploading"
                                class="flex-1"
                            >
                                <Icon v-if="uploading" icon="mdi:loading" class="w-5 h-5 animate-spin" />
                                <span v-else>Upload and Validate</span>
                            </CButton>

                            <CButton type="button" preset="outlined-black" @click="$router.push('/imports')">
                                Cancel
                            </CButton>
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">How to Use</h2>

                <div class="space-y-4 text-sm text-gray-600">
                    <div>
                        <h3 class="font-medium text-gray-900 mb-2">1. Download Template</h3>
                        <p class="mb-2">Use our template to ensure your file is in the correct format.</p>

                        <div class="flex gap-2">
                            <CButton
                                preset="outlined-black"
                                size="sm"
                                :disabled="downloading"
                                @click="handleDownloadTemplate('csv')"
                            >
                                <Icon v-if="downloading" icon="mdi:loading" class="w-4 h-4 animate-spin" />
                                <Icon v-else icon="mdi:file-delimited-outline" class="w-4 h-4" />
                                Download CSV
                            </CButton>

                            <CButton
                                preset="outlined-black"
                                size="sm"
                                :disabled="downloading"
                                @click="handleDownloadTemplate('xlsx')"
                            >
                                <Icon v-if="downloading" icon="mdi:loading" class="w-4 h-4 animate-spin" />
                                <Icon v-else icon="mdi:file-excel-outline" class="w-4 h-4" />
                                Download XLSX
                            </CButton>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-medium text-gray-900 mb-2">2. Fill in the Data</h3>
                        <p>Complete the file with your data. All required fields must be present:</p>

                        <div class="mt-3 space-y-3">
                            <div>
                                <p class="font-medium text-gray-800 mb-2">Required Fields:</p>
                                <ul class="list-disc list-inside space-y-1 text-gray-600">
                                    <li>
                                        <strong>reference_date</strong>
                                        : Date in YYYY-MM-DD format
                                    </li>
                                    <li>
                                        <strong>title</strong>
                                        : Record title (max 255 characters)
                                    </li>
                                </ul>
                            </div>

                            <div>
                                <p class="font-medium text-gray-800 mb-2">Time Entry (choose ONE option):</p>
                                <div class="space-y-2 text-gray-600 ml-4 border-l-2 border-gray-200 pl-3">
                                    <p>
                                        <strong>Option A - Direct Hours:</strong>
                                        <strong>hours</strong>
                                        (numeric value, positive = credit, negative = debit)
                                    </p>
                                    <p class="text-sm italic">Example: 5.5 or -2.25</p>
                                </div>
                                <div class="space-y-2 text-gray-600 ml-4 border-l-2 border-gray-200 pl-3 mt-2">
                                    <p>
                                        <strong>Option B - Time Range:</strong>
                                        <strong>start_time</strong>
                                        and
                                        <strong>end_time</strong>
                                        (format: YYYY-MM-DD HH:MM:SS). Hours will be calculated automatically.
                                    </p>
                                    <p class="text-sm italic">
                                        Example: start_time: 2026-03-27 08:00:00, end_time: 2026-03-27 10:30:00
                                    </p>
                                </div>
                            </div>

                            <div>
                                <p class="font-medium text-gray-800 mb-2">Optional Fields:</p>
                                <ul class="list-disc list-inside space-y-1 text-gray-600">
                                    <li>
                                        <strong>input_type</strong>
                                        : Type of entry (debit, credit, or adjustment; defaults to debit)
                                    </li>
                                    <li>
                                        <strong>description</strong>
                                        : Additional details about the entry
                                    </li>
                                    <li>
                                        <strong>tags</strong>
                                        : Categories (comma-separated values)
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="font-medium text-gray-900 mb-2">3. Validation</h3>
                        <p>
                            After uploading, the system will validate all records. You can review and correct errors
                            before confirming the import.
                        </p>
                    </div>

                    <div>
                        <h3 class="font-medium text-gray-900 mb-2">4. Confirmation</h3>
                        <p>Once confirmed, the import will create ledger entries. This action cannot be undone.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, type Ref } from 'vue';
import { useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';
import { useImport } from '@/composables/useImport';
import { useWallets } from '@/composables/useWallets';
import { useToast } from '@/composables/useToast';
import type { Wallet } from '@/types';

const router = useRouter();
const { uploadFile, downloadTemplate } = useImport();
const { wallets, fetchWallets } = useWallets();
const toast = useToast();

const selectedWalletId: Ref<number | undefined> = ref(undefined);
const selectedFile: Ref<File | null> = ref(null);
const alterSelectedFile: Ref<File | null> = ref(null);
const fileInput: Ref<HTMLInputElement | null> = ref(null);
const isDragging: Ref<boolean> = ref(false);
const uploading: Ref<boolean> = ref(false);
const downloading: Ref<boolean> = ref(false);
const uploadError: Ref<string | null> = ref(null);

onMounted(async () => {
    await fetchWallets();
});

function handleFileSelect(event: Event): void {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        validateAndSetFile(file);
    }
}

function fileInputClick(): void {
    fileInput.value?.click();
}

function handleFileDrop(event: DragEvent): void {
    isDragging.value = false;

    const file = event.dataTransfer?.files?.[0];

    if (file) {
        validateAndSetFile(file);
    }
}

function validateAndSetFile(file: File): void {
    uploadError.value = null;

    const allowedTypes = [
        'text/csv',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];
    const maxSize = 10 * 1024 * 1024;

    if (!allowedTypes.includes(file.type) && !file.name.endsWith('.csv') && !file.name.endsWith('.xlsx')) {
        uploadError.value = 'Only CSV and XLSX files are allowed.';
        return;
    }

    if (file.size > maxSize) {
        uploadError.value = 'The file cannot exceed 10MB.';
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
}

async function handleUpload(): Promise<void> {
    if (!selectedWalletId.value || !selectedFile.value) {
        return;
    }

    uploading.value = true;
    uploadError.value = null;

    try {
        const importPlan = await uploadFile({
            wallet_id: selectedWalletId.value,
            file: selectedFile.value,
        });

        if (!importPlan?.id) {
            console.log('importPlan', importPlan);
            toast.error('There was an error uploading the file...');
            return;
        }

        toast.success('File uploaded successfully! Redirecting for review...');

        setTimeout(() => {
            router.push(`/imports/${importPlan?.id}/review`);
        }, 1000);
    } catch (err) {
        uploadError.value = err instanceof Error ? err.message : 'Error uploading the file.';
        toast.error('Error uploading the file');
    } finally {
        uploading.value = false;
    }
}

async function handleDownloadTemplate(format: 'csv' | 'xlsx' = 'csv'): Promise<void> {
    downloading.value = true;

    console.log('format', format);

    try {
        await downloadTemplate(format);
        toast.success(`Template ${format.toUpperCase()} downloaded successfully`);
    } catch (err) {
        toast.error('Error downloading template');
    } finally {
        downloading.value = false;
    }
}
</script>
