# Plano — Extração de Ledger / Wallet

Data: 2026-05-13
Status: Pendente aprovação

## Objetivo

Extrair as funcionalidades de Ledger e Wallet do `hl-drive-api` e `hl-drive-web` para os novos pacotes compartilhados em `packages/`.

## Arquivos Candidatos

### Backend (`packages/backend/ledger`)

#### Modelos
- `app/Models/Wallet.php`
- `app/Models/LedgerEntry.php`
- `app/Models/CreditPurchase.php`
- `app/Models/CreditPurchasePayment.php`

#### Controladores
- `app/Http/Controllers/Api/WalletController.php`
- `app/Http/Controllers/Api/LedgerEntryController.php`
- `app/Http/Controllers/Api/CreditPurchaseController.php`
- `app/Http/Controllers/Api/PaymentController.php`
- `app/Http/Controllers/Api/PaymentApprovalController.php`
- `app/Http/Controllers/Api/PaymentReceiptController.php`

#### Enums
- `app/Enums/CreditPurchaseStatus.php`
- `app/Enums/PaymentStatus.php`

### Frontend (`packages/frontend/wallet`)

#### Stores
- `src/stores/wallet.ts`

#### Views (para refatorar ou mover componentes)
- `src/views/WalletDetailView.vue`
- `src/views/PaymentHistoryView.vue`
- `src/views/AdminPaymentApprovalView.vue`

## O que permanece no HL Drive

- `app/Models/Timer.php` e `TimerCycle.php` (Consumidores do Ledger).
- `app/Http/Controllers/Api/TimerController.php`.
- `src/stores/timer.ts`.
- `src/views/TimersView.vue`.

## Riscos Identificados

1. **Quebra de Namespaces:** A mudança para `HL\Core\Ledger` ou similar exigirá atualizações em todos os locais que importam estes modelos.
2. **Migrations:** Mover migrations para pacotes exige uma estratégia de carregamento (`$this->loadMigrationsFrom(...)`).
3. **Acoplamento com User:** `Wallet` e `LedgerEntry` dependem de `User`. O `User` deve ser tratado como uma interface ou uma entidade core.
4. **Acoplamento com Client:** Mesma situação do User.

## Plano de Execução (Milestone 3)

### Passo 1 — Preparação Backend
- Criar `packages/backend/ledger`.
- Inicializar `composer.json` do pacote.

### Passo 2 — Migração de Código Backend
- Mover modelos e enums.
- Atualizar namespaces para `HL\Core\Ledger\Models`.
- Ajustar `hl-drive-api` para usar o pacote via composer (path repository).

### Passo 3 — Preparação Frontend
- Criar `packages/frontend/wallet`.
- Inicializar `package.json`.

### Passo 4 — Migração de Código Frontend
- Mover store de wallet.
- Ajustar imports no `hl-drive-web`.

## Testes Necessários

- [ ] Validar que o saldo da wallet continua sendo calculado corretamente a partir do ledger.
- [ ] Validar que novas transações (crédito/débito) continuam funcionando.
- [ ] Validar que a aprovação de pagamentos reflete no saldo da wallet.

---
**Agente:** Antigravity (AI Agent)
