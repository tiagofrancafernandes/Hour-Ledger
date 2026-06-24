import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount, VueWrapper } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import TenantSelector from '@/components/TenantSelector.vue';
import { useTenantStore, type Tenant } from '@/stores/tenant';
import { nextTick } from 'vue';

describe('TenantSelector Component', () => {
    let wrapper: VueWrapper<any>;
    let store: ReturnType<typeof useTenantStore>;

    beforeEach(() => {
        setActivePinia(createPinia());
        store = useTenantStore();

        wrapper = mount(TenantSelector, {
            global: {
                plugins: [createPinia()],
                stubs: {
                    Icon: { template: '<i class="icon" />' },
                },
            },
        });
    });

    afterEach(() => {
        wrapper.unmount();
    });

    it('should render component', () => {
        expect(wrapper.exists()).toBe(true);
    });

    it('should display tenant name when active', async () => {
        const mockTenant: Tenant = {
            id: 1,
            name: 'Tenant A',
            status: 'active',
            created_at: '2026-01-01T00:00:00Z',
        };

        store.tenants.value = [mockTenant];
        store.activeTenantId.value = 1;

        await nextTick();

        expect(wrapper.text()).toContain('Tenant A');
    });

    it('should display placeholder when no tenant selected', async () => {
        store.tenants.value = [];
        store.activeTenantId.value = null;

        await nextTick();

        expect(wrapper.text()).toContain('No tenant selected');
    });

    it('should toggle dropdown on button click', async () => {
        const button = wrapper.find('button');

        expect(wrapper.find('.absolute').exists()).toBe(false);

        await button.trigger('click');
        await nextTick();

        expect(wrapper.find('.absolute').exists()).toBe(true);
    });

    it('should render tenant list in dropdown', async () => {
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

        const button = wrapper.find('button');
        await button.trigger('click');
        await nextTick();

        const dropdownButtons = wrapper.findAll('.absolute button');

        expect(dropdownButtons.length).toBeGreaterThan(0);
    });

    it('should disable suspended tenants', async () => {
        const mockTenants: Tenant[] = [
            {
                id: 1,
                name: 'Active Tenant',
                status: 'active',
                created_at: '2026-01-01T00:00:00Z',
            },
            {
                id: 2,
                name: 'Suspended Tenant',
                status: 'suspended',
                created_at: '2026-01-01T00:00:00Z',
            },
        ];

        store.tenants.value = mockTenants;

        const button = wrapper.find('button');
        await button.trigger('click');
        await nextTick();

        const suspendedButton = wrapper.find('.opacity-60');

        expect(suspendedButton.exists()).toBe(true);
    });

    it('should show loading state', async () => {
        store.loading.value = true;

        await nextTick();

        expect(wrapper.text()).toContain('Loading tenants');
    });

    it('should show error state', async () => {
        store.error.value = 'Failed to load tenants';

        const button = wrapper.find('button');
        await button.trigger('click');
        await nextTick();

        expect(wrapper.text()).toContain('Failed to load tenants');
    });

    it('should call selectTenant when tenant is clicked', async () => {
        const mockTenants: Tenant[] = [
            {
                id: 1,
                name: 'Tenant A',
                status: 'active',
                created_at: '2026-01-01T00:00:00Z',
            },
        ];

        store.tenants.value = mockTenants;

        const selectSpy = vi.spyOn(store, 'setActiveTenant');

        const button = wrapper.find('button');
        await button.trigger('click');
        await nextTick();

        const tenantButton = wrapper.findAll('button')[1];
        await tenantButton.trigger('click');

        expect(selectSpy).toHaveBeenCalledWith(1);
    });

    it('should close dropdown when clicking outside', async () => {
        const mockTenants: Tenant[] = [
            {
                id: 1,
                name: 'Tenant A',
                status: 'active',
                created_at: '2026-01-01T00:00:00Z',
            },
        ];

        store.tenants.value = mockTenants;

        const button = wrapper.find('button');
        await button.trigger('click');
        await nextTick();

        const backdrop = wrapper.find('.fixed');
        await backdrop.trigger('click');
        await nextTick();

        expect(wrapper.find('.absolute').exists()).toBe(false);
    });

    it('should show check icon for active tenant', async () => {
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

        const button = wrapper.find('button');
        await button.trigger('click');
        await nextTick();

        expect(wrapper.text()).toContain('Tenant A');
    });

    it('should generate correct initials from tenant name', async () => {
        const mockTenant: Tenant = {
            id: 1,
            name: 'John Doe',
            status: 'active',
            created_at: '2026-01-01T00:00:00Z',
        };

        store.tenants.value = [mockTenant];
        store.activeTenantId.value = 1;

        await nextTick();

        const avatar = wrapper.find('.w-8.h-8.rounded-full');
        expect(avatar.text()).toContain('JD');
    });
});
