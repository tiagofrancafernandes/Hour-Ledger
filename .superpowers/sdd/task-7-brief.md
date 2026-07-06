# Task 7: Test Tenant Selector Component

**Goal:** Create Vitest unit tests for TenantSelector.vue component.

**Files to create:**
- `apps/hl-drive-web/src/components/__tests__/TenantSelector.spec.ts`

**Exact test code:**

```typescript
import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import TenantSelector from '../TenantSelector.vue';
import type { Tenant } from '~/types/tenant';

describe('TenantSelector', () => {
  const mockTenants: Tenant[] = [
    { id: 1, name: 'My School', role: 'admin' },
    { id: 2, name: 'Partner School', role: 'member' },
  ];

  it('renders all tenants', () => {
    const wrapper = mount(TenantSelector, {
      props: {
        tenants: mockTenants,
        isOpen: true,
      },
      global: {
        stubs: {
          UModal: false,
          UButton: false,
        },
      },
    });

    expect(wrapper.text()).toContain('My School');
    expect(wrapper.text()).toContain('Partner School');
  });

  it('emits selected event when tenant chosen', async () => {
    const wrapper = mount(TenantSelector, {
      props: {
        tenants: mockTenants,
        isOpen: true,
      },
      global: {
        stubs: {
          UModal: false,
          UButton: false,
        },
      },
    });

    const tenantButtons = wrapper.findAll('button[type="button"]');
    await tenantButtons[0].trigger('click');

    const confirmBtn = wrapper.findAll('button').find(b => b.text().includes('Continue'));
    await confirmBtn?.trigger('click');

    expect(wrapper.emitted('selected')).toBeTruthy();
    expect(wrapper.emitted('selected')?.[0]?.[0]).toEqual(mockTenants[0]);
  });

  it('emits cancel event', async () => {
    const wrapper = mount(TenantSelector, {
      props: {
        tenants: mockTenants,
        isOpen: true,
      },
      global: {
        stubs: {
          UModal: false,
          UButton: false,
        },
      },
    });

    const cancelBtn = wrapper.findAll('button').find(b => b.text().includes('Cancel'));
    await cancelBtn?.trigger('click');

    expect(wrapper.emitted('cancel')).toBeTruthy();
  });
});
```

**Steps:**
1. Create test file with exact code above
2. Run `npm run test src/components/__tests__/TenantSelector.spec.ts` (or typecheck if test runner not configured)
3. Verify tests pass or typecheck passes
4. Commit: `test: add tenant selector component tests`

**Report to:** `/mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/.superpowers/sdd/task-7-report.md`
