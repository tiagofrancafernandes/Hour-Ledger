<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useClients } from '@/composables/useClients';
import { useWallets } from '@/composables/useWallets';
import { useLedger } from '@/composables/useLedger';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';
import { splitDecimalHours, combineDualTimeInput } from '@/utils/timeFormatters';
import { extractErrorMessage } from '@/utils/errorHelpers';
import type { TypeaheadOption, LedgerEntryForm, TimezoneConfig } from '@/types';

import { useTimerStore } from '@/stores/timer';
import { useDate } from '@/composables/useDate';
import { getTimezoneList, TZ_DEFAULT } from '@/utils/date-helpers';
const timerStore = useTimerStore();

interface Props {
    show: boolean;
    walletId?: number;
}

interface Emits {
    (e: 'close'): void;
    (e: 'entry-created'): void;
}

const props = withDefaults(defineProps<Props>(), {
    walletId: undefined,
});

const emit = defineEmits<Emits>();

const route = useRoute();
const toast = useToast();

// Composables
const { clients, fetchClients } = useClients();
const { wallets, fetchWallets } = useWallets();
const { createEntry, loading: entryLoading } = useLedger();
const { canAddCredits, canAddDebits, canAddAdjustments } = usePermissions();

const { targetTimezone, resolveTimezone } = useDate();

// Step state
const currentStep = ref(1);

// Form state - Step 1
const selectedClientId = ref<number | null>(null);
const selectedWalletId = ref<number | null>(null);

const walletId = props?.walletId || Number(route.params.id) || 0;

// Form state - Step 2
const entryForm = ref<LedgerEntryForm>({
    wallet_id: walletId,
    type: 'debit',
    hours: 0,
    title: '',
    description: '',
    reference_date: new Date().toISOString().split('T')[0],
    reference_date_timezone: targetTimezone.value || TZ_DEFAULT,
    tags: [],
});

const entryFormHours = ref(0);
const entryFormMinutes = ref(0);
const entryLoadingLocal = ref(false);
const entryError = ref<string>('');

// Computed properties
const manualClientOptions = computed<TypeaheadOption[]>(() => {
    return clients.value.map((client) => ({
        value: client.id,
        label: `${client.name}${client.notes ? ` - ${client.notes}` : ''}`,
    }));
});

const manualWalletOptions = computed<TypeaheadOption[]>(() => {
    return wallets.value.map((wallet) => ({
        value: wallet.id,
        label: wallet.name,
    }));
});

const canAddEntry = computed(() => {
    return canAddCredits.value || canAddDebits.value || canAddAdjustments.value;
});

const isStep1Valid = computed(() => {
    return selectedWalletId.value !== null;
});

const isStep2Valid = computed(() => {
    return entryForm.value.type && entryForm.value.hours > 0;
});

onMounted(() => {
    entryForm.value.reference_date_timezone =
        entryForm.value.reference_date_timezone || targetTimezone.value || TZ_DEFAULT;
});

// Watchers
watch([entryFormHours, entryFormMinutes], ([h, m]) => {
    entryForm.value.hours = combineDualTimeInput(h, m);
});

watch(selectedClientId, () => {
    selectedWalletId.value = null;
    void refreshManualWalletOptions({ searchTerm: '' });
});

watch(
    () => props.show,
    (newShow) => {
        if (newShow) {
            currentStep.value = 1;
            selectedClientId.value = null;
            selectedWalletId.value = null;
            entryForm.value = {
                wallet_id: 0,
                type: 'debit',
                hours: 0,
                tags: [],
                reference_date: new Date().toISOString().split('T')[0],
                title: '',
                description: '',
            };
            entryFormHours.value = 0;
            entryFormMinutes.value = 0;
            entryError.value = '';
            void refreshManualClientOptions({ searchTerm: '' });
            void refreshManualWalletOptions({ searchTerm: '' });
        }
    }
);

// Methods
async function refreshManualClientOptions({ searchTerm }: { searchTerm: string }): Promise<TypeaheadOption[]> {
    await fetchClients(1, searchTerm || '');

    return manualClientOptions.value;
}

async function refreshManualWalletOptions({ searchTerm }: { searchTerm: string }): Promise<TypeaheadOption[]> {
    await fetchWallets(selectedClientId.value || undefined, 1);

    return manualWalletOptions.value;
}

function handleStep1Continue(): void {
    if (!isStep1Valid.value || !selectedWalletId.value) {
        return;
    }

    entryForm.value.wallet_id = selectedWalletId.value;
    currentStep.value = 2;
}

function handleStep2Back(): void {
    currentStep.value = 1;
}

async function handleCreateEntry(): Promise<void> {
    if (!isStep2Valid.value || entryLoadingLocal.value) {
        return;
    }

    if (entryForm.value.hours <= 0) {
        toast.warning('Entry hours must be greater than 0');
        return;
    }

    entryLoadingLocal.value = true;
    entryError.value = '';

    try {
        await createEntry(entryForm.value);
        toast.success('Entry created successfully');
        emit('entry-created');
        closeModal();
    } catch (error) {
        entryError.value = extractErrorMessage(error);
        toast.error(entryError.value);
    } finally {
        entryLoadingLocal.value = false;
    }
}

function handleEntryFormHoursChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    let value = parseInt(target.value, 10) || 0;
    const max = 24;

    if (value < 0) {
        value = 0;
    }

    if (value > max) {
        value = max;
    }

    entryFormHours.value = value;
    target.value = String(entryFormHours.value);
}

function handleEntryFormMinutesChange(event: Event): void {
    const target = event.target as HTMLInputElement;
    let value = parseInt(target.value, 10) || 0;
    const max = 59;

    if (value < 0) {
        value = 0;
    }

    if (value > max) {
        value = max;
    }

    entryFormMinutes.value = value;
    target.value = String(entryFormMinutes.value);
}

const timezoneList = computed(() => {
    return getTimezoneList((tzList: Record<string, TimezoneConfig>): { label: string; value: string }[] => {
        // return [{label: 'string', value: 'string'}];

        return Object.entries(tzList)
            .map((i) => i[1])
            .map((i) => ({
                ...i,
                label: `${i.label} (${i.offset})`,
                value: i.timezone_id,
            }));
    });
});

function refreshTzListOptions({ searchTerm }: { searchTerm: string | null }): TypeaheadOption[] {
    const items = (timezoneList.value || []) as TypeaheadOption[];

    searchTerm = typeof searchTerm === 'string' ? searchTerm.trim().toLowerCase() : '';

    return searchTerm
        ? items.filter((i: any) => {
              return JSON.stringify(i, null, 0).toLowerCase().includes(searchTerm);
          })
        : items;
}

