<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { Icon } from '@iconify/vue';
import { useSubscription } from '@/composables/useSubscription';
import { useToast } from '@/composables/useToast';
import UIPageHeader from '@/components/UIPageHeader.vue';
import CButton from '@/components/CButton.vue';
import type { SubscriptionPaymentRecord, TenantSubscriptionData } from '@/types';

const {
    loading,
    error,
    adminSubscriptions,
    adminPayments,
    fetchAdminSubscriptions,
    fetchAdminPayments,
    approvePayment,
    rejectPayment,
    extendGracePeriod,
} = useSubscription();

const toast = useToast();

const currentTab = ref<'payments' | 'subscriptions'>('payments');
const paymentStatusFilter = ref<string>('pending');

// Approval / Rejection modal state
const selectedPayment = ref<SubscriptionPaymentRecord | null>(null);
const showActionModal = ref(false);
const modalAction = ref<'approve' | 'reject'>('approve');
const actionNotes = ref('');
const isSubmittingAction = ref(false);

// Extension modal state
const selectedSubscription = ref<TenantSubscriptionData | null>(null);
const showExtensionModal = ref(false);
const extensionDate = ref('');
const extensionNotes = ref('');
const isSubmittingExtension = ref(false);

onMounted(async () => {
    await loadData();
});

async function loadData(): Promise<void> {
    if (currentTab.value === 'payments') {
        const params = paymentStatusFilter.value ? { status: paymentStatusFilter.value } : {};
        await fetchAdminPayments(params);
        return;
    }

    await fetchAdminSubscriptions();
}

function openApprovalModal(payment: SubscriptionPaymentRecord): void {
    selectedPayment.value = payment;
    modalAction.value = 'approve';
    actionNotes.value = '';
    showActionModal.value = true;
}

function openRejectionModal(payment: SubscriptionPaymentRecord): void {
    selectedPayment.value = payment;
    modalAction.value = 'reject';
    actionNotes.value = '';
    showActionModal.value = true;
}

async function handleActionSubmit(): Promise<void> {
    if (!selectedPayment.value) {
        return;
    }

    if (modalAction.value === 'reject' && !actionNotes.value.trim()) {
        toast.error('Informe a justificativa da rejeição.');
        return;
    }

    isSubmittingAction.value = true;

    try {
        if (modalAction.value === 'approve') {
            await approvePayment(selectedPayment.value.id, actionNotes.value);
            toast.success('Pagamento aprovado e assinatura ativada/renovada com sucesso!');
        } else {
            await rejectPayment(selectedPayment.value.id, actionNotes.value);
            toast.success('Pagamento rejeitado.');
        }

        showActionModal.value = false;
        selectedPayment.value = null;
        actionNotes.value = '';
        await loadData();
    } catch (e: any) {
        toast.error(e?.message || 'Falha ao processar ação');
    } finally {
        isSubmittingAction.value = false;
    }
}

function openExtensionModal(sub: TenantSubscriptionData): void {
    selectedSubscription.value = sub;
    // Default extension: 7 days from now
    const nextWeek = new Date();
    nextWeek.setDate(nextWeek.getDate() + 7);
    extensionDate.value = nextWeek.toISOString().split('T')[0];
    extensionNotes.value = '';
    showExtensionModal.value = true;
}

async function handleExtensionSubmit(): Promise<void> {
    if (!selectedSubscription.value || !extensionDate.value) {
        toast.error('Selecione uma data válida.');
        return;
    }

    isSubmittingExtension.value = true;

    try {
        await extendGracePeriod(selectedSubscription.value.id, extensionDate.value, extensionNotes.value);

        toast.success('Prazo de tolerância prorrogado com sucesso!');
        showExtensionModal.value = false;
        selectedSubscription.value = null;
        await loadData();
    } catch (e: any) {
        toast.error(e?.message || 'Falha ao prorrogar carência');
    } finally {
        isSubmittingExtension.value = false;
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
    });
}
</script>

