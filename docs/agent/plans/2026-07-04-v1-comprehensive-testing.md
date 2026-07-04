# V1 Comprehensive Testing Plan (2026-07-04)

**Objetivo**: Validar todas as funcionalidades V1 com testes automatizados (backend) e E2E (frontend)

**Status**: Aguardando conclusão de Tracks A, B, C

---

## Backend Testing Strategy

### Track A: Package Management Tests

**File**: `tests/Feature/PackageManagementTest.php`

Tests para validar regras de negócio críticas:

1. **test_instructor_can_create_package** ✅
   - Dado: Um instrutor
   - Quando: Cria um novo pacote (nome, descrição, horas, preço)
   - Então: Pacote é criado com tenant_id correto

2. **test_package_isolation_by_tenant** ✅
   - Dado: Dois tenants diferentes
   - Quando: Ambos criam pacotes
   - Então: Pacotes não são visíveis entre tenants

3. **test_instructor_can_only_see_own_packages** ✅
   - Dado: Dois instrutores no mesmo tenant
   - Quando: Instrutor A lista pacotes
   - Então: Vê apenas seus próprios pacotes

4. **test_package_soft_delete_preserves_history** ✅
   - Dado: Um pacote ativo
   - Quando: Pacote é deletado
   - Então: Soft delete preserva histório para auditoria

5. **test_package_factory_generates_realistic_data** ✅
   - Dado: Factory de pacotes
   - Quando: Factory cria 10 pacotes
   - Então: Todos têm dados válidos (horas 5-100, preço 50-500)

---

### Track B: Hour Acquisition Tests

**File**: `tests/Feature/HourAcquisitionTest.php`

Tests para validar fluxo de compra de horas:

1. **test_student_can_purchase_package** ✅
   - Dado: Estudante + pacote disponível
   - Quando: Estudante compra pacote
   - Então: PackagePurchase criado com status COMPLETED

2. **test_purchase_creates_ledger_entry** ✅
   - Dado: Compra realizada
   - Quando: Ledger é consultado
   - Então: Entrada PURCHASE criada com horas corretas

3. **test_wallet_balance_updates_after_purchase** ✅
   - Dado: Estudante com saldo inicial
   - Quando: Compra pacote com 10 horas
   - Então: Saldo = saldo_anterior + 10

4. **test_purchase_isolation_by_tenant** ✅
   - Dado: Dois tenants com compras
   - Quando: Query compras de tenant A
   - Então: Compras de tenant B não aparecem

5. **test_purchase_status_transitions** ✅
   - Dado: Compra PENDING
   - Quando: HourPurchaseService processa
   - Então: Status = COMPLETED + transaction_id preenchido

---

### Track C: Lesson Scheduling & Consumption Tests

**File**: `tests/Feature/LessonSchedulingTest.php`

Tests para validar agendamento e consumo de aulas:

1. **test_instructor_can_schedule_lesson** ✅
   - Dado: Instrutor + estudante + data/hora
   - Quando: Agenda aula
   - Então: Lesson criado com status SCHEDULED

2. **test_student_can_view_scheduled_lessons** ✅
   - Dado: Estudante com aulas agendadas
   - Quando: Lista aulas
   - Então: Vê apenas suas aulas com status correto

3. **test_marking_lesson_complete_consumes_hours** ✅
   - Dado: Aula SCHEDULED com estudante com saldo
   - Quando: Marca aula como COMPLETED
   - Então: LedgerEntry CONSUMPTION criado com saldo decrementado

4. **test_insufficient_balance_blocks_consumption** ✅
   - Dado: Estudante com saldo < duração da aula
   - Quando: Tenta marcar aula como COMPLETED
   - Então: Exceção "Insufficient balance"

5. **test_active_instructor_link_required** ✅
   - Dado: Aula com instrutor sem link ativo
   - Quando: Tenta marcar COMPLETED
   - Então: Exceção "Active instructor link does not exist"

6. **test_lesson_prevents_instructor_double_booking** ✅
   - Dado: Instrutor com aula agendada às 10h
   - Quando: Tenta agendar outra aula no mesmo horário
   - Então: Unique constraint falha (database-level)

7. **test_lesson_isolation_by_tenant** ✅
   - Dado: Dois tenants com aulas
   - Quando: Query aulas de tenant A
   - Então: Aulas de tenant B não aparecem

8. **test_lesson_status_transitions** ✅
   - Dado: Aula SCHEDULED
   - Quando: Marca COMPLETED
   - Então: Status = COMPLETED + aula não pode ser reprocessada

