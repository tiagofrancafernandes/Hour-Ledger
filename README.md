# Hour Ledger Ecosystem

Monorepo do ecossistema Hour Ledger.

Leia docs/architecture/00-START-HERE.md e siga todas as referências indicadas antes de executar cada tarefa.

Para entender bem o objetivo desse projeto, leia docs/architecture/02-VISION.md pois nele tem definição de planos para agora e planos futuro.

---

## Conceito

O Hour Ledger Ecosystem é uma plataforma modular baseada em:

- HL Core;
- HL Drive;
- HL Consulting;
- futuros produtos;
- ledger/wallet;
- multi tenancy;
- backend e frontend separados;
- modular monolith;
- evolução incremental.

## Estrutura

```txt
apps/       Aplicações executáveis
packages/   Módulos compartilhados
docs/       Documentação operacional, domínio, arquitetura e agentes
tooling/    Configurações compartilhadas
docker/     Infra local
```

Este zip contém apenas a estrutura e arquivos de documentação/configuração inicial.
O código real das aplicações deve ser movido depois para `apps/`.
