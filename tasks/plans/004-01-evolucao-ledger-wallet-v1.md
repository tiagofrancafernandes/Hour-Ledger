# Plano: Evolução do Ledger e Políticas de Carteira (Fase 5)

**Prioridade:** Média  
**Data de Início Planejada:** 2026-10-16  
**Data de Conclusão Planejada:** 2026-10-31  
**Origem:** `ROADMAP.md` (Fase 5 — Evolução da Wallet)  
**Responsável:** Tiago França / Equipe Hour Ledger Ecosystem  

---

## 1. Objetivo

Expandir o motor transacional do Ledger e as políticas de carteira no HL Core, suportando múltiplos tipos de transação, transferências entre carteiras, créditos expiráveis e bônus promocionais, mantendo a imutabilidade estrita do histórico de lançamentos.

---

## 2. Escopo

### 2.1. Tipos de Transação Especializados
- [ ] Implementar enum/tipo para movimentações contábeis:
  - `purchase`: compra/aquisição de horas/créditos.
  - `transfer`: transferência entre carteiras vinculadas.
  - `bonus`: bonificação ou fidelidade concedida pelo instrutor/plataforma.
  - `refund`: estorno ou devolução de horas não utilizadas.
  - `expiration`: débito automático por vencimento de validade do crédito.
  - `adjustment`: acerto manual corretivo justificado.
  - `consumption`: consumo direto em agendamentos/aulas concluídas.

### 2.2. Políticas de Carteira (`WalletPolicy`)
- [ ] Implementar campos e verificações de políticas configuráveis por carteira:
  - `allow_transfer`: habilita/desabilita envio de créditos para terceiros.
  - `allow_negative_balance`: permite que o saldo fique negativo em situações de confiança pré-combinadas.
  - `allow_purchase`: habilita aquisição de novos pacotes.
  - `allow_expiration`: define se os créditos expiram após prazo configurável.

### 2.3. Transferências Atômicas entre Carteiras
- [ ] Criar serviço transacional de transferência que gera lançamentos compensatórios em paridade:
  - Débito na carteira de origem (`transfer_out`).
  - Crédito na carteira de destino (`transfer_in`).
  - Execução estritamente atômica dentro de transação de banco.

---

## 3. Critérios de Sucesso

- ✅ Saldo contábil derivado refletindo todos os novos tipos de transação.
- ✅ Imutabilidade estrita: nenhum UPDATE ou DELETE físico em `ledger_entries`.
- ✅ Testes unitários e de integração cobrindo cenários de transferências atômicas e expiração.
