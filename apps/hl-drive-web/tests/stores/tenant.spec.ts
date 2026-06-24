import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useTenantStore, type Tenant } from '@/stores/tenant';
import { api } from '@/services/api';

vi.mock('@/services/api');

describe('Tenant Store', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        localStorage.clear();
        vi.clearAllMocks();
    });

    afterEach(() => {
        localStorage.clear();
    });

    it('should initialize with empty state', () => {
        const store = useTenantStore();

        expect(store.activeTenantId.value).toBeNull();
        expect(store.tenants.value).toEqual([]);
        expect(store.loading.value).toBe(false);
        expect(store.error.value).toBeNull();
    });

    it('should set active tenant and persist to localStorage', () => {
        const store = useTenantStore();
        const mockTenants: Tenant[] = [
            {
                id: 1,
                name: 'Tenant A',
                status: 'active',
                created_at: '2026-01-01T00:00:00Z',
            },
            {
                id: 2,
                name: 'Tenant B',
                status: 'active',
                created_at: '2026-01-01T00:00:00Z',
            },
        ];

        store.tenants.value = mockTenants;
        store.setActiveTenant(1);

        expect(store.activeTenantId.value).toBe(1);
        expect(localStorage.getItem('tenant_active_id')).toBe('1');
    });

    it('should not set active tenant if status is not active', () => {
        const store = useTenantStore();
        const mockTenants: Tenant[] = [
            {
                id: 1,
                name: 'Suspended Tenant',
                status: 'suspended',
                created_at: '2026-01-01T00:00:00Z',
            },
        ];

        store.tenants.value = mockTenants;
        store.setActiveTenant(1);

        expect(store.activeTenantId.value).toBeNull();
        expect(store.error.value).toBe('Tenant is not active');
    });

    it('should return null if tenant not found', () => {
        const store = useTenantStore();
        const result = store.getActiveTenant();

        expect(result).toBeNull();
    });

    it('should return active tenant', () => {
        const store = useTenantStore();
        const mockTenants: Tenant[] = [
            {
                id: 1,
                name: 'Tenant A',
                status: 'active',
                created_at: '2026-01-01T00:00:00Z',
            },
        ];

        store.tenants.value = mockTenants;
        store.activeTenantId.value = 1;

        const result = store.getActiveTenant();

        expect(result).toEqual(mockTenants[0]);
    });

    it('should fetch tenants from API', async () => {
        const store = useTenantStore();
        const mockTenants: Tenant[] = [
            {
                id: 1,
                name: 'Tenant A',
                status: 'active',
                created_at: '2026-01-01T00:00:00Z',
            },
        ];

        vi.mocked(api.get).mockResolvedValueOnce({ data: mockTenants });

        await store.fetchTenants();

        expect(store.tenants.value).toEqual(mockTenants);
        expect(store.loading.value).toBe(false);
    });

    it('should handle fetch tenants error', async () => {
        const store = useTenantStore();
        const errorMessage = 'API Error';

        vi.mocked(api.get).mockRejectedValueOnce(new Error(errorMessage));

        await store.fetchTenants();

        expect(store.tenants.value).toEqual([]);
        expect(store.error.value).toBe(errorMessage);
        expect(store.activeTenantId.value).toBeNull();
    });

    it('should auto-select first active tenant if current is invalid', async () => {
        const store = useTenantStore();
        const mockTenants: Tenant[] = [
            {
                id: 1,
                name: 'Tenant A',
                status: 'suspended',
                created_at: '2026-01-01T00:00:00Z',
            },
            {
                id: 2,
                name: 'Tenant B',
                status: 'active',
                created_at: '2026-01-01T00:00:00Z',
            },
        ];

        vi.mocked(api.get).mockResolvedValueOnce({ data: mockTenants });

        store.activeTenantId.value = 1;
        await store.fetchTenants();

        expect(store.activeTenantId.value).toBe(2);
    });

    it('should clear tenant context', () => {
        const store = useTenantStore();

        store.activeTenantId.value = 1;
        store.tenants.value = [
            {
                id: 1,
                name: 'Tenant A',
                status: 'active',
                created_at: '2026-01-01T00:00:00Z',
            },
        ];
        localStorage.setItem('tenant_active_id', '1');

        store.clearTenant();

        expect(store.activeTenantId.value).toBeNull();
        expect(store.tenants.value).toEqual([]);
        expect(store.error.value).toBeNull();
        expect(localStorage.getItem('tenant_active_id')).toBeNull();
    });

    it('should set active tenant from localStorage on loadFromStorage', () => {
        const store = useTenantStore();

        localStorage.setItem('tenant_active_id', '42');
        store.loadFromStorage();

        expect(store.activeTenantId.value).toBe(42);
    });

    it('should emit tenant-changed event when setting active tenant', () => {
        const store = useTenantStore();
        const mockTenants: Tenant[] = [
            {
                id: 1,
                name: 'Tenant A',
                status: 'active',
                created_at: '2026-01-01T00:00:00Z',
            },
        ];

        const eventSpy = vi.fn();
        window.addEventListener('tenant-changed', eventSpy);

        store.tenants.value = mockTenants;
        store.setActiveTenant(1);

        expect(eventSpy).toHaveBeenCalled();

        window.removeEventListener('tenant-changed', eventSpy);
    });
});
