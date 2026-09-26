# Tarefa: Camada de Cache Redis para Saldo de Carteiras

**Plano Relacionado:** [tasks/plans/doing/003-01-debitos-tecnicos-e-resiliencia-v1.md](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/tasks/plans/doing/003-01-debitos-tecnicos-e-resiliencia-v1.md)  
**Status:** 🟡 Planejado  
**Criado em:** 2026-09-25 21:10  
**Responsável:** Tiago França / Equipe Hour Ledger Ecosystem  

---

## 1. Contexto

O cálculo do saldo derivado das carteiras (`BalanceCalculatorService::getWalletBalance`) recalcula `SUM(hours)` a cada requisição. Em carteiras com milhares de lançamentos no ledger, essa operação gera carga desnecessária no PostgreSQL. Esta tarefa introduz cache via Redis por 5 minutos com invalidação por eventos.

---

## 2. Escopo de Trabalho

- [ ] Implementar `Cache::remember` com chave `wallet.{id}.balance` no `BalanceCalculatorService`.
- [ ] Registrar invalidação de cache (`Cache::forget`) em `LedgerEntryObserver` nos eventos `created`, `updated`, `deleted`.
- [ ] Implementar fallback transparente caso o Redis esteja indisponível.
- [ ] Criar testes automatizados cobrindo cache hit, cache miss e invalidação imediata após novos lançamentos.

---

## 3. Critérios de Aceitação

- Código segue `UNIVERSAL-CODE-STYLE-RULES.md`.
- Invalidação atômica e correta do saldo ao registrar lançamentos.
- Testes automatizados passando 100%.
