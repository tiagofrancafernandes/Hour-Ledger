# Plano — Extração progressiva do HL Core a partir da aplicação atual

## Status

Planejado.

## Contexto

Os projetos atuais de backend e frontend já foram clonados e movidos para dentro do monorepo Hour Ledger.

Estrutura atual confirmada:

```txt
apps/
  hl-drive-api/
  hl-drive-web/
```

O sistema atual nasceu como uma aplicação funcional de controle de horas, ainda sem multi tenancy completo e ainda sem separação formal entre core compartilhado e produto específico.

A partir de agora, o objetivo é separar progressivamente o que é reutilizável e transformar em HL Core, mantendo o HL Drive funcionando durante todo o processo.

## Objetivo

Separar progressivamente do sistema atual tudo que é genérico, reutilizável e independente do domínio de instrutores de direção, movendo esses recursos para módulos compartilhados do HL Core.

O objetivo não é reescrever a aplicação.

O objetivo é criar boundaries claros e permitir que HL Drive, HL Consulting e futuros produtos reutilizem capacidades comuns.

## Regra principal

O HL Core não pode conhecer HL Drive.

Permitido:

```txt
HL Drive -> HL Core
HL Drive -> Ledger
HL Drive -> Auth
HL Drive -> Preferences
HL Drive -> Invitations
HL Drive -> Tenancy
```

Proibido:

```txt
HL Core -> HL Drive
Ledger -> HL Drive
Auth -> HL Drive
Preferences -> HL Drive
Invitations -> HL Drive
Tenancy -> HL Drive
```

## Critério para mover algo para o HL Core

Antes de mover qualquer código para `packages/`, validar:

1. Este recurso serve para mais de um produto?
2. Este recurso faz sentido para HL Consulting?
3. Este recurso pode existir sem mencionar instrutor de direção?
4. Este recurso pode existir sem mencionar aluno de direção?
5. Este recurso pode existir sem mencionar aula prática?
6. Este recurso pode existir sem mencionar CNH, veículo ou agenda de aula?
7. Este recurso pode ser testado isoladamente?
8. O nome do módulo continua fazendo sentido fora do HL Drive?

Se a resposta indicar dependência do domínio de instrutores de direção, o código deve permanecer em `apps/hl-drive-*`.

## Estrutura-alvo inicial

```txt
apps/
  hl-drive-api/
  hl-drive-web/

packages/
  backend/
    core/
    ledger/
    auth/
    preferences/
    invitations/
    tenancy/
    notifications/
    audit/

  frontend/
    core/
    ui/
    i18n/
    auth/
    tenancy/
    wallet/
    preferences/
```

## Ordem oficial de extração

1. Ledger / Wallet
2. Auth
3. Preferences
4. Invitations
5. Tenancy
6. Notifications
7. Audit
8. Frontend UI compartilhado
9. Frontend composables compartilhados
10. Ajustes finais de boundaries

---

## Milestone 0 — Validar estrutura atual

### Objetivo

Garantir que backend e frontend estejam nas pastas corretas antes de iniciar qualquer extração.

### Tarefas

- [ ] Confirmar que o backend está em `apps/hl-drive-api`.
- [ ] Confirmar que o frontend está em `apps/hl-drive-web`.
- [ ] Confirmar que ambos ainda sobem localmente, se possível.
- [ ] Confirmar que nenhuma regra funcional foi alterada apenas por mover os projetos.
- [ ] Confirmar scripts atuais de instalação, build e teste.
- [ ] Registrar comandos necessários para rodar backend e frontend.
- [ ] Criar relatório em `docs/agent/reports/2026-05-13-milestone-0-structure-validation.md`.
- [ ] Atualizar checkpoint.

---

## Milestone 1 — Inventário de domínio e código

### Objetivo

Mapear o que existe hoje antes de mover código.

### Resultado esperado

Criar relatório em:

`docs/agent/reports/2026-05-13-current-app-inventory.md`

### Classificação obrigatória

Cada item encontrado deve ser classificado como:

- `CORE_CANDIDATE`
- `DRIVE_SPECIFIC`
- `UNCLEAR`

### Critérios de aceite

- Inventário criado.
- Itens reutilizáveis identificados.
- Itens específicos do Drive identificados.
- Itens incertos marcados como `UNCLEAR`.
- Nenhum código movido ainda.
- Checkpoint atualizado.

---

## Milestone 2 — Planejar extração de Ledger / Wallet

### Objetivo

Preparar a extração do domínio de ledger/wallet como primeiro módulo core, sem aplicar mudanças imediatamente.

### Tarefas

- [ ] Ler o inventário da Milestone 1.
- [ ] Identificar arquivos candidatos a mover para `packages/backend/ledger`.
- [ ] Identificar arquivos candidatos a mover para `packages/frontend/wallet`.
- [ ] Identificar arquivos que devem permanecer no HL Drive.
- [ ] Listar riscos.
- [ ] Listar testes necessários.
- [ ] Criar plano curto de execução da extração.
- [ ] Aguardar aprovação antes de editar código.

---

## Milestone 3 — Executar extração de Ledger / Wallet

### Pré-requisito

A Milestone 2 deve estar aprovada.

### Destino backend

`packages/backend/ledger/`

### Destino frontend

`packages/frontend/wallet/`

### Critérios de aceite

- Código genérico de wallet isolado.
- Regras específicas de aula permanecem no HL Drive.
- Testes de wallet continuam passando ou pendências documentadas.
- Saldo continua derivado do ledger.
- Nenhuma edição direta de saldo foi introduzida.
- Operações críticas continuam usando transação de banco.
- Checkpoint atualizado.
- Relatório criado.

## Milestones futuras

Após Ledger/Wallet, seguir nesta ordem:

1. Auth
2. Preferences
3. Invitations
4. Tenancy
5. Notifications
6. Audit
7. Frontend UI compartilhado
8. Frontend composables compartilhados
9. Ajustes finais de boundaries

Cada milestone futura deve ter plano próprio antes de alterar código.
