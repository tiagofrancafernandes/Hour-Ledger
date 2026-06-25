# Task list — Extração progressiva do HL Core

## Regra de execução

Executar uma milestone por vez.

Não avançar para a próxima milestone sem atualizar o checkpoint e gerar relatório.

## Fase A — Validação da migração para monorepo

- [ ] Confirmar que `apps/hl-drive-api` existe e contém o backend.
- [ ] Confirmar que `apps/hl-drive-web` existe e contém o frontend.
- [ ] Identificar comandos reais de instalação/teste/build do backend.
- [ ] Identificar comandos reais de instalação/teste/build do frontend.
- [ ] Executar testes/build apenas se for seguro.
- [ ] Atualizar checkpoint de migração.
- [ ] Criar relatório em `docs/agent/reports/2026-05-13-monorepo-migration-validation.md`.

## Fase B — Milestone 0 da extração do core

- [ ] Confirmar estrutura atual.
- [ ] Confirmar gerenciador de pacotes do frontend.
- [ ] Confirmar scripts disponíveis.
- [ ] Confirmar arquivos de ambiente de exemplo.
- [ ] Atualizar checkpoint de extração do core.
- [ ] Criar relatório em `docs/agent/reports/2026-05-13-milestone-0-structure-validation.md`.

## Fase C — Milestone 1 da extração do core

- [ ] Mapear models do backend.
- [ ] Mapear controllers do backend.
- [ ] Mapear services/actions/use cases do backend.
- [ ] Mapear requests/validators do backend.
- [ ] Mapear policies/middlewares do backend.
- [ ] Mapear migrations relevantes.
- [ ] Mapear testes existentes.
- [ ] Mapear páginas do frontend.
- [ ] Mapear componentes do frontend.
- [ ] Mapear composables/stores/plugins do frontend.
- [ ] Classificar itens como `CORE_CANDIDATE`, `DRIVE_SPECIFIC` ou `UNCLEAR`.
- [ ] Criar relatório em `docs/agent/reports/2026-05-13-current-app-inventory.md`.
- [ ] Atualizar checkpoint.

## Fase D — Milestone 2 da extração do core

- [ ] Ler inventário.
- [ ] Identificar arquivos candidatos para `packages/backend/ledger`.
- [ ] Identificar arquivos candidatos para `packages/frontend/wallet`.
- [ ] Identificar arquivos que devem permanecer no HL Drive.
- [ ] Criar plano curto de extração de Ledger/Wallet.
- [ ] Listar riscos.
- [ ] Listar testes necessários.
- [ ] Aguardar aprovação antes de editar código.

## Fase E — Milestone 3 da extração do core

- [ ] Executar somente após aprovação da Milestone 2.
- [ ] Mover apenas código genérico de Ledger/Wallet.
- [ ] Manter regras de aula/instrutor/aluno no HL Drive.
- [ ] Ajustar imports/autoloads mínimos.
- [ ] Executar testes relevantes.
- [ ] Atualizar checkpoint.
- [ ] Criar relatório de extração.
