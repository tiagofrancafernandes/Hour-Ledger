# Multi tenancy

## Modelo planejado

- Identidade global.
- Dados tenantizados.
- PostgreSQL schemas separados por tenant.
- Contexto ativo de tenant/produto.
- Usuário podendo participar de múltiplos tenants.

## Regra

O tenant deve ser resolvido antes de acessar dados tenantizados.

## Separação

Global:

- users;
- auth;
- profile;
- preferences.

Tenantizado:

- wallets;
- students;
- lessons;
- schedules;
- payments;
- reports.
