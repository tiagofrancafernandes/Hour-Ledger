import { useTenantStore } from '@/stores/tenant';

export interface TenantHeadersComposable {
    headers: () => Record<string, string>;
    getTenantIdHeader: () => string | null;
}

export function useTenantHeaders(): TenantHeadersComposable {
    const tenantStore = useTenantStore();

    function getTenantIdHeader(): string | null {
        const { activeTenantId } = tenantStore;

        if (!activeTenantId.value) {
            return null;
        }

        return String(activeTenantId.value);
    }

    function headers(): Record<string, string> {
        const headerRecord: Record<string, string> = {};
        const tenantHeader = getTenantIdHeader();

        if (tenantHeader) {
            headerRecord['X-Tenant-ID'] = tenantHeader;
        }

        return headerRecord;
    }

    return {
        headers,
        getTenantIdHeader,
    };
}
