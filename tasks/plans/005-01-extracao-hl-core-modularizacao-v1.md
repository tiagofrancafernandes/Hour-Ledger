# Plano: Extração do HL Core e Modularização Monorepo (Fase 6)

**Prioridade:** Baixa / Médio Prazo  
**Data de Início Planejada:** 2026-11-01  
**Data de Conclusão Planejada:** 2026-11-20  
**Origem:** `ROADMAP.md` (Fase 6 — Novos Produtos) & `docs/future/spikes/`  
**Responsável:** Tiago França / Equipe Hour Ledger Ecosystem  

---

## 1. Objetivo

Extrair os componentes genéricos e reutilizáveis atualmente residentes em `apps/hl-drive-api` e `apps/hl-drive-web` para os pacotes compartilhados em `packages/backend/core`, `packages/backend/ledger` e `packages/frontend/ui`, viabilizando o lançamento de novos produtos verticais como o **HL Consulting** sem duplicação de regras de negócio.

---

## 2. Escopo

### 2.1. Extração Backend (`packages/backend/core` & `ledger`)
- [ ] Mapear e mover modelos e contratos fundamentais:
  - `User`, `Tenant`, `TenantContext`, `Invitation`, `Preference`.
  - `Wallet`, `LedgerEntry`, `CreditPurchase`.
- [ ] Mapear e mover traits e scopes reutilizáveis:
  - `BelongsToTenant`, `TenantScope`.
- [ ] Mover serviços centrais:
  - `TenantResolver`, `BalanceCalculatorService`, `AuthService`.
- [ ] Configurar autoload PSR-4 no `composer.json` da raiz para os pacotes locais.

### 2.2. Extração Frontend (`packages/frontend/ui` & `core`)
- [ ] Mover presets do Tailwind, composables de autenticação base e internacionalização.
- [ ] Mover componentes de ledger, saldo e carteiras genéricos.

### 2.3. Preparação do HL Consulting
- [ ] Criar estrutura base de `apps/hl-consulting-api` e `apps/hl-consulting-web` consumindo os pacotes compartilhados.

---

## 3. Critérios de Sucesso

- ✅ HL Drive funcionando normalmente consumindo os pacotes `packages/backend/*`.
- ✅ Boundaries arquiteturais respeitados: o Core nunca importa código específico do Drive ou Consulting.
- ✅ Autoloading e testes da suíte completa passando sem regressão.
