# Task 5: Export Tenant Types

**Goal:** Export Tenant types from `src/types/index.ts` so they're available app-wide.

**Files to modify:**
- `apps/hl-drive-web/src/types/index.ts`

**Exact addition:**

Add this line to `src/types/index.ts`:
```typescript
export * from './tenant';
```

**Steps:**
1. Open `apps/hl-drive-web/src/types/index.ts`
2. Add the export line
3. Run `npm run typecheck`
4. Commit: `feat: export tenant types`

**Report to:** `/mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/.superpowers/sdd/task-5-report.md`