9. **test_lesson_scopes_work** ✅
   - Dado: Aulas em vários estados
   - Quando: Usa scopes (byInstructor, byStudent, scheduled, completed)
   - Então: Filtros retornam resultados corretos

10. **test_consumption_respects_tenant_isolation** ✅
    - Dado: Dois tenants com mesmos estudantes
    - Quando: Consome horas em tenant A
    - Então: Saldo de tenant B não é afetado

---

### Integration Tests (Cross-Domain)

**File**: `tests/Feature/V1IntegrationTest.php`

Tests para validar fluxos completos V1:

1. **test_complete_v1_workflow** ⏳
   - Fluxo: Criar pacote → Comprar horas → Agendar aula → Consumir horas
   - Validações: Saldo correto em cada etapa, isolamento mantido

2. **test_multi_instructor_workflow** ⏳
   - Fluxo: Estudante com 2 instrutores → Compra horas → Agenda aulas com ambos → Consome horas isoladamente
   - Validações: Cada instrutor tem acesso apenas suas aulas

3. **test_ledger_immutability** ⏳
   - Fluxo: Compra → Consumo → Query ledger
   - Validações: Ledger é append-only, não há updates/deletes

4. **test_multi_tenant_isolation_workflow** ⏳
   - Fluxo: Duas tenants completam V1 workflow em paralelo
   - Validações: Dados não são visíveis entre tenants

---

## Frontend Testing Strategy (E2E com chrome-devtools)

**Quando Track D completa**, executaremos testes E2E:

### UI Component Tests

1. **Package Listing & Creation**
   - Navegação → Packages
   - Validação: Lista carrega pacotes do instrutor
   - Ação: Cria novo pacote
   - Validação: Pacote aparece na lista

2. **Package Purchase Flow**
   - Navegação → Shop
   - Ação: Clica "Buy" em pacote
   - Validação: Modal de confirmação aparece
   - Ação: Confirma compra
   - Validação: Wallet balance aumenta

3. **Lesson Scheduling**
   - Navegação → Schedule
   - Ação: Clica "Book Lesson"
   - Validação: Formulário aparece com date picker
   - Ação: Seleciona data/hora/duração, confirma
   - Validação: Aula aparece no calendário

4. **Lesson Consumption**
   - Navegação → My Lessons
   - Validação: Aulas SCHEDULED listadas
   - Ação: Clica "Mark Complete"
   - Validação: Status muda para COMPLETED, saldo decrementado

5. **Dashboard & Balance**
   - Navegação → Dashboard
   - Validação: Balance atual mostrado
   - Validação: Histórico de transações carregado
   - Validação: Stats (aulas agendadas, completadas) corretos

---

## Test Metrics

### Backend Coverage

| Category | Target | Current |
|----------|--------|---------|
| Unit Tests | 50+ | TBD |
| Feature Tests | 20+ | TBD |
| Integration Tests | 10+ | TBD |
| Total | 80+ | TBD |
| Pass Rate | 100% | Monitorar |

### Frontend Coverage

| Feature | Test Status |
|---------|-------------|
| Package CRUD | 🔄 E2E |
| Purchase Flow | 🔄 E2E |
| Lesson Scheduling | 🔄 E2E |
| Lesson Consumption | 🔄 E2E |
| Balance View | 🔄 E2E |

---

## Execution Order

1. **Backend Tests**: Execute após Tracks A, B, C concluem
   ```bash
   php artisan test tests/Feature/PackageManagementTest.php
   php artisan test tests/Feature/HourAcquisitionTest.php
   php artisan test tests/Feature/LessonSchedulingTest.php
   php artisan test tests/Feature/V1IntegrationTest.php
   ```

2. **Frontend Tests**: Execute após Track D concluir (com chrome-devtools)
   ```bash
   npm run test:e2e:packages
   npm run test:e2e:purchases
   npm run test:e2e:lessons
   npm run test:e2e:dashboard
   ```

3. **Full Regression**: Execute antes de staging deploy
   ```bash
   php artisan test --no-coverage
   npm run test:e2e:full
   ```

---

## Success Criteria

✅ **Backend**: 80+ testes passando (100% pass rate)
✅ **Frontend**: 20+ E2E testes passando (100% pass rate)
✅ **Integration**: Complete V1 workflow validado end-to-end
✅ **Performance**: Resposta < 200ms para principais operações
✅ **Security**: Multi-tenant isolation confirmada

---

**Status**: Aguardando conclusão de Tracks A, B, C
**ETA**: 2026-07-05
