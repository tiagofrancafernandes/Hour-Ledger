<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Icon } from '@iconify/vue';
import { useSubscription } from '@/composables/useSubscription';
import { useToast } from '@/composables/useToast';
import UIPageHeader from '@/components/UIPageHeader.vue';
import CButton from '@/components/CButton.vue';

const {
    loading,
    error,
    subscription,
    banner,
    canMutate,
    paymentInstructions,
    payments,
    fetchSummary,
    fetchPayments,
    createPaymentIntent,
    uploadReceipt,
} = useSubscription();

const toast = useToast();

const selectedTab = ref<'online' | 'offline'>('online');
const isGeneratingPix = ref(false);
const generatedPixCode = ref<string | null>(null);
const isUploadingReceipt = ref(false);
const receiptFile = ref<File | null>(null);
const receiptNotes = ref('');
const fileInputRef = ref<HTMLInputElement | null>(null);

onMounted(async () => {
    await fetchSummary();
    await fetchPayments();
});

function handleFileSelect(event: Event): void {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) {
        return;
    }

    const validTypes = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];

    if (!validTypes.includes(file.type)) {
        toast.error('Formato inválido. Envie um arquivo PDF, PNG ou JPG.');
        target.value = '';
        return;
    }

    if (file.size > 5 * 1024 * 1024) {
        toast.error('O arquivo não pode exceder 5MB.');
        target.value = '';
        return;
    }

    receiptFile.value = file;
}

async function handleGeneratePix(): Promise<void> {
    isGeneratingPix.value = true;

    try {
        const response = await createPaymentIntent('pix_online');

        if (response.pix_details?.code) {
            generatedPixCode.value = response.pix_details.code;
            toast.success('Código PIX gerado com sucesso!');
        } else {
            generatedPixCode.value = response.payment.pix_code ?? null;
        }

        await fetchPayments();
    } catch (e: any) {
        toast.error(e?.message || 'Falha ao gerar cobrança PIX');
    } finally {
        isGeneratingPix.value = false;
    }
}

async function copyPixCode(): Promise<void> {
    if (!generatedPixCode.value) {
        return;
    }

    try {
        await navigator.clipboard.writeText(generatedPixCode.value);
        toast.success('Código PIX copiado para a área de transferência!');
    } catch {
        toast.error('Não foi possível copiar o código PIX.');
    }
}

async function handleSubmitReceipt(): Promise<void> {
    if (!receiptFile.value) {
        toast.error('Por favor, selecione o arquivo do comprovante.');
        return;
    }

    isUploadingReceipt.value = true;

    try {
        await uploadReceipt(receiptFile.value, null, receiptNotes.value);
        toast.success('Comprovante enviado com sucesso! Aguarde a aprovação do administrador.');
        receiptFile.value = null;
        receiptNotes.value = '';

        if (fileInputRef.value) {
            fileInputRef.value.value = '';
        }
    } catch (e: any) {
        toast.error(e?.message || 'Falha ao enviar comprovante');
    } finally {
        isUploadingReceipt.value = false;
    }
}

function formatCurrency(value?: number | string | null): string {
    if (value === null || value === undefined) {
        return 'R$ 0,00';
    }

    const num = typeof value === 'string' ? parseFloat(value) : value;

    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(num);
}

