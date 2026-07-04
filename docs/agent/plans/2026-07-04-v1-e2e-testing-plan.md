# V1 E2E Testing Plan (2026-07-04)

**Objetivo**: Validar frontend Track D com testes automatizados usando chrome-devtools

**Status**: Pronto para execução

---

## Testes E2E Planejados

### 1. Package Management Flow

**URL**: `http://localhost:3000/packages`

**Test Steps**:
1. ✓ Navegação: Abrir página de packages
2. ✓ Validação: Lista de pacotes carrega
3. ✓ Ação: Clicar "Create Package"
4. ✓ Preenchimento: Preencher form (nome, descrição, horas, preço)
5. ✓ Submissão: Clicar "Create"
6. ✓ Validação: Novo pacote aparece na lista

**Assertions**:
- PackageList renderiza corretamente
- PackageForm validação funciona
- Novo pacote é adicionado à lista
- Form limpa após submissão

---

### 2. Purchase Flow

**URL**: `http://localhost:3000/shop`

**Test Steps**:
1. ✓ Navegação: Abrir página Shop
2. ✓ Validação: Pacotes carregam
3. ✓ Ação: Clicar "Buy" em um pacote
4. ✓ Validação: Modal de compra abre
5. ✓ Preenchimento: Selecionar quantidade
6. ✓ Ação: Clicar "Confirm Purchase"
7. ✓ Validação: Wallet balance aumenta
8. ✓ Validação: Compra aparece no histórico

**Assertions**:
- Purchase modal abre/fecha corretamente
- Quantidade é validada
- Saldo da wallet é atualizado
- Histórico de compras é populado

---

### 3. Lesson Scheduling Flow

**URL**: `http://localhost:3000/schedule`

**Test Steps**:
1. ✓ Navegação: Abrir página de agendamento
2. ✓ Ação: Clicar "Book Lesson"
3. ✓ Validação: Formulário aparece
4. ✓ Preenchimento: Selecionar instrutor
5. ✓ Preenchimento: Selecionar data (date picker)
6. ✓ Preenchimento: Selecionar hora
7. ✓ Preenchimento: Selecionar duração
8. ✓ Submissão: Clicar "Book"
9. ✓ Validação: Aula aparece no calendário

**Assertions**:
- Date picker funciona
- Validação de duração
- Aula criada com status SCHEDULED
- Aula aparece no calendário/lista

---

### 4. Lesson Consumption Flow

**URL**: `http://localhost:3000/my-lessons`

**Test Steps**:
1. ✓ Navegação: Abrir página "My Lessons"
2. ✓ Validação: Aulas SCHEDULED listadas
3. ✓ Ação: Clicar "Mark Complete" em uma aula
4. ✓ Validação: Modal de confirmação
5. ✓ Ação: Confirmar
6. ✓ Validação: Status muda para COMPLETED
7. ✓ Validação: Saldo decrementado

**Assertions**:
- Aulas filtradas corretamente por status
- Consumption modal valida saldo
- Status transição: SCHEDULED → COMPLETED
- Wallet balance atualizado

---

### 5. Dashboard & Balance View

**URL**: `http://localhost:3000/dashboard`

**Test Steps**:
1. ✓ Navegação: Abrir Dashboard
2. ✓ Validação: WalletBalance carrega
3. ✓ Validação: Balance valor correto
4. ✓ Validação: TransactionHistory carrega
5. ✓ Validação: QuickStats mostram valores corretos

**Assertions**:
- Balance exibe valor formatado
- Transações filtradas corretamente
- Stats (scheduled, completed) contam certo
- Dark mode toggle funciona

---

## Test Execution Matrix

| Test | Component | User Path | Status |
|------|-----------|-----------|--------|
| Package Creation | PackageForm | admin → create | 🔄 E2E |
| Package Purchase | PurchaseForm | student → buy | 🔄 E2E |
| Lesson Booking | LessonScheduler | student → book | 🔄 E2E |
| Lesson Completion | LessonConsumption | student → complete | 🔄 E2E |
| Dashboard View | Dashboard | user → view stats | 🔄 E2E |

---

## Chrome DevTools Test Strategy

### Tools Used:
- `mcp__chrome-devtools__new_page` - Abrir nova aba
- `mcp__chrome-devtools__navigate_page` - Navegar para URLs
- `mcp__chrome-devtools__click` - Interagir com elementos
- `mcp__chrome-devtools__fill` - Preencher inputs
- `mcp__chrome-devtools__take_screenshot` - Capturar estado

### Test Flow:
1. Abrir browser
2. Navegar para URL
3. Esperar load (implicit wait)
4. Preencher formulários
5. Submeter ações
6. Validar resultado
7. Screenshot para documentação
8. Fechar aba

---

## Success Criteria

✅ Todos os 5 fluxos principais validados
✅ Sem erros console (200 erro OK para redirect)
✅ Elementos carregam em < 3 segundos
✅ Formulários validam corretamente
✅ Estado atualiza em tempo real
✅ Dark mode funciona
✅ Responsive (testar mobile view)

---

## Expected Test Duration

- Package Creation: 2 min
- Purchase Flow: 2 min
- Lesson Booking: 2 min
- Lesson Completion: 2 min
- Dashboard: 1 min

**Total**: ~9 minutos para full E2E suite

---

## Prerequisite Setup

**Backend Requirements**:
- ✅ API endpoints implementados (Track A, B, C)
- ✅ Database migrations executadas
- ✅ Test data criado (packages, instructors, students)

**Frontend Requirements**:
- ✅ Componentes implementados (Track D)
- ✅ Composables conectados
- ✅ Build executado (npm run build)

**Environment**:
- Frontend URL: `http://localhost:3000` (ou configurado)
- Backend API: `http://localhost:8000/api` (ou configurado)
- Browser: Chrome (via devtools)

---

**Status**: Pronto para execução manual com chrome-devtools
**ETA**: 20 minutos para setup + 9 minutos de testes = 29 minutos total
