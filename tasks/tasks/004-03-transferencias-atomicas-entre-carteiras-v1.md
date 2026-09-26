# Tarefa: Transferências Atômicas entre Carteiras

**Plano Relacionado:** [tasks/plans/004-01-evolucao-ledger-wallet-v1.md](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/tasks/plans/004-01-evolucao-ledger-wallet-v1.md)  
**Status:** 🟡 Planejado  
**Criado em:** 2026-09-25 21:10  
**Responsável:** Tiago França / Equipe Hour Ledger Ecosystem  

---

## 1. Contexto

Implementar transferência segura de créditos/horas entre carteiras do mesmo tenant através de lançamentos compensatórios pares no ledger (`transfer_out` e `transfer_in`) dentro de transação ACID no PostgreSQL.

---

## 2. Escopo de Trabalho

- [ ] Implementar método `transfer(Wallet $source, Wallet $destination, float $hours, string $description)` no `LedgerService`.
- [ ] Validar políticas de carteira de ambas as carteiras envolvidas.
- [ ] Executar débito e crédito atomicamente dentro de `DB::transaction()`.
- [ ] Criar endpoint `POST /api/wallets/{wallet}/transfer`.
- [ ] Criar testes garantindo rollback completo em caso de falha e consistência dos saldos.
