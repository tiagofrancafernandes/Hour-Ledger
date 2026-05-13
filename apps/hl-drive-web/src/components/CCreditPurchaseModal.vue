<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Icon } from '@iconify/vue';
import { decimalToTimeFormat } from '@/utils/timeFormatters';
import type { WalletWithBalance } from '@/types';
import { useCreditPurchases } from '@/composables/useCreditPurchases';
import { useToast } from '@/composables/useToast';

export interface CCreditPurchaseModalProps {
    show: boolean;
    wallet: WalletWithBalance | null;
}

const props = defineProps<CCreditPurchaseModalProps>();

const emit = defineEmits<{
    close: [];
    success: [];
}>();

const { createPurchase, createPayment, uploadReceipt, loading } = useCreditPurchases();
const toast = useToast();

// Step management
const currentStep = ref<1 | 2 | 3>(1);

// Step 1: Package selection
const selectedPackage = ref<string | null>(null);
const customHours = ref<number | null>(null);

// Step 3: Payment method
const selectedPaymentMethod = ref<'pix_offline' | 'bank_transfer' | null>(null);
const uploadReceiptNow = ref(false);
const receiptFile = ref<File | null>(null);

// Purchase tracking
const createdPurchaseId = ref<number | null>(null);
const createdPaymentId = ref<number | null>(null);

// Computed values
const totalHours = computed(() => {
    if (selectedPackage.value === 'custom') {
        return customHours.value || 0;
    }

    const packageMap: Record<string, number> = {
        '5h': 5,
        '10h': 10,
        '15h': 15,
    };

    return packageMap[selectedPackage.value || ''] || 0;
});

const formattedTotalHours = computed(() => {
    return decimalToTimeFormat(totalHours.value);
});

const discountPercentage = computed(() => {
    if (selectedPackage.value === '5h') {
        return 0.1;
    }

    if (selectedPackage.value === '10h') {
        return 0.15;
    }

    if (selectedPackage.value === '15h') {
        return 0.2;
    }

    if (selectedPackage.value === 'custom' && customHours.value && customHours.value > 15) {
        return 0.25;
    }

    return 0;
});

const hourlyRate = computed(() => {
    return props.wallet?.hourly_rate_reference ? parseFloat(String(props.wallet.hourly_rate_reference)) : 0;
});

const basePrice = computed(() => {
    return totalHours.value * hourlyRate.value;
});

const discountAmount = computed(() => {
    return basePrice.value * discountPercentage.value;
});

const totalPrice = computed(() => {
    return basePrice.value - discountAmount.value;
});

const isStep1Valid = computed(() => {
    if (selectedPackage.value === 'custom') {
        return customHours.value !== null && customHours.value > 0;
    }

    return selectedPackage.value !== null;
});

// Step 3 is always valid — payment method and receipt are both optional
const isStep3Valid = computed(() => {
    return true;
});

const isSubmitting = computed(() => loading.value);

const showReceiptUpload = computed(() => {
    return selectedPaymentMethod.value !== null && uploadReceiptNow.value;
});

const expirationLabel = computed(() => {
    if (selectedPaymentMethod.value === 'pix_offline') {
        return 'Expires in 48 hours';
    }

    if (selectedPaymentMethod.value === 'bank_transfer') {
        return 'Expires in 7 days';
    }

    return null;
});

// Watchers
watch(
    () => props.show,
    (newShow) => {
        if (!newShow) {
            resetForm();
        }
    }
);

watch(
    () => selectedPaymentMethod.value,
    () => {
        uploadReceiptNow.value = false;
        receiptFile.value = null;
    }
);

watch(
    () => props.wallet,
    (newWallet) => {
        if (!newWallet || !props.show) {
            return;
        }

        if (!newWallet.credit_purchase_allowed) {
            toast.error('Credit purchases are not allowed for this wallet');
            emit('close');
        }
    }
);

// Methods
function resetForm(): void {
    currentStep.value = 1;
    selectedPackage.value = null;
    customHours.value = null;
    selectedPaymentMethod.value = null;
    uploadReceiptNow.value = false;
    receiptFile.value = null;
    createdPurchaseId.value = null;
    createdPaymentId.value = null;
}

function goToStep(step: 1 | 2 | 3): void {
    if (step < currentStep.value) {
        currentStep.value = step;
        return;
    }

    if (step === 2 && !isStep1Valid.value) {
        toast.error('Please select a valid package');
        return;
    }

    currentStep.value = step;
}

