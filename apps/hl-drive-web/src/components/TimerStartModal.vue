<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { useTimerStore } from '@/stores/timer';
import { api } from '@/services/api';
import { useClients } from '@/composables/useClients';
import { useWallets } from '@/composables/useWallets';
import type { WalletWithBalance, Tag, TypeaheadOption } from '@/types';
import TagInput from '@/components/TagInput.vue';
import { allFilled, fieldIsFilled, getRequiredEmptyInputs, makeRequiredInputs } from '@/utils/data-helpers';

const props = defineProps<{
    show: boolean;
}>();

const emit = defineEmits<{
    close: [];
    started: [];
}>();

const timerStore = useTimerStore();
const { clients, fetchClients } = useClients();
const { wallets: walletsComposable, fetchWallets } = useWallets();

const wallets = ref<WalletWithBalance[]>([]);
const tags = ref<Tag[]>([]);
const loading = ref(false);
const loadingWallets = ref(false);

const selectedClientId = ref<number | null>(null);

const requiredInputs = {
    title: true,
    wallet_id: true,
    description: false,
};

const form = ref({
    wallet_id: null as number | null,
    title: '',
    description: '',
    tags: [] as number[],
});

// allRequiredFieldsIsFilled

const requiredEmptyInputs = computed(() => getRequiredEmptyInputs(form.value, requiredInputs));
const allRequiredIsFilled = computed(() => (requiredEmptyInputs.value || []).length <= 0);

const selectedWallet = computed(() => {
    if (!form.value.wallet_id) {
        return null;
    }

    const wallet = wallets.value.find((w) => w.id === form.value.wallet_id);

    return wallet || null;
});

const canSubmit = computed(() => {
    if (!allRequiredIsFilled.value) {
        return false;
    }

    return form.value.wallet_id !== null && !loading.value;
});

const clientOptions = computed<TypeaheadOption[]>(() => {
    return clients.value.map((client) => ({
        value: client.id,
        label: `${client.name}${client.notes ? ` - ${client.notes}` : ''}`,
    }));
});

const filteredWallets = computed(() => {
    if (!selectedClientId.value) {
        return wallets.value;
    }

    return wallets.value.filter((wallet) => wallet.client_id === selectedClientId.value);
});

const walletOptions = computed<TypeaheadOption[]>(() => {
    return filteredWallets.value.map((w) => ({
        value: w.id,
        label: w.name,
    }));
});

async function refreshClientOptions({ searchTerm }: { searchTerm: string }): Promise<TypeaheadOption[]> {
    await fetchClients(1, searchTerm || '');

    return clientOptions.value;
}

async function refreshWalletOptions({ searchTerm }: { searchTerm: string }): Promise<TypeaheadOption[]> {
    await fetchWallets(selectedClientId.value || undefined, 1);

    return walletOptions.value;
}

async function loadWallets(): Promise<void> {
    loadingWallets.value = true;

    try {
        const response = await api.get<WalletWithBalance[]>('/wallets?with=tags,balance');

        let _data = ('data' in response ? response?.data : response) as WalletWithBalance[];

        wallets.value = Array.isArray(_data) ? _data : [];
    } catch (error) {
        console.error('Failed to load wallets:', error);
    } finally {
        loadingWallets.value = false;
    }
}

async function loadTags(): Promise<void> {
    try {
        const response = await api.get<Tag[]>('/tags');

        tags.value = response;
    } catch (error) {
        console.error('Failed to load tags:', error);
    }
}

async function handleSubmit(): Promise<void> {
    if (!canSubmit.value) {
        return;
    }

    loading.value = true;

    try {
        const success = await timerStore.startTimer({
            wallet_id: form.value.wallet_id!,
            title: form.value.title || undefined,
            description: form.value.description || undefined,
            tags: form.value.tags.length > 0 ? form.value.tags : undefined,
        });

        if (success) {
            resetForm();
            emit('started');
            emit('close');
        }
    } finally {
        loading.value = false;
    }
}

function resetForm(): void {
    form.value = {
        wallet_id: null,
        title: '',
        description: '',
        tags: [],
    };
}

function handleClose(): void {
    if (!loading.value) {
        resetForm();
        emit('close');
    }
}

onMounted(async () => {
    loadWallets();
    loadTags();
    await Promise.all([fetchClients(1, ''), fetchWallets()]);
});

watch(selectedClientId, () => {
    form.value.wallet_id = null;
    void refreshWalletOptions({ searchTerm: '' });
});
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="handleClose">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 max-h-screen overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Start Timer</h2>
                <button
                    class="text-gray-400 hover:text-gray-600 transition-colors"
                    :disabled="loading"
                    @click="handleClose"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <form class="p-6 space-y-4" @submit.prevent="handleSubmit">
                <!-- Client Selection -->
                <CTypeahead
                    v-model="selectedClientId"
                    label="Client"
                    placeholder="Search by name or notes"
                    clearable
                    :initial-options="clientOptions"
                    :refresh-options="refreshClientOptions"
                    empty-text="No clients found"
                    loading-text="Loading clients..."
                />

                <!-- Wallet Selection -->
                <CTypeahead
                    v-model="form.wallet_id"
                    label="Wallet"
                    placeholder="Choose wallet"
                    clearable
                    :initial-options="walletOptions"
                    :refresh-options="refreshWalletOptions"
                    empty-text="No wallets found"
                    loading-text="Loading wallets..."
                    required
                />

                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">
                        Title
                        <span v-if="'title' in requiredInputs" class="text-red-500 font-bold italic">*</span>
                    </label>

                    <input
                        id="title"
                        v-model="form.title"
                        type="text"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-transparent"
                        placeholder="What are you working on?"
                        :disabled="loading"
                        :required="requiredInputs?.title ?? false"
                    />
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600 focus:border-transparent resize-none"
                        placeholder="Add more details..."
                        :disabled="loading"
                    ></textarea>
                </div>

                <!-- Tags -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                    <TagInput v-model="form.tags" :available-tags="tags" />
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-4">
                    <button
                        type="button"
                        class="flex-1 px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg font-medium transition-colors"
                        :disabled="loading"
                        @click="handleClose"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        :class="[
                            'flex-1 px-4 py-2 rounded-lg font-medium transition-colors',
                            {
                                'bg-red-600 hover:bg-red-700 text-white': canSubmit,
                                'bg-gray-300 text-gray-500 cursor-not-allowed': !canSubmit,
                            },
                        ]"
                        :disabled="!canSubmit"
                    >
                        {{ loading ? 'Starting...' : 'Start Timer' }}
                    </button>
                </div>
            </form>

            <!-- Error Message -->
            <div v-if="timerStore.error" class="mx-6 mb-6 p-3 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-600">{{ timerStore.error }}</p>
            </div>
        </div>
    </div>
</template>