function formatDate(dateStr?: string | null): string {
    if (!dateStr) {
        return '—';
    }

    const d = new Date(dateStr);

    return d.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const statusBadge = computed(() => {
    if (!subscription.value) {
        return { label: 'Carregando...', class: 'bg-gray-100 text-gray-700' };
    }

    if (subscription.value.is_read_only) {
        return { label: 'Suspenso (Somente Leitura)', class: 'bg-red-100 text-red-800 border border-red-300' };
    }

    if (subscription.value.is_in_grace_period) {
        return { label: 'Em Carência (Atrasado)', class: 'bg-yellow-100 text-yellow-800 border border-yellow-300' };
    }

    if (subscription.value.status === 'active') {
        return { label: 'Ativo', class: 'bg-green-100 text-green-800 border border-green-300' };
    }

    return { label: subscription.value.status, class: 'bg-gray-100 text-gray-700' };
});
</script>

<template>
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <UIPageHeader
            title="Assinatura & Pagamentos"
            description="Gerencie seu plano de acesso ao sistema, regularize pagamentos pendentes e envie comprovantes."
        />

        <!-- Error State -->
        <div v-if="error" class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
            {{ error }}
        </div>

        <!-- Banner Alert (Grace Period or Read-Only) -->
        <div
            v-if="banner?.show_banner"
            :class="[
                'mb-6 rounded-xl p-5 border flex items-start gap-4 shadow-sm',
                banner.type === 'danger'
                    ? 'bg-red-50 border-red-300 text-red-900'
                    : 'bg-yellow-50 border-yellow-300 text-yellow-900',
            ]"
        >
            <Icon
                :icon="banner.type === 'danger' ? 'heroicons:exclamation-triangle' : 'heroicons:clock'"
                class="w-6 h-6 flex-shrink-0 mt-0.5"
            />
            <div class="flex-1">
                <h4 class="font-bold text-base mb-1">
                    {{ banner.type === 'danger' ? 'Acesso Bloqueado para Novas Modificações' : 'Pagamento Pendente' }}
                </h4>
                <p class="text-sm">
                    {{ banner.message }}
                </p>
                <p v-if="banner.deadline" class="text-xs mt-1.5 opacity-90">
                    Prazo limite informado:
                    <strong>{{ formatDate(banner.deadline) }}</strong>
                </p>
            </div>
        </div>

        <!-- Current Plan Overview Card -->
        <div
            class="mb-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:bg-gray-800 dark:border-gray-700"
        >
            <div
                class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-6 border-b border-gray-200 dark:border-gray-700"
            >
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ subscription?.plan?.name || 'Plano Instrutor HL Drive' }}
                        </h2>
                        <span :class="['px-2.5 py-1 rounded-full text-xs font-semibold', statusBadge.class]">
                            {{ statusBadge.label }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Acesso completo ao ecossistema de gestão de alunos e consumo de horas.
                    </p>
                </div>

                <div class="text-right">
                    <p class="text-3xl font-extrabold text-gray-900 dark:text-white">
                        {{ formatCurrency(subscription?.price) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">por ciclo (30 dias)</p>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-6">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Período Atual</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                        {{ formatDate(subscription?.current_period_start) }} até
                        {{ formatDate(subscription?.current_period_end) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tolerância / Carência</p>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                        {{ formatDate(subscription?.effective_deadline) }}
                    </p>
                </div>

                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status das Operações</p>
                    <p class="text-sm font-semibold mt-1" :class="canMutate ? 'text-green-600' : 'text-red-600'">
                        {{ canMutate ? 'Operações Ativas (Criar/Editar)' : 'Somente Leitura' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Payment Actions Section -->
        <div
            class="mb-8 rounded-xl border border-gray-200 bg-white shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden"
        >
            <!-- Tabs -->
            <div class="flex border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-850">
                <button
                    type="button"
                    :class="[
                        'flex-1 py-3 px-6 text-sm font-semibold text-center border-b-2 transition-colors flex items-center justify-center gap-2',
                        selectedTab === 'online'
                            ? 'border-red-600 text-red-600 bg-white dark:bg-gray-800'
                            : 'border-transparent text-gray-600 hover:text-gray-900 dark:text-gray-400',
                    ]"
                    @click="selectedTab = 'online'"
                >
                    <Icon icon="heroicons:qr-code" class="w-5 h-5" />
                    Pagamento Online (PIX Instantâneo)
                </button>
                <button
                    type="button"
                    :class="[
                        'flex-1 py-3 px-6 text-sm font-semibold text-center border-b-2 transition-colors flex items-center justify-center gap-2',
                        selectedTab === 'offline'
                            ? 'border-red-600 text-red-600 bg-white dark:bg-gray-800'
                            : 'border-transparent text-gray-600 hover:text-gray-900 dark:text-gray-400',
                    ]"
                    @click="selectedTab = 'offline'"
                >
                    <Icon icon="heroicons:document-arrow-up" class="w-5 h-5" />
                    Enviar Comprovante (PIX Offline / Transferência)
                </button>
            </div>

            <!-- Tab 1: PIX Online -->
            <div v-if="selectedTab === 'online'" class="p-6 space-y-6">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Pagamento Instantâneo via PIX</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Gere o código Copia e Cola para pagar pelo aplicativo do seu banco. A liberação ocorre assim que
                        confirmado.
                    </p>
                </div>

                <div v-if="!generatedPixCode" class="pt-2">
                    <CButton
                        preset="primary"
                        icon="heroicons:bolt"
                        :disabled="isGeneratingPix"
                        @click="handleGeneratePix"
                    >
                        {{ isGeneratingPix ? 'Gerando Código...' : 'Gerar Código PIX' }}
                    </CButton>
                </div>

                <div v-else class="space-y-4 pt-2">
                    <div
                        class="p-4 rounded-lg bg-gray-50 border border-gray-200 dark:bg-gray-700/50 dark:border-gray-600"
                    >
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            Código PIX (Copia e Cola)
                        </label>
                        <div class="flex items-center gap-2">
                            <input
                                readonly
                                :value="generatedPixCode"
                                class="flex-1 px-3 py-2 text-xs font-mono bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg select-all"
                            />
                            <CButton preset="outlined-black" icon="heroicons:clipboard-document" @click="copyPixCode">
                                Copiar
                            </CButton>
                        </div>
                    </div>

                    <div v-if="paymentInstructions?.pix" class="text-xs text-gray-500 space-y-1">
                        <p v-if="paymentInstructions.pix.receiver_name">
                            Favorecido:
                            <strong>{{ paymentInstructions.pix.receiver_name }}</strong>
                        </p>
                        <p v-if="paymentInstructions.pix.key">
                            Chave alternativa:
                            <strong>{{ paymentInstructions.pix.key }}</strong>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Offline Proof Upload -->
            <div v-if="selectedTab === 'offline'" class="p-6 space-y-6">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Envio de Comprovante de Pagamento</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Realizou uma transferência direta ou PIX avulso? Suba o comprovante para conferência do nosso
                        time de suporte.
                    </p>
                </div>

                <form @submit.prevent="handleSubmitReceipt" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Arquivo do Comprovante (PDF, PNG ou JPG até 5MB)
                            <span class="text-red-500">*</span>
                        </label>
                        <input
                            ref="fileInputRef"
                            type="file"
                            accept=".pdf,image/png,image/jpeg,image/jpg"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 cursor-pointer"
                            @change="handleFileSelect"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Observações ou Identificação Adicional (Opcional)
                        </label>
                        <textarea
                            v-model="receiptNotes"
                            rows="2"
                            placeholder="Ex: Pago via conta do Banco do Brasil em 25/09..."
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                        />
                    </div>

                    <div class="pt-2">
                        <CButton
                            type="submit"
                            preset="primary"
                            icon="heroicons:arrow-up-tray"
                            :disabled="isUploadingReceipt || !receiptFile"
                        >
                            {{ isUploadingReceipt ? 'Enviando Comprovante...' : 'Enviar Comprovante' }}
                        </CButton>
                    </div>
                </form>
            </div>
        </div>

        <!-- Payment History (Latest First) -->
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:bg-gray-800 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Histórico de Pagamentos</h3>
                <button
                    type="button"
                    class="text-xs font-medium text-red-600 hover:underline inline-flex items-center gap-1"
                    @click="() => fetchPayments()"
                >
                    <Icon icon="heroicons:arrow-path" class="w-3.5 h-3.5" />
                    Atualizar
                </button>
            </div>

            <div v-if="payments.length === 0" class="text-center py-8 text-sm text-gray-500 dark:text-gray-400">
                Nenhum pagamento registrado até o momento.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-left text-sm">
                    <thead>
                        <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Data</th>
                            <th class="py-3 px-4">Valor</th>
                            <th class="py-3 px-4">Método</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Comprovante</th>
                            <th class="py-3 px-4">Anotações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        <tr
                            v-for="item in payments"
                            :key="item.id"
                            class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30"
                        >
                            <td class="py-3 px-4 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                {{ formatDate(item.created_at) }}
                            </td>
                            <td class="py-3 px-4 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                {{ formatCurrency(item.amount) }}
                            </td>
                            <td class="py-3 px-4 text-gray-600 dark:text-gray-400 whitespace-nowrap uppercase text-xs">
                                {{ item.payment_method }}
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span
                                    v-if="item.status === 'approved'"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800"
                                >
                                    <Icon icon="heroicons:check-circle" class="w-3.5 h-3.5" />
                                    Aprovado
                                </span>
                                <span
                                    v-else-if="item.status === 'rejected'"
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800"
                                >
                                    <Icon icon="heroicons:x-circle" class="w-3.5 h-3.5" />
                                    Rejeitado
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800"
                                >
                                    <Icon icon="heroicons:clock" class="w-3.5 h-3.5" />
                                    Pendente
                                </span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap text-xs">
                                <a
                                    v-if="item.pix_receipt_path"
                                    :href="item.pix_receipt_path"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-red-600 hover:underline inline-flex items-center gap-1 font-medium"
                                >
                                    <Icon icon="heroicons:document-arrow-down" class="w-4 h-4" />
                                    Ver Arquivo
                                </a>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                            <td class="py-3 px-4 text-xs text-gray-500 max-w-xs truncate">
                                <span v-if="item.rejection_reason" class="text-red-600 font-medium">
                                    Motivo: {{ item.rejection_reason }}
                                </span>
                                <span v-else-if="item.notes">{{ item.notes }}</span>
                                <span v-else class="text-gray-400">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
