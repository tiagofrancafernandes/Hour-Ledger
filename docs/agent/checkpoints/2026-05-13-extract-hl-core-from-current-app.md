# Checkpoint — Extração progressiva do HL Core

## Status atual

Planejado.

## Estrutura atual confirmada

- Backend: `apps/hl-drive-api`
- Frontend: `apps/hl-drive-web`

## Última milestone concluída

- Milestone 0 — Validar estrutura atual
- Milestone 1 — Inventário de domínio e código
- Milestone 2 — Planejar extração de Ledger / Wallet

## Próxima ação

Aguardar revisão humana do plano de extração (Etapa 6 de EXECUTION.md).

## Pendências imediatas

- Obter aprovação para o plano em docs/agent/plans/2026-05-13-ledger-wallet-extraction-execution-plan.md.

## Observações

A extração do core deve ser incremental.

Não iniciar extração antes de validar que backend e frontend funcionam dentro do monorepo ou antes de documentar claramente as pendências.

A primeira extração real deve ser Ledger/Wallet, mas somente depois do inventário e de um plano curto aprovado.
