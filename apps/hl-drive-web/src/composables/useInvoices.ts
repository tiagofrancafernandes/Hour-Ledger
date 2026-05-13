import { ref, computed } from 'vue';
import api from '@/services/api';
import type { GenericResponse, Invoice, InvoiceForm, PaginatedResponse } from '@/types';
import { useAuth } from './useAuth';

export function useInvoices() {
    const invoices = ref<Invoice[]>([]);
    const invoice = ref<Invoice | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const pagination = ref({
        currentPage: 1,
        lastPage: 1,
        perPage: 15,
        total: 0,
    });

    const { hasPermission } = useAuth();

    const canViewAny = computed(() => hasPermission('invoice.view_any'));
    const canCreate = computed(() => hasPermission('invoice.create'));
    const canUpdate = computed(() => hasPermission('invoice.update'));
    const canUpdateAny = computed(() => hasPermission('invoice.update_any'));
    const canDelete = computed(() => hasPermission('invoice.delete'));
    const canViewDraft = computed(() => hasPermission('invoice.view_draft'));
    const canHide = computed(() => hasPermission('invoice.hide'));

    function isGenericInvoiceResponse(
        response: Invoice | GenericResponse<Invoice>
    ): response is GenericResponse<Invoice> {
        return typeof response === 'object' && response !== null && 'data' in response;
    }

    function unwrapInvoiceResponse(response: Invoice | GenericResponse<Invoice>): Invoice {
        if (!isGenericInvoiceResponse(response)) {
            return response;
        }

        const responseData = response.data;

        if (typeof responseData === 'object' && responseData !== null && 'data' in responseData) {
            return responseData.data as Invoice;
        }

        return responseData as Invoice;
    }

    async function fetchInvoices(
        filters: {
            client_id?: number;
            status?: string;
            from_date?: string;
            to_date?: string;
            page?: number;
        } = {}
    ) {
        loading.value = true;
        error.value = null;

        try {
            const params = new URLSearchParams();

            if (filters.client_id) {
                params.append('client_id', filters.client_id.toString());
            }

            if (filters.status) {
                params.append('status', filters.status);
            }

            if (filters.from_date) {
                params.append('from_date', filters.from_date);
            }

            if (filters.to_date) {
                params.append('to_date', filters.to_date);
            }

            if (filters.page) {
                params.append('page', filters.page.toString());
            }

            const response = await api.get<PaginatedResponse<Invoice>>(`/invoices?${params.toString()}`);

            invoices.value = response.data;
            pagination.value = {
                currentPage: response.current_page,
                lastPage: response.last_page,
                perPage: response.per_page,
                total: response.total,
            };

            return response;
        } catch (err: unknown) {
            const message = err instanceof Error ? err.message : 'Failed to fetch invoices';
            error.value = message;

            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchInvoice(id: number) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<Invoice>(`/invoices/${id}`);
            invoice.value = response;

            return response;
        } catch (err: unknown) {
            const message = err instanceof Error ? err.message : 'Failed to fetch invoice';
            error.value = message;

            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createInvoice(data: InvoiceForm) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<Invoice>('/invoices', data);
            invoice.value = response;

            return response;
        } catch (err: unknown) {
            const message = err instanceof Error ? err.message : 'Failed to create invoice';
            error.value = message;

            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateInvoice(id: number, data: Partial<InvoiceForm>) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put<Invoice | GenericResponse<Invoice>>(`/invoices/${id}`, data);
            const updatedInvoice = unwrapInvoiceResponse(response);

            invoice.value = updatedInvoice;

            return updatedInvoice;
        } catch (err: unknown) {
            const message = err instanceof Error ? err.message : 'Failed to update invoice';
            error.value = message;

            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteInvoice(id: number) {
        loading.value = true;
        error.value = null;

        try {
            await api.delete(`/invoices/${id}`);

            const index = invoices.value.findIndex((inv) => inv.id === id);

            if (index !== -1) {
                invoices.value.splice(index, 1);
            }

            if (invoice.value?.id === id) {
                invoice.value = null;
            }
        } catch (err: unknown) {
            const message = err instanceof Error ? err.message : 'Failed to delete invoice';
            error.value = message;

            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function downloadMarkdown(id: number) {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<{ markdown: string }>(`/invoices/${id}/download-markdown`);

            return response.markdown;
        } catch (err: unknown) {
            const message = err instanceof Error ? err.message : 'Failed to download markdown';
            error.value = message;

            throw err;
        } finally {
            loading.value = false;
        }
    }

    function canViewInvoice(invoiceData: Invoice): boolean {
        if (canViewAny.value) {
            if (invoiceData.status === 'draft' && !canViewDraft.value) {
                return false;
            }

            return true;
        }

        if (invoiceData.hidden) {
            return false;
        }

        if (invoiceData.status === 'draft') {
            return false;
        }

        return true;
    }

    function canEditInvoice(invoiceData: Invoice): boolean {
        if (canUpdateAny.value) {
            return true;
        }

        if (!canUpdate.value) {
            return false;
        }

        return true;
    }

    function canDeleteInvoice(invoiceData: Invoice): boolean {
        if (canUpdateAny.value) {
            return true;
        }

        if (!canDelete.value) {
            return false;
        }

        return true;
    }

    return {
        invoices,
        invoice,
        loading,
        error,
        pagination,
        canViewAny,
        canCreate,
        canUpdate,
        canUpdateAny,
        canDelete,
        canViewDraft,
        canHide,
        fetchInvoices,
        fetchInvoice,
        createInvoice,
        updateInvoice,
        deleteInvoice,
        downloadMarkdown,
        canViewInvoice,
        canEditInvoice,
        canDeleteInvoice,
    };
}
