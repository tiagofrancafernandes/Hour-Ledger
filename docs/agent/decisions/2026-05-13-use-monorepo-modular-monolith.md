# Decisão — Usar monorepo com modular monolith

## Contexto

O Hour Ledger está evoluindo de um sistema único de controle de horas para um ecossistema com HL Core, HL Drive, HL Consulting e futuros produtos.

## Decisão

Adotar monorepo com modular monolith.

## Motivos

- O domínio ainda está amadurecendo.
- O core será compartilhado entre produtos.
- Backend e frontend continuarão separados por app.
- A equipe precisa de refactor rápido e seguro.
- Microservices seriam complexidade prematura.

## Consequências positivas

- Refactor mais simples.
- Compartilhamento de código controlado.
- Testes integrados mais fáceis.
- Evolução incremental.

## Consequências negativas

- Repositório maior.
- Exige disciplina de boundaries.
- CI precisa ser bem organizado.

## Regras associadas

- Core não depende de produtos.
- Produtos dependem do core.
- Não criar packages publicados prematuramente.
