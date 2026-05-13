import { ref, type Ref } from 'vue';
import api from '@/services/api';
import type { ImportPlan, ImportPlanRow, ImportPlanForm, ImportRowForm, PaginatedResponse } from '@/types';

export function useImport() {
    const importPlans: Ref<ImportPlan[]> = ref([]);
    const currentPlan: Ref<ImportPlan | null> = ref(null);
    const loading: Ref<boolean> = ref(false);
    const error: Ref<string | null> = ref(null);
    const totalPages: Ref<number> = ref(1);
    const currentPage: Ref<number> = ref(1);

    async function fetchImportPlans(page = 1, filters?: { status?: string; wallet_id?: number }): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            const params: Record<string, string | number> = {
                page: page,
            };

            if (filters?.status) {
                params.status = filters.status;
            }

            if (filters?.wallet_id) {
                params.wallet_id = filters.wallet_id;
            }

            const response: any = await api.get('/import-plans', { params });
            const body = response?.data ?? response;

            importPlans.value = body?.data || body || [];
            totalPages.value = body?.last_page || 1;
            currentPage.value = body?.current_page || 1;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to fetch import plans';
            importPlans.value = [];
        } finally {
            loading.value = false;
        }
    }

    async function fetchImportPlan(id: number): Promise<ImportPlan> {
        loading.value = true;
        error.value = null;

        try {
            const response: any = await api.get(`/import-plans/${id}`);
            const body = response?.data ?? response;

            currentPlan.value = body;

            return body;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to fetch import plan';

            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function uploadFile(data: ImportPlanForm): Promise<ImportPlan> {
        loading.value = true;
        error.value = null;

        try {
            const formData = new FormData();

            formData.append('wallet_id', (data?.wallet_id || '').toString());
            formData.append('file', data.file);

            console.log('formData', formData);

            const response: Response = await api.post<Response>(
                '/import-plans',
                formData,
                {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                },
                /* returnResponse */ true
            );

            if (!response.ok) {
                let _data: any = await response.json();
                let errorMessage =
                    _data?.error ||
                    (_data?.errors && _data?.errors[0]) ||
                    null ||
                    _data?.message ||
                    'Failed to upload file';
                throw new Error(errorMessage);
            }

            let _data: any = await response.json();

            let importPlan: ImportPlan = 'data' in _data ? _data['data'] : _data;

            currentPlan.value = importPlan;

            return importPlan;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to upload file';

            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function confirmImportPlan(planId: number): Promise<ImportPlan> {
        loading.value = true;
        error.value = null;

        try {
            const response: any = await api.post(`/import-plans/${planId}/confirm`);
            const body = response?.data ?? response;

            if (currentPlan.value && currentPlan.value.id === planId) {
                currentPlan.value = body?.data || body;
            }

            return body?.data || body;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to confirm import plan';

            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function cancelImportPlan(planId: number): Promise<ImportPlan> {
        loading.value = true;
        error.value = null;

        try {
            const response: any = await api.post(`/import-plans/${planId}/cancel`);
            const body = response?.data ?? response;

            if (currentPlan.value && currentPlan.value.id === planId) {
                currentPlan.value = body?.data || body;
            }

            return body?.data || body;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to cancel import plan';

            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateRow(rowId: number, data: Partial<ImportRowForm>): Promise<ImportPlanRow> {
        loading.value = true;
        error.value = null;

        try {
            const response: any = await api.put(`/import-plans/rows/${rowId}`, data);
            const body = response?.data ?? response;

            if (currentPlan.value && currentPlan.value.rows) {
                const index = currentPlan.value.rows.findIndex((row) => row.id === rowId);

                if (index !== -1) {
                    currentPlan.value.rows[index] = body?.data || body;
                }

                await fetchImportPlan(currentPlan.value.id);
            }

            return body?.data || body;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to update row';

            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function addRow(planId: number, data: ImportRowForm): Promise<ImportPlanRow> {
        loading.value = true;
        error.value = null;

        try {
            const response: any = await api.post(`/import-plans/${planId}/rows`, data);
            const body = response?.data ?? response;

            if (currentPlan.value && currentPlan.value.id === planId) {
                await fetchImportPlan(planId);
            }

            return body?.data || body;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to add row';

            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteRow(rowId: number): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            await api.delete(`/import-plans/rows/${rowId}`);

            if (currentPlan.value && currentPlan.value.rows) {
                const index = currentPlan.value.rows.findIndex((row) => row.id === rowId);

                if (index !== -1 && currentPlan.value) {
                    await fetchImportPlan(currentPlan.value.id);
                }
            }
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to delete row';

            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function downloadTemplate(format: 'csv' | 'xlsx' = 'csv'): Promise<void> {
        loading.value = true;
        error.value = null;

        try {
            if (!['csv', 'xlsx'].includes(format)) {
                throw new Error('Invalid format value');
            }

            await api.download(`/import-plans/template/download?format=${format}`, `import_template.${format}`);
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to download template';

            throw err;
        } finally {
            loading.value = false;
        }
    }

    return {
        importPlans,
        currentPlan,
        loading,
        error,
        totalPages,
        currentPage,
        fetchImportPlans,
        fetchImportPlan,
        uploadFile,
        confirmImportPlan,
        cancelImportPlan,
        updateRow,
        addRow,
        deleteRow,
        downloadTemplate,
    };
}
