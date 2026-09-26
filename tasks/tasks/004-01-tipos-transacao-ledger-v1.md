# Tarefa: Tipos Especializados de Transação do Ledger

**Plano Relacionado:** [tasks/plans/004-01-evolucao-ledger-wallet-v1.md](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/tasks/plans/004-01-evolucao-ledger-wallet-v1.md)  
**Status:** 🟡 Planejado  
**Criado em:** 2026-09-25 21:10  
**Responsável:** Tiago França / Equipe Hour Ledger Ecosystem  

---

## 1. Contexto

Expandir os tipos contábeis aceitos no `LedgerEntry` para além dos básicos, incorporando formalmente `purchase`, `transfer`, `bonus`, `refund`, `expiration`, `adjustment` e `consumption`.

---

## 2. Escopo de Trabalho

- [ ] Criar enum PHP `LedgerEntryType` com os tipos oficiais.
- [ ] Atualizar validação de formulários (`StoreLedgerEntryRequest`).
- [ ] Atualizar cálculo de saldo derivado no `BalanceCalculatorService`.
- [ ] Criar testes cobrindo lançamentos de cada tipo e validação de imutabilidade.
