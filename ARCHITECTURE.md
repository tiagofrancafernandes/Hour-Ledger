# ARCHITECTURE.md

## Visão geral

O Hour Ledger é um ecossistema modular baseado em:

- monorepo;
- modular monolith;
- HL Core compartilhado;
- produtos especializados;
- multi tenancy;
- ledger/wallet;
- backend e frontend separados.

## Estrutura principal

```txt
apps/
  hl-drive-api/
  hl-drive-web/
  hl-consulting-api/
  hl-consulting-web/

packages/
  backend/
    core/
    ledger/
    tenancy/
    auth/
    invitations/
    preferences/
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

## Regra de dependência

Produtos podem depender do core.

O core não pode depender de produtos.

```txt
Product -> Core
Core -X-> Product
```

## Ledger

Saldo é derivado das movimentações.

Não existe edição direta de saldo.

Correções são feitas por movimentações compensatórias.

## Multi tenancy

Modelo planejado:

- identidade global;
- dados tenantizados;
- PostgreSQL schemas por tenant;
- contexto ativo de tenant/produto.
