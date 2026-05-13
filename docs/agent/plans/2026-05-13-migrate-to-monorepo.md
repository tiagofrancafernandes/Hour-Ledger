# Plano — Migrar para monorepo

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

- Criar pastas principais.
- Criar arquivos raiz.
- Adicionar documentação inicial.

### Milestone 2 — Mover backend

- Copiar backend atual.
- Ajustar caminhos mínimos.
- Rodar testes existentes.

### Milestone 3 — Mover frontend

- Copiar frontend atual.
- Ajustar caminhos mínimos.
- Rodar build.

### Milestone 4 — Validar

- Subir ambiente local.
- Validar login.
- Validar wallet.
- Validar fluxo principal.

## Critérios de aceite

- Backend sobe localmente.
- Frontend sobe localmente.
- Login funciona.
- Fluxo principal de wallet continua funcionando.
- Nenhuma regra de negócio foi alterada.
