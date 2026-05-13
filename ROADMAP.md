# ROADMAP.md

## Fase 0 — Organização

- Criar monorepo.
- Migrar backend atual para `apps/hl-drive-api`.
- Migrar frontend atual para `apps/hl-drive-web`.
- Criar `AGENTS.md`, `CLAUDE.md`, `CONVENTIONS.md`.
- Criar documentação inicial em `docs/`.

## Fase 1 — Modularização interna

- Separar domínios no backend.
- Isolar Ledger/Wallet.
- Isolar Auth.
- Isolar Preferences.
- Isolar Invitations.
- Isolar Drive.
- Criar boundaries explícitos.

## Fase 2 — HL Drive beta

- Ajustar timezone.
- Fixar idioma pt-BR.
- Ocultar invoices.
- Desativar descontos no beta.
- Configurar métodos de pagamento offline.
- Garantir recuperação de conta.
- Garantir permissões mínimas para usuário auto registrado.

## Fase 3 — Multi instrutor

- Criar vínculo aluno × instrutor.
- Criar convites.
- Aceitar/rejeitar convites.
- Selecionar instrutor ativo.
- Exibir recursos conforme contexto.
- Revogar permissões ao remover vínculo.

## Fase 4 — Multi tenancy

- Separar identidade global.
- Separar dados tenantizados.
- Implementar tenant resolver.
- Implementar PostgreSQL schemas por tenant.
- Testar isolamento.

## Fase 5 — Evolução da wallet

- Tipos de transação:
    - purchase;
    - transfer;
    - bonus;
    - refund;
    - expiration;
    - adjustment;
    - consumption.
- WalletPolicy:
    - allow_transfer;
    - allow_negative_balance;
    - allow_purchase;
    - allow_expiration.
- Transferência entre wallets.
- Crédito manual.
- Crédito expirável.
- Promoções.
- Bônus.
- Compensações.

## Fase 6 — Novos produtos

- HL Consulting.
- Reaproveitamento do HL Core.
- Reaproveitamento do Ledger.
- Reaproveitamento de frontend core/ui.
