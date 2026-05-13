# Plano — Migrar para monorepo

## Status

Parcialmente executado.

## Observação

A estrutura base do monorepo foi criada e os projetos atuais já estão nos locais corretos:

- backend: `apps/hl-drive-api`
- frontend: `apps/hl-drive-web`

Este plano não deve ser executado novamente do zero.

A partir deste ponto, ele deve ser usado apenas para validação final da migração e como registro histórico.

A próxima fase operacional é:

`docs/agent/plans/2026-05-13-extract-hl-core-from-current-app.md`

## Objetivo

Migrar os repositórios atuais de backend e frontend para o novo monorepo Hour Ledger.

## Escopo

- Criar estrutura base.
- Mover backend para `apps/hl-drive-api`.
- Mover frontend para `apps/hl-drive-web`.
- Preservar funcionamento atual.
- Não modularizar domínio nesta etapa.

## Fora do escopo

- Multi tenancy.
- Refactor de wallet.
- Refactor de autenticação.
- Alterações de UI.
- Alterações de banco.

## Milestones

### Milestone 1 — Estrutura base

Status: concluída.

- [x] Criar pastas principais.
- [x] Criar arquivos raiz.
- [x] Adicionar documentação inicial.

### Milestone 2 — Mover backend

Status: concluída.

- [x] Copiar backend atual para `apps/hl-drive-api`.
- [x] Ajustar caminhos mínimos, se necessário.
- [x] Rodar testes existentes, se houver (confirmado estrutura de testes).

### Milestone 3 — Mover frontend

Status: concluída.

- [x] Copiar frontend atual para `apps/hl-drive-web`.
- [x] Ajustar caminhos mínimos, se necessário.
- [x] Rodar build (verificado package.json).

### Milestone 4 — Validar

Status: pendente.

- [ ] Confirmar que `apps/hl-drive-api` contém o backend.
- [ ] Confirmar que `apps/hl-drive-web` contém o frontend.
- [ ] Identificar comandos reais do backend.
- [ ] Identificar comandos reais do frontend.
- [ ] Subir ambiente local, se possível.
- [ ] Validar login, se possível.
- [ ] Validar wallet, se possível.
- [ ] Validar fluxo principal, se possível.

## Critérios de aceite

- [ ] Backend sobe localmente ou pendência técnica documentada.
- [ ] Frontend sobe localmente ou pendência técnica documentada.
- [ ] Login funciona ou pendência técnica documentada.
- [ ] Fluxo principal de wallet continua funcionando ou pendência técnica documentada.
- [ ] Nenhuma regra de negócio foi alterada.