async function handleSubmit(): Promise<void> {
    if (!props.wallet) {
        toast.error('Wallet not selected');
        return;
    }

    if (!isStep1Valid.value) {
        toast.error('Please select a valid package');
        return;
    }

    try {
        // Create purchase
        const purchase = await createPurchase(props.wallet.id, totalHours.value, totalPrice.value);

        if (!purchase) {
            toast.error('Failed to create purchase');
            return;
        }

        createdPurchaseId.value = purchase.id;

        // Create payment (method is optional)
        const payment = await createPayment(purchase.id, selectedPaymentMethod.value);

        if (!payment) {
            toast.error('Failed to create payment');
            return;
        }

        createdPaymentId.value = payment.id;

        // Upload receipt if checkbox is checked and file is selected
        if (uploadReceiptNow.value && receiptFile.value) {
            await uploadReceipt(purchase.id, payment.id, receiptFile.value);
        }

        toast.success('Credit purchase created successfully!');
        emit('success');
        emit('close');
    } catch (error) {
        toast.error(error instanceof Error ? error.message : 'Failed to process purchase');
    }
}

function handleClose(): void {
    if (!isSubmitting.value) {
        emit('close');
    }
}

function handleFileSelect(event: Event): void {
    const input = event.target as HTMLInputElement;
    const files = input.files;

    if (!files || files.length === 0) {
        return;
    }

    const file = files[0];

    if (!file) {
        toast.error('No file selected');
        return;
    }

    const validTypes = ['application/pdf', 'image/png', 'image/jpeg'];

    if (!validTypes.includes(file.type)) {
        toast.error('Invalid file type. Please upload PDF, PNG, or JPG');
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        toast.error('File is too large. Maximum size is 5MB');
        return;
    }

    receiptFile.value = file;
}

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: props.wallet?.currency_code || 'USD',
    }).format(value);
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            @click.self="handleClose"
        >
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl transform transition-all" @click.stop>
                <!-- Header -->
                <div
                    class="bg-linear-to-r from-green-600 to-green-700 px-6 py-4 flex items-center justify-between rounded-t-lg"
                >
                    <div>
                        <h2 class="text-xl font-bold text-white">Buy Credits</h2>
                        <p class="text-green-100 text-sm mt-1">Step {{ currentStep }} of 3</p>
                    </div>

                    <button
                        @click="handleClose"
                        :disabled="isSubmitting"
                        class="text-white hover:text-green-100 disabled:opacity-50"
                    >
                        <Icon icon="mdi:close" class="w-6 h-6" />
                    </button>
                </div>

                <!-- Steps Indicator -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div v-for="step in [1, 2, 3]" :key="step" class="flex items-center flex-1">
                            <button
                                @click="goToStep(step as 1 | 2 | 3)"
                                :disabled="isSubmitting"
                                :class="[
                                    'flex items-center justify-center w-10 h-10 rounded-full font-semibold text-sm',
                                    'disabled:opacity-50 disabled:cursor-not-allowed',
                                    {
                                        'bg-green-600 text-white': step === currentStep,
                                        'bg-green-100 text-green-600': step < currentStep,
                                        'bg-gray-200 text-gray-400': step > currentStep,
                                    },
                                ]"
                            >
                                <span v-if="step < currentStep">
                                    <Icon icon="mdi:check" class="w-5 h-5" />
                                </span>
                                <span v-else>{{ step }}</span>
                            </button>

                            <div
                                v-if="step < 3"
                                :class="[
                                    'flex-1 h-1 mx-2',
                                    {
                                        'bg-green-600': step < currentStep,
                                        'bg-gray-200': step >= currentStep,
                                    },
                                ]"
                            />
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-4 py-4 max-h-[60vh] overflow-y-auto">
                    <!-- Step 1: Package Selection -->
                    <div v-if="currentStep === 1" class="space-y-4">
                        <div class="space-y-3">
                            <!-- Preset Packages -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <button
                                    v-for="pkg in ['5h', '10h', '15h']"
                                    :key="pkg"
                                    @click="
                                        selectedPackage = pkg;
                                        customHours = null;
                                    "
                                    :class="[
                                        'p-4 rounded-lg border-2 transition-all',
                                        {
                                            'border-green-600 bg-green-50': selectedPackage === pkg,
                                            'border-gray-200 bg-white hover:border-gray-300': selectedPackage !== pkg,
                                        },
                                    ]"
                                >
                                    <div class="font-semibold text-gray-900">{{ pkg }}</div>
                                    <div class="text-sm text-gray-600 mt-2">
                                        {{ pkg === '5h' ? '10% discount' : '' }}
                                        {{ pkg === '10h' ? '15% discount' : '' }}
                                        {{ pkg === '15h' ? '20% discount' : '' }}
                                    </div>
                                    <div class="text-lg font-bold text-green-600 mt-3">
                                        {{
                                            formatCurrency(
                                                parseInt(pkg) *
                                                    hourlyRate *
                                                    (pkg === '5h' ? 0.9 : pkg === '10h' ? 0.85 : 0.8)
                                            )
                                        }}
                                    </div>
                                </button>
                            </div>

                            <!-- Custom Hours -->
                            <div class="border-t border-gray-200 pt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Or enter custom hours
                                </label>

                                <div class="flex gap-2 justify-between items-center">
                                    <CInput
                                        v-model.number="customHours"
                                        type="number"
                                        placeholder="Enter hours"
                                        min="1"
                                        container-classes=""
                                        @focus="selectedPackage = 'custom'"
                                    />

                                    <div class="flex w-36 items-center px-0 py-1">
                                        <span
                                            class="w-full min-h-10 text-center pt-2 bg-gray-100 rounded-lg border border-gray-300 text-sm text-gray-600"
                                        >
                                            {{ customHours && customHours > 15 ? '25% discount' : 'No discount' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Summary Preview -->
                        <div v-if="isStep1Valid" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="text-sm text-gray-600">
                                <div class="flex justify-between mb-2">
                                    <span>Total Hours:</span>
                                    <span class="font-semibold text-gray-900">{{ formattedTotalHours }}</span>
                                </div>

                                <div class="flex justify-between mb-2">
                                    <span>Base Price:</span>
                                    <span class="font-semibold text-gray-900">{{ formatCurrency(basePrice) }}</span>
                                </div>

                                <div v-if="discountPercentage > 0" class="flex justify-between mb-2 text-green-600">
                                    <span>Discount ({{ Math.round(discountPercentage * 100) }}%):</span>
                                    <span class="font-semibold">-{{ formatCurrency(discountAmount) }}</span>
                                </div>

                                <div
                                    class="border-t border-blue-200 pt-2 mt-2 flex justify-between text-green-600 font-bold"
                                >
                                    <span>Total:</span>
                                    <span>{{ formatCurrency(totalPrice) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Review -->
                    <div v-if="currentStep === 2" class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Review Your Purchase</h3>

                        <div class="space-y-3 bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                <span class="text-gray-600">Wallet:</span>
                                <span class="font-semibold text-gray-900">{{ wallet?.name }}</span>
                            </div>

                            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                <span class="text-gray-600">Hours:</span>
                                <span class="font-semibold text-gray-900">{{ formattedTotalHours }}</span>
                            </div>

                            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                <span class="text-gray-600">Hourly Rate:</span>
                                <span class="font-semibold text-gray-900">{{ formatCurrency(hourlyRate) }}</span>
                            </div>

                            <div class="flex justify-between items-center pb-3 border-b border-gray-200">
                                <span class="text-gray-600">Base Price:</span>
                                <span class="font-semibold text-gray-900">{{ formatCurrency(basePrice) }}</span>
                            </div>

                            <div
                                v-if="discountPercentage > 0"
                                class="flex justify-between items-center pb-3 border-b border-gray-200 text-green-600"
                            >
                                <span>Discount ({{ Math.round(discountPercentage * 100) }}%):</span>
                                <span class="font-semibold">-{{ formatCurrency(discountAmount) }}</span>
                            </div>

                            <div class="flex justify-between items-center pt-2 text-lg font-bold text-green-600">
                                <span>Total to Pay:</span>
                                <span>{{ formatCurrency(totalPrice) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Payment Method -->
                    <div v-if="currentStep === 3" class="space-y-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-1">Payment Method</h3>
                            <p class="text-sm text-gray-500 mb-4">
                                You can select a payment method now or choose it later from your payment history.
                            </p>
                        </div>

                        <div class="space-y-3">
                            <!-- No Method Option -->
                            <button
                                @click="selectedPaymentMethod = null"
                                :class="[
                                    'w-full p-4 rounded-lg border-2 transition-all text-left',
                                    {
                                        'border-gray-500 bg-gray-50': selectedPaymentMethod === null,
                                        'border-gray-200 bg-white hover:border-gray-300':
                                            selectedPaymentMethod !== null,
                                    },
                                ]"
                            >
                                <div class="flex items-start">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-900">Choose later</div>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Select payment method from your payment history.
                                        </p>
                                    </div>

                                    <Icon
                                        v-if="selectedPaymentMethod === null"
                                        icon="mdi:check-circle"
                                        class="w-5 h-5 text-gray-600 shrink-0 ml-2"
                                    />
                                </div>
                            </button>

                            <!-- Bank Transfer -->
                            <button
                                @click="selectedPaymentMethod = 'bank_transfer'"
                                :class="[
                                    'w-full p-4 rounded-lg border-2 transition-all text-left',
                                    {
                                        'border-green-600 bg-green-50': selectedPaymentMethod === 'bank_transfer',
                                        'border-gray-200 bg-white hover:border-gray-300':
                                            selectedPaymentMethod !== 'bank_transfer',
                                    },
                                ]"
                            >
                                <div class="flex items-start">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-900">Bank Transfer</div>
                                        <p class="text-sm text-gray-600 mt-1">
                                            Transfer via bank. Awaiting admin approval.
                                        </p>
                                        <p class="text-xs text-amber-600 mt-1">Expires in 7 days</p>
                                    </div>

                                    <Icon
                                        v-if="selectedPaymentMethod === 'bank_transfer'"
                                        icon="mdi:check-circle"
                                        class="w-5 h-5 text-green-600 shrink-0 ml-2"
                                    />
                                </div>
                            </button>

                            <!-- PIX Offline -->
                            <button
                                @click="selectedPaymentMethod = 'pix_offline'"
                                :class="[
                                    'w-full p-4 rounded-lg border-2 transition-all text-left',
                                    {
                                        'border-green-600 bg-green-50': selectedPaymentMethod === 'pix_offline',
                                        'border-gray-200 bg-white hover:border-gray-300':
                                            selectedPaymentMethod !== 'pix_offline',
                                    },
                                ]"
                            >
                                <div class="flex items-start">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-900">PIX Offline</div>
                                        <p class="text-sm text-gray-600 mt-1">
                                            Send PIX and attach receipt. Admin approval required.
                                        </p>
                                        <p class="text-xs text-amber-600 mt-1">Expires in 48 hours</p>
                                    </div>

                                    <Icon
                                        v-if="selectedPaymentMethod === 'pix_offline'"
                                        icon="mdi:check-circle"
                                        class="w-5 h-5 text-green-600 shrink-0 ml-2"
                                    />
                                </div>
                            </button>

                            <!-- Upload Receipt Now checkbox -->
                            <div v-if="selectedPaymentMethod !== null" class="border border-gray-200 rounded-lg p-4">
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        v-model="uploadReceiptNow"
                                        class="w-4 h-4 text-green-600 rounded border-gray-300 focus:ring-green-500"
                                    />
                                    <span class="text-sm font-medium text-gray-700">Upload receipt now</span>
                                </label>
                                <p class="text-xs text-gray-500 mt-1 ml-7">
                                    You can also upload it later from your payment history.
                                </p>

                                <!-- Receipt Upload Area -->
                                <div v-if="uploadReceiptNow" class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-3">
                                        Payment Receipt (PDF, PNG, or JPG)
                                    </label>

                                    <div
                                        class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-green-300 hover:bg-green-50 transition-all"
                                    >
                                        <input
                                            type="file"
                                            accept=".pdf,.png,.jpg,.jpeg"
                                            @change="handleFileSelect"
                                            class="hidden"
                                            ref="fileInput"
                                        />

                                        <button
                                            type="button"
                                            @click="($refs.fileInput as HTMLInputElement)?.click()"
                                            class="w-full"
                                        >
                                            <Icon icon="mdi:cloud-upload" class="w-8 h-8 text-gray-400 mx-auto mb-2" />

                                            <div v-if="!receiptFile" class="text-gray-600">
                                                <p class="font-medium">Click to upload or drag and drop</p>
                                                <p class="text-xs text-gray-500 mt-1">PDF, PNG or JPG (max 5MB)</p>
                                            </div>

                                            <div v-else class="text-green-600">
                                                <Icon icon="mdi:check-circle" class="w-6 h-6 mx-auto mb-1" />
                                                <p class="font-medium">{{ receiptFile.name }}</p>
                                                <p class="text-xs text-green-500 mt-1">File selected</p>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 flex gap-3 justify-between rounded-b-lg border-t border-gray-200">
                    <CButton
                        preset="outlined-black"
                        @click="goToStep((currentStep - 1) as 1 | 2 | 3)"
                        :disabled="currentStep === 1 || isSubmitting"
                    >
                        Previous
                    </CButton>

                    <div class="flex gap-2">
                        <CButton preset="outlined-black" @click="handleClose" :disabled="isSubmitting">Cancel</CButton>

                        <CButton
                            v-if="currentStep < 3"
                            preset="primary"
                            @click="goToStep((currentStep + 1) as 1 | 2 | 3)"
                            :disabled="(currentStep === 1 && !isStep1Valid) || isSubmitting"
                        >
                            Next
                        </CButton>

                        <CButton
                            v-else
                            preset="primary"
                            @click="handleSubmit"
                            :disabled="isSubmitting"
                            :loading="isSubmitting"
                        >
                            Confirm Purchase
                        </CButton>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>
