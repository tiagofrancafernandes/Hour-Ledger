import { ref } from 'vue';
import api from '@/services/api';
import type { LedgerEntry, PaginatedResponse, ReportFilters, ReportSummary } from '@/types';

interface GroupedData {
    wallet_id?: number;
    wallet_name?: string;
    client_id?: number;
    client_name?: string;
    total_credits: string;
    total_debits: string;
    net_balance: string;
    entry_count: number;
}

interface ReportResponse {
    summary: ReportSummary;
    entries?: PaginatedResponse<LedgerEntry>;
    grouped?: GroupedData[];
}

export function useReports() {
    const summary = ref<ReportSummary | null>(null);
    const entries = ref<LedgerEntry[]>([]);
    const groupedData = ref<GroupedData[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);
    const pagination = ref({
        currentPage: 1,
        lastPage: 1,
        total: 0,
    });

    function buildQueryString(filters: ReportFilters): string {
        const params = new URLSearchParams();

        if (filters.client_id) {
            params.append('client_id', String(filters.client_id));
        }

        if (filters.wallet_id) {
            params.append('wallet_id', String(filters.wallet_id));
        }

        if (filters.date_from) {
            params.append('date_from', filters.date_from);
        }

        if (filters.date_to) {
            params.append('date_to', filters.date_to);
        }

        if (filters.tags && filters.tags.length > 0) {
            filters.tags.forEach((tag) => params.append('tags[]', String(tag)));
        }

        if (filters.type) {
            params.append('type', filters.type);
        }

        if (filters.group_by) {
            params.append('group_by', filters.group_by);
        }

        if (filters.per_page) {
            params.append('per_page', String(filters.per_page));
        }

        return params.toString();
    }

    async function fetchReport(filters: ReportFilters = {}) {
        loading.value = true;
        error.value = null;

        entries.value = [];
        groupedData.value = [];

        try {
            const queryString = buildQueryString(filters);
            const response = await api.get<ReportResponse>(`/reports?${queryString}`);

            summary.value = response.summary;

            if (response.entries) {
                entries.value = response.entries.data;
                pagination.value = {
                    currentPage: response.entries.current_page,
                    lastPage: response.entries.last_page,
                    total: response.entries.total,
                };
            } else {
                entries.value = [];
                pagination.value = {
                    currentPage: 1,
                    lastPage: 1,
                    total: 0,
                };
            }

            if (response.grouped) {
                groupedData.value = response.grouped;
            } else {
                groupedData.value = [];
            }
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch report';
        } finally {
            loading.value = false;
        }
    }

    async function fetchSummary(filters: ReportFilters = {}) {
        loading.value = true;
        error.value = null;

        try {
            const queryString = buildQueryString(filters);

            summary.value = await api.get<ReportSummary>(`/reports/summary?${queryString}`);
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch summary';
        } finally {
            loading.value = false;
        }
    }

    return {
        summary,
        entries,
        groupedData,
        loading,
        error,
        pagination,
        fetchReport,
        fetchSummary,
    };
}