function closeModal(): void {
    emit('close');
}
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 px-4 py-6"
        @click.self="closeModal"
    >
        <div class="w-full max-w-4xl rounded-2xl bg-white p-6 shadow-xl" @click.stop>
            <!-- Step 1: Wallet Selection -->
            <div v-if="currentStep === 1">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Manual Entry</p>
                        <h3 class="text-xl font-semibold text-gray-900">Select Wallet</h3>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-700" @click="closeModal">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 6l8 8m0-8l-8 8"
                            />
                        </svg>
                    </button>
                </div>

                <p class="mt-2 text-sm text-gray-500">Use the client to reduce wallet options before continuing.</p>

                <div class="mt-6 space-y-4 flex gap-3">
                    <CTypeahead
                        v-model="selectedClientId"
                        label="Client"
                        class="w-full"
                        placeholder="Search by name or notes"
                        clearable
                        :initial-options="manualClientOptions"
                        :refresh-options="refreshManualClientOptions"
                        empty-text="No clients found"
                        loading-text="Loading clients..."
                    />

                    <CTypeahead
                        v-model="selectedWalletId"
                        label="Wallet"
                        class="w-full"
                        placeholder="Choose wallet"
                        clearable
                        :initial-options="manualWalletOptions"
                        :refresh-options="refreshManualWalletOptions"
                        empty-text="No wallets found"
                        loading-text="Loading wallets..."
                    />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <CButton preset="gray" @click="closeModal">Cancel</CButton>
                    <CButton preset="primary" :disabled="!isStep1Valid" @click="handleStep1Continue">Continue</CButton>
                </div>
            </div>

            <!-- Step 2: Entry Form -->
            <div v-if="currentStep === 2">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Manual Entry</p>
                        <h3 class="text-xl font-semibold text-gray-900">Add Ledger Entry</h3>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-700" @click="closeModal">
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 6l8 8m0-8l-8 8"
                            />
                        </svg>
                    </button>
                </div>

                <div class="max-h-[60vh] overflow-y-auto mt-4 space-y-4">
                    <!-- Type Selector -->
                    <div class="mb-4">
                        <label class="mb-2 block text-sm font-medium text-gray-700">Type</label>
                        <div class="flex gap-3">
                            <!-- Debit Card -->
                            <label v-if="canAddDebits" class="flex-1 cursor-pointer">
                                <input v-model="entryForm.type" type="radio" value="debit" class="sr-only" />
                                <div
                                    :class="[
                                        'flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-all',
                                        {
                                            'border-red-500 bg-red-50': entryForm.type === 'debit',
                                            'border-gray-200 bg-white hover:border-red-300': entryForm.type !== 'debit',
                                        },
                                    ]"
                                >
                                    <svg
                                        class="h-6 w-6 text-red-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M13 13h8m-8-6h8M5 5a2 2 0 012-2h.01A2 2 0 019 5v.01A2 2 0 017.01 9H7a2 2 0 01-2-2V5z"
                                        />
                                    </svg>
                                    <div class="text-center">
                                        <p class="text-sm font-semibold text-gray-900">Debit</p>
                                        <p class="text-xs text-gray-600">Consume hours</p>
                                    </div>
                                </div>
                            </label>

                            <!-- Credit Card -->
                            <label v-if="canAddCredits" class="flex-1 cursor-pointer">
                                <input v-model="entryForm.type" type="radio" value="credit" class="sr-only" />
                                <div
                                    :class="[
                                        'flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-all',
                                        {
                                            'border-green-500 bg-green-50': entryForm.type === 'credit',
                                            'border-gray-200 bg-white hover:border-green-300':
                                                entryForm.type !== 'credit',
                                        },
                                    ]"
                                >
                                    <svg
                                        class="h-6 w-6 text-green-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M12 4v16m8-8H4"
                                        />
                                    </svg>
                                    <div class="text-center">
                                        <p class="text-sm font-semibold text-gray-900">Credit</p>
                                        <p class="text-xs text-gray-600">Add hours</p>
                                    </div>
                                </div>
                            </label>

                            <!-- Adjustment Card -->
                            <label v-if="canAddAdjustments" class="flex-1 cursor-pointer">
                                <input v-model="entryForm.type" type="radio" value="adjustment" class="sr-only" />
                                <div
                                    :class="[
                                        'flex flex-col items-center gap-2 rounded-lg border-2 p-4 transition-all',
                                        {
                                            'border-amber-500 bg-amber-50': entryForm.type === 'adjustment',
                                            'border-gray-200 bg-white hover:border-amber-300':
                                                entryForm.type !== 'adjustment',
                                        },
                                    ]"
                                >
                                    <svg
                                        class="h-6 w-6 text-amber-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M9 7h6m0 10v-3m-3 3v.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <div class="text-center">
                                        <p class="text-sm font-semibold text-gray-900">Adjustment</p>
                                        <p class="text-xs text-gray-600">Correction</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Form Fields (shown when type is selected) -->
                    <template v-if="entryForm.type">
                        <!-- Tags -->
                        <div class="mb-4 overflow-visible">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Tags</label>
                            <TagInput v-model="entryForm.tags" placeholder="Add tags..." :allow-create="true" />
                        </div>

                        <div class="flex flex-col md:flex-row gap-3 mb-4">
                            <div class="mb-4 w-full flex flex-col justify-around">
                                <label class="mb-1 block text-sm font-medium text-gray-700">Reference Date</label>
                                <input
                                    v-model="entryForm.reference_date"
                                    type="date"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                                />
                            </div>

                            <CTypeahead
                                containerClasses="mb-4 w-full"
                                v-model="entryForm.reference_date_timezone"
                                :initialValue="entryForm.reference_date_timezone || targetTimezone || TZ_DEFAULT"
                                label="Timezone"
                                placeholder="Search timezone..."
                                clearable
                                :initial-options="timezoneList"
                                :refresh-options="refreshTzListOptions"
                                empty-text="No clients found"
                                loading-text="Loading clients..."
                            />
                        </div>

                        <!-- Date and Time -->
                        <div class="flex flex-col md:flex-row gap-3 mb-4">
                            <div class="mb-4 w-full">
                                <label class="mb-2 block text-sm font-medium text-gray-700">Time</label>
                                <div class="flex gap-3">
                                    <div class="flex-1">
                                        <label class="mb-1 block text-xs text-gray-600">Hours</label>
                                        <input
                                            :value="entryFormHours"
                                            @blur="handleEntryFormHoursChange"
                                            @keypress="handleEntryFormHoursChange"
                                            @input="handleEntryFormHoursChange"
                                            type="number"
                                            min="0"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                                            placeholder="0"
                                        />
                                    </div>
                                    <div class="flex-1">
                                        <label class="mb-1 block text-xs text-gray-600">Minutes</label>
                                        <input
                                            :value="entryFormMinutes"
                                            @blur="handleEntryFormMinutesChange"
                                            @keypress="handleEntryFormMinutesChange"
                                            @input="handleEntryFormMinutesChange"
                                            type="number"
                                            min="0"
                                            max="59"
                                            step="5"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                                            placeholder="0"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Title -->
                        <div class="mb-4">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Title</label>
                            <input
                                v-model="entryForm.title"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                            />
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                            <textarea
                                v-model="entryForm.description"
                                rows="2"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"
                            ></textarea>
                        </div>

                        <!-- Error Message -->
                        <div v-if="entryError" class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                            {{ entryError }}
                        </div>
                    </template>
                </div>

                <div class="flex justify-between gap-3 pt-4 border-t border-gray-200">
                    <CButton preset="gray" @click="handleStep2Back">Back</CButton>
                    <div class="flex gap-3">
                        <CButton preset="gray" @click="closeModal">Cancel</CButton>
                        <CButton
                            preset="primary"
                            :disabled="!isStep2Valid || entryLoadingLocal"
                            @click="handleCreateEntry"
                        >
                            {{ entryLoadingLocal ? 'Saving...' : 'Save' }}
                        </CButton>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