<template>
    <div class="container mx-auto px-4 py-8">
        <UIPageHeader
            title="Gestão de Assinaturas (Super Admin)"
            description="Modere comprovantes de pagamento de instrutores, aprove/rejeite cobranças e gerencie prazos de carência."
        >
            <template #actions>
                <CButton preset="outlined-black" icon="heroicons:arrow-path" :disabled="loading" @click="loadData">
                    Atualizar
                </CButton>
            </template>
        </UIPageHeader>

        <!-- Navigation Tabs -->
        <div class="mb-6 flex gap-4 border-b border-gray-200 dark:border-gray-700">
            <button
                type="button"
                :class="[
                    'py-3 px-4 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2',
                    currentTab === 'payments'
                        ? 'border-red-600 text-red-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                ]"
                @click="
                    currentTab = 'payments';
                    loadData();
                "
            >
                <Icon icon="heroicons:credit-card" class="w-4 h-4" />
                Pagamentos & Comprovantes
            </button>
            <button
                type="button"
                :class="[
                    'py-3 px-4 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2',
                    currentTab === 'subscriptions'
                        ? 'border-red-600 text-red-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400',
                ]"
                @click="
                    currentTab = 'subscriptions';
                    loadData();
                "
            >
                <Icon icon="heroicons:building-office" class="w-4 h-4" />
                Assinaturas de Instrutores
            </button>
        </div>

        <!-- Error State -->
        <div v-if="error" class="mb-6 rounded-lg bg-red-50 border border-red-200 p-4 text-sm text-red-700">
            {{ error }}
        </div>

        <!-- TAB 1: Payments Moderation -->
        <div v-if="currentTab === 'payments'" class="space-y-4">
            <!-- Filter Bar -->
            <div class="flex items-center gap-3">
                <label class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Filtrar por Status:</label>
                <select
                    v-model="paymentStatusFilter"
                    class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                    @change="loadData"
                >
                    <option value="pending">Pendentes de Revisão</option>
                    <option value="approved">Aprovados</option>
                    <option value="rejected">Rejeitados</option>
                    <option value="">Todos</option>
                </select>
            </div>

            <!-- Payments Table -->
            <div
                class="rounded-xl border border-gray-200 bg-white shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden"
            >
                <div
                    v-if="adminPayments.length === 0"
                    class="text-center py-12 text-sm text-gray-500 dark:text-gray-400"
                >
                    Nenhum pagamento encontrado com o filtro selecionado.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-850">
                            <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="py-3 px-4">Instrutor / Tenant</th>
                                <th class="py-3 px-4">Usuário</th>
                                <th class="py-3 px-4">Valor</th>
                                <th class="py-3 px-4">Método</th>
                                <th class="py-3 px-4">Comprovante</th>
                                <th class="py-3 px-4">Data</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            <tr
                                v-for="item in adminPayments"
                                :key="item.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30"
                            >
                                <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                    {{ item.tenant?.name || item.tenant_id }}
                                </td>
                                <td class="py-3 px-4 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    {{ item.user?.name }} ({{ item.user?.email }})
                                </td>
                                <td class="py-3 px-4 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                                    {{ formatCurrency(item.amount) }}
                                </td>
                                <td
                                    class="py-3 px-4 text-xs uppercase text-gray-600 dark:text-gray-400 whitespace-nowrap"
                                >
                                    {{ item.payment_method }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap text-xs">
                                    <a
                                        v-if="item.pix_receipt_path"
                                        :href="item.pix_receipt_path"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="text-red-600 hover:underline inline-flex items-center gap-1 font-semibold"
                                    >
                                        <Icon icon="heroicons:document-arrow-down" class="w-4 h-4" />
                                        Ver Comprovante
                                    </a>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400 whitespace-nowrap text-xs">
                                    {{ formatDate(item.created_at) }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span
                                        v-if="item.status === 'approved'"
                                        class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800"
                                    >
                                        Aprovado
                                    </span>
                                    <span
                                        v-else-if="item.status === 'rejected'"
                                        class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800"
                                    >
                                        Rejeitado
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800"
                                    >
                                        Pendente
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <div v-if="item.status === 'pending'" class="flex items-center justify-end gap-1.5">
                                        <button
                                            type="button"
                                            class="p-1.5 text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                                            title="Aprovar pagamento"
                                            @click="openApprovalModal(item)"
                                        >
                                            <Icon icon="heroicons:check-circle" class="w-5 h-5" />
                                        </button>
                                        <button
                                            type="button"
                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                            title="Rejeitar pagamento"
                                            @click="openRejectionModal(item)"
                                        >
                                            <Icon icon="heroicons:x-circle" class="w-5 h-5" />
                                        </button>
                                    </div>
                                    <span v-else class="text-xs text-gray-400">Processado</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: All Subscriptions -->
        <div v-if="currentTab === 'subscriptions'" class="space-y-4">
            <div
                class="rounded-xl border border-gray-200 bg-white shadow-sm dark:bg-gray-800 dark:border-gray-700 overflow-hidden"
            >
                <div
                    v-if="adminSubscriptions.length === 0"
                    class="text-center py-12 text-sm text-gray-500 dark:text-gray-400"
                >
                    Nenhuma assinatura encontrada.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-850">
                            <tr class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                <th class="py-3 px-4">Tenant</th>
                                <th class="py-3 px-4">Plano</th>
                                <th class="py-3 px-4">Preço</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4">Fim do Período</th>
                                <th class="py-3 px-4">Tolerância / Limite</th>
                                <th class="py-3 px-4 text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                            <tr
                                v-for="sub in adminSubscriptions"
                                :key="sub.id"
                                class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30"
                            >
                                <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                    {{ sub.tenant?.name || 'N/A' }}
                                </td>
                                <td class="py-3 px-4 text-gray-700 dark:text-gray-300 whitespace-nowrap">
                                    {{ sub.plan?.name || 'HL Drive Pro' }}
                                </td>
                                <td class="py-3 px-4 font-semibold text-gray-900 dark:text-white whitespace-nowrap">
                                    {{ formatCurrency(sub.price) }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <span
                                        v-if="sub.is_read_only"
                                        class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800"
                                    >
                                        Somente Leitura
                                    </span>
                                    <span
                                        v-else-if="sub.is_in_grace_period"
                                        class="px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800"
                                    >
                                        Em Carência
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800"
                                    >
                                        Ativo
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-600 dark:text-gray-400 whitespace-nowrap text-xs">
                                    {{ formatDate(sub.current_period_end) }}
                                </td>
                                <td class="py-3 px-4 whitespace-nowrap text-xs">
                                    <span
                                        :class="sub.is_manually_extended ? 'text-blue-600 font-bold' : 'text-gray-600'"
                                    >
                                        {{ formatDate(sub.effective_deadline) }}
                                        <span v-if="sub.is_manually_extended">(Prorrogado)</span>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <CButton
                                        preset="outlined-black"
                                        icon="heroicons:calendar-days"
                                        class="text-xs"
                                        @click="openExtensionModal(sub)"
                                    >
                                        Prorrogar Carência
                                    </CButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Approval / Rejection Modal -->
        <Teleport to="body">
            <div
                v-if="showActionModal && selectedPayment"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="showActionModal = false"
            >
                <div class="w-full max-w-md rounded-xl bg-white dark:bg-gray-800 shadow-xl overflow-hidden">
                    <div
                        :class="[
                            'px-6 py-4 border-b flex items-center justify-between',
                            modalAction === 'approve'
                                ? 'bg-green-50 border-green-200 text-green-900'
                                : 'bg-red-50 border-red-200 text-red-900',
                        ]"
                    >
                        <h3 class="font-bold text-lg">
                            {{ modalAction === 'approve' ? 'Aprovar Pagamento' : 'Rejeitar Pagamento' }}
                        </h3>
                        <button type="button" @click="showActionModal = false">
                            <Icon icon="heroicons:x-mark" class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="handleActionSubmit" class="p-6 space-y-4">
                        <div class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                            <p>
                                Tenant:
                                <strong>{{ selectedPayment.tenant?.name }}</strong>
                            </p>
                            <p>
                                Valor:
                                <strong>{{ formatCurrency(selectedPayment.amount) }}</strong>
                            </p>
                            <p>
                                Método:
                                <strong>{{ selectedPayment.payment_method }}</strong>
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{
                                    modalAction === 'approve'
                                        ? 'Observações de Aprovação (Opcional)'
                                        : 'Justificativa da Rejeição (Obrigatória)'
                                }}
                            </label>
                            <textarea
                                v-model="actionNotes"
                                rows="3"
                                :placeholder="
                                    modalAction === 'approve'
                                        ? 'Ex: Verificado no extrato bancário...'
                                        : 'Ex: Comprovante ilegível ou valor divergente...'
                                "
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                            />
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <CButton type="button" preset="outlined-black" @click="showActionModal = false">
                                Cancelar
                            </CButton>
                            <CButton
                                type="submit"
                                :preset="modalAction === 'approve' ? 'primary' : 'danger'"
                                :disabled="isSubmittingAction"
                            >
                                {{
                                    isSubmittingAction
                                        ? 'Processando...'
                                        : modalAction === 'approve'
                                          ? 'Confirmar Aprovação'
                                          : 'Confirmar Rejeição'
                                }}
                            </CButton>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- Grace Period Extension Modal -->
        <Teleport to="body">
            <div
                v-if="showExtensionModal && selectedSubscription"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                @click.self="showExtensionModal = false"
            >
                <div class="w-full max-w-md rounded-xl bg-white dark:bg-gray-800 shadow-xl overflow-hidden">
                    <div
                        class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between"
                    >
                        <h3 class="font-bold text-lg text-gray-900 dark:text-white">Prorrogar Prazo de Tolerância</h3>
                        <button type="button" @click="showExtensionModal = false">
                            <Icon icon="heroicons:x-mark" class="w-5 h-5 text-gray-400" />
                        </button>
                    </div>

                    <form @submit.prevent="handleExtensionSubmit" class="p-6 space-y-4">
                        <div class="text-sm text-gray-600 dark:text-gray-300 space-y-1">
                            <p>
                                Tenant:
                                <strong>{{ selectedSubscription.tenant?.name }}</strong>
                            </p>
                            <p>
                                Plano:
                                <strong>{{ selectedSubscription.plan?.name }}</strong>
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Prorrogar até a data:
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="extensionDate"
                                type="date"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                            />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Motivo / Anotação Administrativa (Opcional)
                            </label>
                            <textarea
                                v-model="extensionNotes"
                                rows="2"
                                placeholder="Ex: Alinhado com o instrutor via WhatsApp..."
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500"
                            />
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <CButton type="button" preset="outlined-black" @click="showExtensionModal = false">
                                Cancelar
                            </CButton>
                            <CButton type="submit" preset="primary" :disabled="isSubmittingExtension">
                                {{ isSubmittingExtension ? 'Salvando...' : 'Salvar Prorrogação' }}
                            </CButton>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>
