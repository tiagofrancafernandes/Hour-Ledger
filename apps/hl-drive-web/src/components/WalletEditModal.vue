<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import type { WalletWithBalance } from '@/types';
import { useWallets } from '@/composables/useWallets';
import { usePermissions } from '@/composables/usePermissions';
import { useToast } from '@/composables/useToast';

const props = defineProps<{
    show: boolean;
    wallet: WalletWithBalance | null;
}>();

const emit = defineEmits<{
    close: [];
    updated: [];
}>();

const { updateWallet, loading: updating } = useWallets();
const toast = useToast();

const form = ref({
    name: '',
    description: '',
    hourly_rate_reference: '',
    currency_code: 'USD',
    credit_purchase_allowed: false,
    internal_note: '',
});

const isSubmitting = computed(() => updating.value);

const currencyCodes = ['USD', 'EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'INR', 'MXN', 'BRL'];

watch(
    () => props.wallet,
    (newWallet) => {
        if (!newWallet) return;

        form.value = {
            name: newWallet.name,
            description: newWallet.description || '',
            hourly_rate_reference: newWallet.hourly_rate_reference ? String(newWallet.hourly_rate_reference) : '',
            currency_code: newWallet.currency_code || 'USD',
            credit_purchase_allowed: newWallet.credit_purchase_allowed ?? false,
            internal_note: newWallet.internal_note || '',
        };
    },
    { immediate: true }
);

const { hasPermission } = usePermissions();

const canViewInternalNote = computed(() => hasPermission('wallet.view_internal_note'));
const canUpdateRules = computed(() => hasPermission('wallet.update_rules'));

const validationErrors = ref<Record<string, string>>({});

const computedErrors = computed(() => {
    const errors: Record<string, string> = {};

    if (!form.value.name.trim()) {
        errors.name = 'Wallet name is required';
    }

    if (form.value.credit_purchase_allowed) {
        if (!form.value.hourly_rate_reference || parseFloat(form.value.hourly_rate_reference) <= 0) {
            errors.hourly_rate_reference = 'Hourly rate is required when credit purchase is enabled';
        }

        if (!form.value.currency_code) {
            errors.currency_code = 'Currency is required when credit purchase is enabled';
        }
    }

    return errors;
});

const isFormValid = computed(() => {
    return Object.keys(computedErrors.value).length === 0;
});

watch(
    computedErrors,
    (errors) => {
        validationErrors.value = errors;
    },
    { immediate: true }
);

async function handleSubmit() {
    if (!props.wallet || !isFormValid.value) {
        if (!isFormValid.value) {
            const firstError = Object.values(validationErrors.value)[0];

            if (firstError) {
                toast.error(firstError);
            }
        }

        return;
    }

    try {
        const payload: Record<string, unknown> = {
            name: form.value.name,
            description: form.value.description || undefined,
            hourly_rate_reference: form.value.hourly_rate_reference
                ? parseFloat(form.value.hourly_rate_reference)
                : undefined,
            currency_code: form.value.currency_code || undefined,
            ...(canViewInternalNote.value ? { internal_note: form.value.internal_note || undefined } : {}),
        };

        if (canUpdateRules.value) {
            payload.credit_purchase_allowed = form.value.credit_purchase_allowed;
        }

        await updateWallet(props.wallet.id, payload);

        toast.success('Wallet updated successfully');
        emit('updated');
        emit('close');
    } catch {
        toast.error('Failed to update wallet');
    }
}

function handleClose() {
    emit('close');
}
</script>

<template>
    <div v-if="show && wallet" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
            <!-- Header -->
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Edit Wallet</h2>
                <button class="text-gray-400 hover:text-gray-600" :disabled="isSubmitting" @click="handleClose">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>
            </div>

            <!-- Form -->
            <form class="space-y-4" @submit.prevent="handleSubmit">
                <!-- Name -->
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Wallet Name *</label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none"
                        :disabled="isSubmitting"
                    />
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="mb-1 block text-sm font-medium text-gray-700">Description</label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="3"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none"
                        :disabled="isSubmitting"
                    ></textarea>
                </div>

                <!-- Internal Note (only for permitted users) -->
                <div v-if="canViewInternalNote">
                    <label for="internal_note" class="mb-1 block text-sm font-medium text-gray-700">
                        Internal Note
                    </label>
                    <textarea
                        id="internal_note"
                        v-model="form.internal_note"
                        rows="3"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-blue-500 focus:outline-none"
                        :disabled="isSubmitting"
                        placeholder="Visible only to authorized users"
                    ></textarea>
                </div>

                <div v-if="canUpdateRules" class="space-y-2">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Allow credit purchases</p>
                            <p class="text-xs text-gray-500">Permit credits to be sold for this wallet.</p>
                        </div>

                        <button
                            type="button"
                            role="switch"
                            :aria-checked="form.credit_purchase_allowed"
                            :class="[
                                'relative inline-flex h-6 w-11 shrink-0 rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500',
                                form.credit_purchase_allowed ? 'bg-blue-600' : 'bg-gray-200',
                            ]"
                            @click="form.credit_purchase_allowed = !form.credit_purchase_allowed"
                            :disabled="isSubmitting"
                        >
                            <span
                                :class="[
                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition duration-200',
                                    form.credit_purchase_allowed ? 'translate-x-5' : 'translate-x-0',
                                ]"
                            />
                        </button>
                    </div>
                </div>

                <!-- Hourly Rate Reference -->
                <div>
                    <label for="hourly_rate" class="mb-1 block text-sm font-medium text-gray-700">
                        Hourly Rate Reference
                        <span v-if="form.credit_purchase_allowed" class="text-red-600">*</span>
                    </label>
                    <input
                        id="hourly_rate"
                        v-model="form.hourly_rate_reference"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        :class="[
                            'w-full rounded-lg border px-3 py-2 focus:outline-none',
                            validationErrors.hourly_rate_reference
                                ? 'border-red-500 focus:border-red-600 bg-red-50'
                                : 'border-gray-300 focus:border-blue-500',
                        ]"
                        :disabled="isSubmitting"
                    />
                    <p v-if="validationErrors.hourly_rate_reference" class="mt-1 text-xs text-red-600">
                        {{ validationErrors.hourly_rate_reference }}
                    </p>
                </div>

                <!-- Currency Code -->
                <div>
                    <label for="currency_code" class="mb-1 block text-sm font-medium text-gray-700">
                        Currency Code
                        <span v-if="form.credit_purchase_allowed" class="text-red-600">*</span>
                    </label>
                    <select
                        id="currency_code"
                        v-model="form.currency_code"
                        :class="[
                            'w-full rounded-lg border px-3 py-2 focus:outline-none',
                            validationErrors.currency_code
                                ? 'border-red-500 focus:border-red-600 bg-red-50'
                                : 'border-gray-300 focus:border-blue-500',
                        ]"
                        :disabled="isSubmitting"
                    >
                        <option value="">Select currency...</option>
                        <option v-for="code in currencyCodes" :key="code" :value="code">
                            {{ code }}
                        </option>
                    </select>
                    <p v-if="validationErrors.currency_code" class="mt-1 text-xs text-red-600">
                        {{ validationErrors.currency_code }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-2 pt-4">
                    <button
                        type="button"
                        class="rounded-lg bg-gray-100 px-4 py-2 text-gray-700 hover:bg-gray-200 disabled:opacity-50"
                        :disabled="isSubmitting"
                        @click="handleClose"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700 disabled:opacity-50"
                        :disabled="isSubmitting || !isFormValid"
                    >
                        {{ isSubmitting ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
