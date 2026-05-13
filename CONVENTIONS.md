# CONVENTIONS.md

## Princípios gerais

- Código simples, explícito e testável.
- Preferir clareza a abstração prematura.
- Evitar classes genéricas sem responsabilidade clara.
- Evitar helpers globais sem necessidade.
- Manter baixo acoplamento entre módulos.
- Respeitar boundaries de domínio.

## Backend

Stack principal:

- PHP;
- Laravel;
- PostgreSQL;
- Redis;
- filas quando necessário.

## Laravel

- Seguir a estrutura existente do projeto.
- Preferir Form Requests para validação quando aplicável.
- Preferir Policies/Gates quando a autorização for explícita.
- Preferir Enums nativos para domínios fechados.
- Evitar Services genéricos com nomes vagos.
- Usar Actions ou Use Cases quando houver operação de negócio clara.
- Não colocar regra de negócio complexa em Controllers.
- Controllers devem orquestrar entrada/saída, não conter domínio.
- Usar transações de banco para operações ledger críticas.
- Não editar saldo diretamente.
- Toda alteração de saldo deve gerar movimentação ledger.

## Frontend

Stack principal:

- Vue 3;
- Nuxt;
- composables;
- componentes reutilizáveis;
- stores apenas quando necessário.

## Vue/Nuxt

- Componentes pequenos e explícitos.
- Evitar estado global quando estado local ou composable resolver.
- Não introduzir biblioteca de UI sem autorização.
- Separar componentes genéricos de componentes de produto.
- Componentes do HL Drive não devem ir para `packages/frontend/ui` se carregarem regra específica de instrutor/aula.
- Composables genéricos devem ficar em `packages/frontend/core`.
- Composables específicos do Drive devem ficar no app ou módulo do Drive.

## Testes

- Testar comportamento, não implementação interna.
- Para API, preferir testes de Feature.
- Para domínio crítico, criar testes unitários ou de integração.
- Ledger/wallet deve ter cobertura forte.
- Multi tenancy deve ter testes de isolamento.
- Permissões derivadas de vínculo devem ter testes específicos.

## Banco de dados

- Usar UUID para entidades expostas publicamente.
- Evitar expor IDs sequenciais em rotas públicas.
- Em multi tenancy, separar identidade global de dados tenantizados.
- Não apagar histórico transacional.
- Para histórico financeiro/ledger, preferir imutabilidade e compensação.

## Regra obrigatoria de code style (Code Guidelines)

Toda alteracao de codigo deve seguir **obrigatoriamente** o documento:

- `UNIVERSAL-CODE-STYLE-RULES.md`

Em caso de conflito entre preferencia do assistente e o documento, prevalece `UNIVERSAL-CODE-STYLE-RULES.md`.
