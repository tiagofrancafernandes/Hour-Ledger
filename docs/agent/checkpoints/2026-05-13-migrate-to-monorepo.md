# Checkpoint — Migrar para monorepo

## Status atual

Parcialmente executado.

## Estrutura confirmada pelo proprietário do projeto

- Backend em `apps/hl-drive-api`
- Frontend em `apps/hl-drive-web`

## Última milestone concluída

- Milestone 1 — Estrutura base
- Milestone 2 — Mover backend (confirmado em apps/hl-drive-api)
- Milestone 3 — Mover frontend (confirmado em apps/hl-drive-web)

## Próxima ação

Executar a validação da migração para monorepo (Etapa 2 de EXECUTION.md).

## Pendências imediatas

- Confirmar comandos reais do backend.
- Confirmar comandos reais do frontend.
- Confirmar gerenciador de pacotes do frontend.
- Rodar testes backend, se existirem.
- Rodar build frontend, se possível.
- Criar relatório em `docs/agent/reports/2026-05-13-monorepo-migration-validation.md`.

## Observação

Este plano não deve ser executado novamente do zero.

Após a validação mínima da migração, seguir para:

`docs/agent/plans/2026-05-13-extract-hl-core-from-current-app.md`
