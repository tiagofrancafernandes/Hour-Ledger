# Tarefa: Políticas de Carteira (WalletPolicy)

**Plano Relacionado:** [tasks/plans/004-01-evolucao-ledger-wallet-v1.md](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/tasks/plans/004-01-evolucao-ledger-wallet-v1.md)  
**Status:** 🟡 Planejado  
**Criado em:** 2026-09-25 21:10  
**Responsável:** Tiago França / Equipe Hour Ledger Ecosystem  

---

## 1. Contexto

Permitir que cada carteira (`Wallet`) defina políticas granulares de movimentação contábil (`allow_transfer`, `allow_negative_balance`, `allow_purchase`, `allow_expiration`).

---

## 2. Escopo de Trabalho

- [ ] Criar migration adicionando campos de política na tabela `wallets`.
- [ ] Implementar classe de validação de políticas no `WalletService` antes de qualquer lançamento.
- [ ] Bloquear lançamentos que violem as regras definidas na carteira com exceções de domínio apropriadas.
- [ ] Criar testes unitários e de integração validando cada política.
