# Task 1: Define Tenant Types

**Goal:** Create TypeScript type definitions for Tenant and LoginResponse. These types are used by auth store and TenantSelector component.

**Context:** V1 frontend integration of multi-tenant login. Backend already returns `accessible_tenants` after login; frontend doesn't use it yet. This task establishes the type contract.

**Files to create:**
- `apps/hl-drive-web/src/types/tenant.ts`

**Exact code to create:**

```typescript
// apps/hl-drive-web/src/types/tenant.ts

export type TenantRole = 'admin' | 'instructor' | 'student' | 'member';

export interface Tenant {
  id: number;
  name: string;
  role: TenantRole;
  status?: 'active' | 'inactive';
  created_at?: string;
}

export interface LoginResponse {
  user: {
    id: number;
    email: string;
    name?: string;
  };
  token: string;
  role: string;
  permissions: string[];
  accessible_tenants: Tenant[];
}
```

**Steps:**
1. Create file `apps/hl-drive-web/src/types/tenant.ts` with exact code above
2. Run `npm run typecheck` to verify no errors
3. Commit with message: `feat: define tenant and login response types`

**What to report:**
- Status: DONE / NEEDS_CONTEXT / DONE_WITH_CONCERNS / BLOCKED
- Commits: list of commit SHA7 created
- Test output: typecheck result
- Concerns: any issues found during implementation
