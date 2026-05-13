# Execução dos planos com agente de IA

## Premissa confirmada

Os projetos já estão nos locais corretos:

- Backend: `apps/hl-drive-api`
- Frontend: `apps/hl-drive-web`

## Regra principal

Execute uma etapa por vez.

Não misture validação de monorepo com extração de core.

Não peça ao agente para executar todos os planos de uma vez.

## Ordem oficial

### Etapa 1 — Atualizar plano de migração

Usar:

`docs/agent/prompts/01-update-monorepo-plan.md`

### Etapa 2 — Validar migração para monorepo

Usar:

`docs/agent/prompts/02-validate-monorepo-migration.md`

Resultado esperado:

`docs/agent/reports/2026-05-13-monorepo-migration-validation.md`

### Etapa 3 — Executar Milestone 0 da extração do core

Usar:

`docs/agent/prompts/03-execute-core-extraction-milestone-0.md`

Resultado esperado:

`docs/agent/reports/2026-05-13-milestone-0-structure-validation.md`

### Etapa 4 — Executar Milestone 1 da extração do core

Usar:

`docs/agent/prompts/04-execute-core-extraction-milestone-1.md`

Resultado esperado:

`docs/agent/reports/2026-05-13-current-app-inventory.md`

### Etapa 5 — Planejar extração de Ledger/Wallet

Usar:

`docs/agent/prompts/05-plan-ledger-wallet-extraction.md`

Resultado esperado:

`docs/agent/plans/2026-05-13-ledger-wallet-extraction-execution-plan.md`

### Etapa 6 — Revisão humana

Antes de executar a extração real, revisar manualmente:

`docs/agent/plans/2026-05-13-ledger-wallet-extraction-execution-plan.md`

Aprovar, ajustar ou rejeitar.

Não pule esta etapa.

### Etapa 7 — Executar extração de Ledger/Wallet

Usar:

`docs/agent/prompts/06-execute-ledger-wallet-extraction-after-approval.md`

Somente após aprovação explícita.

Resultado esperado:

`docs/agent/reports/2026-05-13-ledger-wallet-extraction-report.md`

## Resumo visual

```txt
1. Atualizar plano antigo
2. Validar monorepo
3. Milestone 0 — validar estrutura da extração
4. Milestone 1 — inventário
5. Milestone 2 — planejar Ledger/Wallet
6. Revisão humana
7. Milestone 3 — executar Ledger/Wallet
```

## Regra de parada

Se qualquer etapa encontrar problema estrutural relevante:

1. parar;
2. registrar no relatório;
3. atualizar checkpoint;
4. não avançar para a próxima etapa.
