<template>
    <div class="container mx-auto px-4 py-8">
        <h1 class="mb-6 text-3xl font-bold text-gray-900 dark:text-white">
            {{ isEditing ? 'Edit Invoice' : 'New Invoice' }}
        </h1>

        <form class="space-y-6 rounded-lg bg-white p-6 shadow dark:bg-gray-800" @submit.prevent="handleSubmit">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <CInput v-model="form.client_id" label="Client" type="number" required :disabled="isEditing" />

                <CInput v-model="form.issue_date" label="Issue Date" type="date" required />

                <CInput v-model="form.due_date" label="Due Date" type="date" />

                <CInput v-model="form.currency" label="Currency" maxlength="3" placeholder="USD" required />

                <CInput
                    v-model.number="form.tax_percentage"
                    label="Tax Percentage (%)"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                />

                <CInput
                    v-model.number="form.discount_amount"
                    label="Discount Amount"
                    type="number"
                    step="0.01"
                    min="0"
                />

                <CSelect v-model="form.status" label="Status">
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="paid">Paid</option>
                    <option value="cancelled">Cancelled</option>
                </CSelect>

                <div v-if="canHide" class="flex items-center gap-2">
                    <input id="hidden" v-model="form.hidden" type="checkbox" class="h-4 w-4 rounded border-gray-300" />
                    <label for="hidden" class="text-sm text-gray-700 dark:text-gray-300">Hidden from customer</label>
                </div>
            </div>

            <CTextarea v-model="form.notes" label="Notes" rows="3" />

            <div class="border-t pt-6">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Line Items</h2>
                    <button
                        type="button"
                        class="rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700"
                        @click="addItem"
                    >
                        Add Item
                    </button>
                </div>

                <div v-for="(item, index) in form.items" :key="index" class="mb-4 rounded-lg border p-4">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-medium text-gray-900 dark:text-white">Item {{ index + 1 }}</h3>
                        <button
                            v-if="form.items.length > 1"
                            type="button"
                            class="text-red-600 hover:text-red-800"
                            @click="removeItem(index)"
                        >
                            Remove
                        </button>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <div class="lg:col-span-2">
                            <CInput v-model="item.description" label="Description" required />
                        </div>

                        <CInput v-model="item.unit" label="Unit" placeholder="unit" />

                        <CInput
                            v-model.number="item.quantity"
                            label="Quantity"
                            type="number"
                            step="0.01"
                            min="0.01"
                            required
                        />

                        <CInput
                            v-model.number="item.unit_price"
                            label="Unit Price"
                            type="number"
                            step="0.01"
                            min="0"
                            required
                        />
                    </div>
                </div>
            </div>

            <div class="flex gap-4 border-t pt-6">
                <CButton type="submit" :disabled="loading">
                    {{ isEditing ? 'Update Invoice' : 'Create Invoice' }}
                </CButton>
                <CButton preset="outlined-black" type="button" @click="handleCancel">Cancel</CButton>
            </div>
        </form>
    </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useInvoices } from '@/composables/useInvoices';
import { useToast } from '@/composables/useToast';
import type { InvoiceForm, InvoiceItemForm } from '@/types';

const route = useRoute();
const router = useRouter();
const toast = useToast();
const { invoice, loading, fetchInvoice, createInvoice, updateInvoice, canHide } = useInvoices();

const isEditing = computed(() => !!route.params.id);

const form = ref<InvoiceForm>({
    client_id: 0,
    issue_date: new Date().toISOString().split('T')[0] as string,
    due_date: null,
    currency: 'USD',
    tax_percentage: 0,
    discount_amount: 0,
    status: 'draft',
    hidden: false,
    notes: null,
    items: [
        {
            description: '',
            unit: 'unit',
            quantity: 1,
            unit_price: 0,
        },
    ],
});

function addItem() {
    form.value.items.push({
        description: '',
        unit: 'unit',
        quantity: 1,
        unit_price: 0,
    });
}

function removeItem(index: number) {
    form.value.items.splice(index, 1);
}

async function handleSubmit() {
    try {
        if (isEditing.value) {
            await updateInvoice(Number(route.params.id), form.value);
            toast.success('Invoice updated successfully');
        } else {
            await createInvoice(form.value);
            toast.success('Invoice created successfully');
        }

        router.push({ name: 'invoices' });
    } catch (err) {
        toast.error('Failed to save invoice');
    }
}

function handleCancel() {
    router.push({ name: 'invoices' });
}

onMounted(async () => {
    if (isEditing.value) {
        await fetchInvoice(Number(route.params.id));

        if (invoice.value) {
            form.value = {
                client_id: invoice.value.client_id,
                issue_date: invoice.value.issue_date,
                due_date: invoice.value.due_date,
                currency: invoice.value.currency,
                tax_percentage: parseFloat(invoice.value.tax_percentage),
                discount_amount: parseFloat(invoice.value.discount_amount),
                status: invoice.value.status,
                hidden: invoice.value.hidden,
                notes: invoice.value.notes,
                items:
                    invoice.value.items?.map((item) => ({
                        id: item.id,
                        description: item.description,
                        unit: item.unit,
                        quantity: parseFloat(item.quantity),
                        unit_price: parseFloat(item.unit_price),
                        product_service_id: item.product_service_id,
                    })) || [],
            };
        }
    }
});
</script>
