<template>
    <div class="container mx-auto px-4 py-8">
        <div v-if="loading" class="text-center">
            <p class="text-gray-600 dark:text-gray-400">Loading invoice...</p>
        </div>

        <div v-else-if="error" class="rounded-lg bg-red-50 p-4 text-red-800">
            {{ error }}
        </div>

        <div v-else-if="invoice" class="space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Invoice #{{ invoice.invoice_number }}</h1>
                <div class="flex gap-2">
                    <button
                        v-if="canEditInvoice(invoice)"
                        type="button"
                        class="rounded-lg border border-gray-300 px-4 py-2 hover:bg-gray-50"
                        @click="handleEdit"
                    >
                        Edit
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700"
                        @click="handleDownloadMarkdown"
                    >
                        Download Markdown
                    </button>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Invoice Details</h2>
                        <dl class="space-y-2">
                            <div>
                                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Client</dt>
                                <dd class="text-gray-900 dark:text-white">{{ invoice.client?.name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Issue Date</dt>
                                <dd class="text-gray-900 dark:text-white">{{ formatDate(invoice.issue_date) }}</dd>
                            </div>
                            <div v-if="invoice.due_date">
                                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Due Date</dt>
                                <dd class="text-gray-900 dark:text-white">{{ formatDate(invoice.due_date) }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Status</dt>
                                <dd>
                                    <span
                                        :class="[
                                            'inline-flex rounded-full px-2 py-1 text-xs font-semibold',
                                            {
                                                'bg-gray-100 text-gray-800': invoice.status === 'draft',
                                                'bg-blue-100 text-blue-800': invoice.status === 'sent',
                                                'bg-green-100 text-green-800': invoice.status === 'paid',
                                                'bg-red-100 text-red-800': invoice.status === 'cancelled',
                                            },
                                        ]"
                                    >
                                        {{ invoice.status }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Totals</h2>
                        <dl class="space-y-2">
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Subtotal</dt>
                                <dd class="text-gray-900 dark:text-white">
                                    {{ invoice.currency }} {{ invoice.subtotal }}
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                    Tax ({{ invoice.tax_percentage }}%)
                                </dt>
                                <dd class="text-gray-900 dark:text-white">
                                    {{ invoice.currency }} {{ invoice.tax_amount }}
                                </dd>
                            </div>
                            <div v-if="parseFloat(invoice.discount_amount) > 0" class="flex justify-between">
                                <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Discount</dt>
                                <dd class="text-gray-900 dark:text-white">
                                    -{{ invoice.currency }} {{ invoice.discount_amount }}
                                </dd>
                            </div>
                            <div class="flex justify-between border-t pt-2">
                                <dt class="text-lg font-bold text-gray-900 dark:text-white">Total</dt>
                                <dd class="text-lg font-bold text-gray-900 dark:text-white">
                                    {{ invoice.currency }} {{ invoice.total }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div v-if="invoice.notes" class="mt-6">
                    <h3 class="mb-2 text-sm font-medium text-gray-600 dark:text-gray-400">Notes</h3>
                    <p class="text-gray-900 dark:text-white">{{ invoice.notes }}</p>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow dark:bg-gray-800">
                <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Line Items</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-700 dark:text-gray-300"
                                >
                                    Description
                                </th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-700 dark:text-gray-300"
                                >
                                    Unit
                                </th>
                                <th
                                    class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-700 dark:text-gray-300"
                                >
                                    Quantity
                                </th>
                                <th
                                    class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-700 dark:text-gray-300"
                                >
                                    Unit Price
                                </th>
                                <th
                                    class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-700 dark:text-gray-300"
                                >
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="item in invoice.items" :key="item.id">
                                <td class="px-4 py-3 text-gray-900 dark:text-white">{{ item.description }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ item.unit }}</td>
                                <td class="px-4 py-3 text-right text-gray-900 dark:text-white">{{ item.quantity }}</td>
                                <td class="px-4 py-3 text-right text-gray-900 dark:text-white">
                                    {{ invoice.currency }} {{ item.unit_price }}
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                                    {{ invoice.currency }} {{ item.line_total }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useInvoices } from '@/composables/useInvoices';
import { useToast } from '@/composables/useToast';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { invoice, loading, error, fetchInvoice, downloadMarkdown, canEditInvoice } = useInvoices();

function formatDate(dateString: string): string {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

function handleEdit() {
    router.push({ name: 'invoice-edit', params: { id: route.params.id } });
}

async function handleDownloadMarkdown() {
    try {
        const markdown = await downloadMarkdown(Number(route.params.id));
        const blob = new Blob([markdown], { type: 'text/markdown' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `invoice-${invoice.value?.invoice_number}.md`;
        a.click();
        window.URL.revokeObjectURL(url);
        toast.success('Markdown downloaded successfully');
    } catch (err) {
        toast.error('Failed to download markdown');
    }
}

onMounted(async () => {
    await fetchInvoice(Number(route.params.id));
});
</script>
