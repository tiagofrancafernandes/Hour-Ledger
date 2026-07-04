# V1 Completion Strategy (2026-06-28)

**Goal**: Complete all V1 flows by end of June  
**Status**: Phase 3 & 4 complete, V1 critical paths remain  
**Authority**: VISION.md (V1 flows are mandatory)

---

## Current State

### ✅ Completed (Phase 3-4)
- Authentication & authorization
- Student-instructor links (invites, acceptance)
- Multi-tenancy with PostgreSQL schemas
- Wallet/Ledger (foundation ready)
- 63 isolation tests validated

### ❌ Pending (Critical for V1)
1. **Packages** - Instructor creates hour packages
2. **Hour Acquisition** - Student buys hours (via wallet)
3. **Hour Consumption** - Student uses hours in lessons
4. **Lesson Scheduling** - Schedule lessons with instructors
5. **Basic Schedule View** - Show scheduled lessons

---

## Implementation Plan

### Phase 5: Core V1 Domain (Parallel Execution)

**Timeline**: 2026-06-28 to 2026-07-05 (8 days)

#### Track A: Packages (3 days)
1. **Database**: Lesson Package model + migrations
2. **Backend**: Package CRUD controllers + policies
3. **Frontend**: Package creation & listing UI
4. **Tests**: Package isolation + access control (5+ tests)

#### Track B: Hour Acquisition (2 days)
1. **Backend**: Wire Wallet to package purchase flow
2. **Transactions**: Create ledger entries for purchases
3. **Frontend**: Purchase UI (credit card integration optional for V1)
4. **Tests**: Transaction validation (3+ tests)

#### Track C: Lessons & Consumption (3 days)
1. **Database**: Lesson model + scheduling
2. **Backend**: Lesson creation, scheduling, hour consumption
3. **Frontend**: Lesson booking & schedule view
4. **Tests**: Consumption isolation + edge cases (5+ tests)

#### Track D: Documentation & Deployment (1 day)
1. Update EXECUTION.md (V1 complete)
2. Generate V1 changelog
3. Prepare staging deployment checklist

---

## Execution Strategy

### Subagent Parallelization

```
Day 1 (Track A):    Package schema + controllers (subagent-backend)
Day 1 (Track B):    Wallet integration (subagent-ledger)
Day 2 (Track C):    Lesson scheduling (subagent-backend-2)

Day 2 (Track A):    Package UI (subagent-frontend)
Day 3 (Track B):    Purchase UI (subagent-frontend-2)

Day 3-4 (Tests):    All isolation + transaction tests (subagent-tests)
Day 5 (Deploy):     V1 validation checklist (documentation)
```

### Quality Gates

- ✅ All code follows UNIVERSAL-CODE-STYLE-RULES.md
- ✅ All models have BelongsToTenant trait
- ✅ All queries respect TenantScope
- ✅ 100% isolation tests passing
- ✅ EXECUTION.md updated with completion

---

## Risk Mitigation

1. **Tenant Isolation**: TenantScope must be applied to ALL new models
2. **Data Integrity**: Ledger is append-only (no balance columns)
3. **Authorization**: Every action validated by policies
4. **Testing**: Each feature gets isolation + edge-case tests

---

## Next Actions

1. ✅ Confirm this plan (approval needed)
2. Dispatch Track A backend subagent (Package schema)
3. Dispatch Track B ledger subagent (Wallet integration)
4. Dispatch Track C backend subagent (Lesson model)
5. Dispatch frontend subagents once backend ready

**Ready to begin?**
