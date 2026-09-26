# 📚 Índice Geral de Documentação do Ecossistema Hour Ledger

Bem-vindo ao índice central da documentação do **Hour Ledger Ecosystem**.

Este documento organiza todos os arquivos de documentação por grupo e tema, com links relativos diretos, servindo como guia de navegação para desenvolvedores e agentes de IA.

---

## 🧭 1. Ponto de Partida e Visão Geral

Documentos essenciais para entender a visão, propósito e limites do projeto antes de propor ou alterar qualquer linha de código:

- [00-START-HERE.md](./architecture/00-START-HERE.md) — Guia de onboarding e primeiro contato com o projeto.
- [01-CONSTITUTION.md](./architecture/01-CONSTITUTION.md) — Princípios inegociáveis e diretrizes fundamentais da plataforma.
- [02-VISION.md](./architecture/02-VISION.md) — Visão de negócio, planos imediatos e expansão futura.
- [03-CURRENT-DIRECTION.md](./architecture/03-CURRENT-DIRECTION.md) — Direcionamento atual e foco de execução.
- [99-GLOSSARY.md](./architecture/99-GLOSSARY.md) — Glossário de termos e vocabulário ubíquo do domínio.

---

## 🏛️ 2. Arquitetura e Estratégia de Engenharia

Decisões de design, isolamento de dados e limites arquiteturais:

- [04-DECISION-FRAMEWORK.md](./architecture/04-DECISION-FRAMEWORK.md) — Critérios e matriz para tomada de decisões arquiteturais.
- [05-BOUNDARIES.md](./architecture/05-BOUNDARIES.md) — Fronteiras estritas entre o HL Core e produtos verticais (Drive, Consulting).
- [06-ARCHITECTURE-FREEZE.md](./architecture/06-ARCHITECTURE-FREEZE.md) — Política de congelamento de arquitetura durante lançamentos de versão.
- [07-A-TENANT-SCHEMA-STRATEGY.md](./architecture/07-A-TENANT-SCHEMA-STRATEGY.md) — **Estratégia de isolamento por Schemas PostgreSQL** (`tenant_{id}_{env}` via `search_path`).
- [07-MULTI-TENANCY.md](./architecture/07-MULTI-TENANCY.md) — Arquitetura detalhada de multi-tenancy e resolução de contexto.
- [07-TESTING-STRATEGY.md](./architecture/07-TESTING-STRATEGY.md) — Filosofia e pirâmide de testes automatizados do ecossistema.
- [08-REFACTORING-POLICY.md](./architecture/08-REFACTORING-POLICY.md) — Diretrizes para refatorações seguras e evoluções incrementais.
- [instructor-student-link.md](./architecture/instructor-student-link.md) — Arquitetura de vínculo entre alunos e múltiplos instrutores.

---

## 💼 3. Domínio de Negócio e Regras

Especificações detalhadas das entidades e regras dos domínios da plataforma:

### 3.1. Ledger e Carteiras (HL Core)
- [wallet.md](./domain/ledger/wallet.md) — Conceito central da carteira, saldo sempre derivado e imutabilidade.
- [transaction-types.md](./domain/ledger/transaction-types.md) — Tipos de movimentação contábil (`purchase`, `debit`, `credit`, `adjustment`, etc.).
- [wallet-policy.md](./domain/ledger/wallet-policy.md) — Políticas de carteira (`allow_transfer`, `allow_negative_balance`, etc.).

### 3.2. HL Drive (Autoescolas e Instrutores Autônomos)
- [beta-scope.md](./domain/drive/beta-scope.md) — Escopo funcional do HL Drive na fase Beta.
- [instructor-student-link.md](./domain/drive/instructor-student-link.md) — Regras de vínculo, convites, permissões e isolamento por instrutor.

---

## 🚀 4. Produto e Funcionalidades

Notas de lançamento e especificações de features do SaaS:

- [V1-RELEASE-NOTES.md](./V1-RELEASE-NOTES.md) — Release notes consolidado da versão V1.
- [V1-FEATURES-SUMMARY.md](./product/V1-FEATURES-SUMMARY.md) — Resumo de funcionalidades entregues na V1.
- [roadmap.md](./product/hl-drive/roadmap.md) — Roadmap de produto específico do HL Drive.
- [2026-07-06-tenant-selection.md](./features/2026-07-06-tenant-selection.md) — Especificação da seleção e alternância de tenants na UI.

---

## 🛠️ 5. Operações, Setup Local e Deploy

Instruções para configurar, rodar, testar e publicar a aplicação:

- [local-development.md](./operations/local-development.md) — Como configurar e rodar o monorepo localmente.
- [BETA-SETUP-GUIDE.md](./operations/BETA-SETUP-GUIDE.md) — Guia passo a passo de setup para o ambiente Beta.
- [DEPLOYMENT-CHECKLIST.md](./operations/DEPLOYMENT-CHECKLIST.md) — Checklist de pré-deploy, homologação e produção.

---

## 💡 6. Conhecimento Técnico e Referência

Guias profundos de transição técnica e especificações tecnológicas:

- [TECHNICAL-TRANSITION-REPORT.md](./knowledge/TECHNICAL-TRANSITION-REPORT.md) — Relatório detalhado da transição para monorepo e multi-tenancy.
- [TRANSITION-QUICK-REFERENCE.md](./knowledge/TRANSITION-QUICK-REFERENCE.md) — Guia rápido de referência para handover técnico.
- **Guias por Tecnologia**:
  - [Docker](./knowledge/docker/) — Configurações e boas práticas de containers.
  - [Laravel](./knowledge/laravel/) — Padrões de backend e PSR-12.
  - [PostgreSQL](./knowledge/postgres/) — Recursos avançados, schemas e tuning.
  - [Redis](./knowledge/redis/) — Estratégia de cache e sessão.
  - [Testing](./knowledge/testing/) — Boas práticas para PHPUnit e testes de integração.
  - [Vue 3](./knowledge/vue/) — Padrões de composição e reatividade.
  - [Nuxt](./knowledge/nuxt/) — Arquitetura de aplicações frontend.

---

## 🔮 7. Planos Futuros e Débitos Técnicos

Planejamento de médio e longo prazo do ecossistema:

- [ROADMAP.md](./future/ROADMAP.md) — Visão geral das 6 fases do ecossistema.
- [TECHNICAL-DEBT-ROADMAP.md](./future/TECHNICAL-DEBT-ROADMAP.md) — Mapeamento detalhado de débitos técnicos.
- [spikes/INDEX.md](./future/spikes/INDEX.md) — Spikes de extração de pacotes compartilhados (`HL Core` e `Ledger`).
- **Planos e Tarefas do Ecossistema**:
  - [003-01-debitos-tecnicos-e-resiliencia-v1.md](../tasks/plans/doing/003-01-debitos-tecnicos-e-resiliencia-v1.md) — Rate limiting, cache Redis e resiliência (Em andamento).
  - [004-01-evolucao-ledger-wallet-v1.md](../tasks/plans/004-01-evolucao-ledger-wallet-v1.md) — Tipos contábeis, WalletPolicy e transferências (Planejado).
  - [005-01-extracao-hl-core-modularizacao-v1.md](../tasks/plans/005-01-extracao-hl-core-modularizacao-v1.md) — Extração para packages e suporte a HL Consulting (Planejado).
  - [002-01-unificacao-arquivos-planejamento-v1.md](../tasks/plans/done/002-01-unificacao-arquivos-planejamento-v1.md) — Unificação de planejamento (Concluído).

---

## 📜 8. Histórico de Execução

Registros das fases de desenvolvimento já concluídas:

- [INDEX.md](./execution-history/INDEX.md) — Índice completo dos relatórios de fases.
- [BETA-LAUNCH-FINAL-REPORT.md](./execution-history/BETA-LAUNCH-FINAL-REPORT.md) — Relatório do lançamento Beta.
- [BETA-STATUS.md](./execution-history/BETA-STATUS.md) — Status do Beta.
- [EXECUTION-SUMMARY-2026-07-04.md](./execution-history/EXECUTION-SUMMARY-2026-07-04.md) — Resumo executivo de Julho/2026.
- [PROJECT-COMPLETION-SUMMARY.md](./execution-history/PROJECT-COMPLETION-SUMMARY.md) — Resumo de conclusão.
- [PHASE-3/FASE-3-FINAL-REPORT.md](./execution-history/PHASE-3/FASE-3-FINAL-REPORT.md) — Relatório final da Fase 3.
- [PHASE-4/FASE-4-MULTI-TENANCY-FINAL-REPORT.md](./execution-history/PHASE-4/FASE-4-MULTI-TENANCY-FINAL-REPORT.md) — Relatório de segurança da Fase 4.
- [PHASE-4/TAREFA_G_SUMMARY.md](./execution-history/PHASE-4/TAREFA_G_SUMMARY.md) — Resumo dos testes de isolamento multi-tenant.
- [PROGRESSO-ATUAL.txt](./execution-history/PROGRESSO-ATUAL.txt) — Log de execução diária.

---

## 📡 9. Exemplos Práticos de Requisições HTTP (API Requests & cURL)

A pasta `backend/dev-contents/demo-requests/` contém arquivos `.http` prontos para execução no VS Code / PhpStorm / Insomnia / Bruno.

### Arquivos de Demonstração Disponíveis:
- [tenants-demo.http](../backend/dev-contents/demo-requests/tenants-demo.http) — Resolução de tenant e cabeçalhos de contexto.
- [subscriptions-demo.http](../backend/dev-contents/demo-requests/subscriptions-demo.http) — Assinaturas de planos, carência, upload de comprovantes PIX e moderação de admin.
- [lessons-demo.http](../backend/dev-contents/demo-requests/lessons-demo.http) — Agendamentos, aulas e consumo de horas.
- [packages-demo.http](../backend/dev-contents/demo-requests/packages-demo.http) — Criação e compra de pacotes de aulas.

---

### Exemplos com cURL

#### 1. Autenticação e Obtenção de Token
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "instrutor@example.com",
    "password": "password123"
  }'
```

#### 2. Consulta de Saldo com Contexto de Tenant
```bash
curl -X GET http://localhost:8000/api/wallets/1/balance \
  -H "Authorization: Bearer <SEU_TOKEN_AQUI>" \
  -H "X-Tenant-ID: 1" \
  -H "Accept: application/json"
```

#### 3. Registro de Lançamento de Crédito no Ledger
```bash
curl -X POST http://localhost:8000/api/ledger-entries \
  -H "Authorization: Bearer <SEU_TOKEN_AQUI>" \
  -H "X-Tenant-ID: 1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "wallet_id": 1,
    "type": "credit",
    "hours": 10.0,
    "title": "Aquisição de Pacote Inicial"
  }'
```

#### 4. Upload de Comprovante de Pagamento PIX Offline
```bash
curl -X POST http://localhost:8000/api/subscription/upload-receipt \
  -H "Authorization: Bearer <SEU_TOKEN_AQUI>" \
  -H "X-Tenant-ID: 1" \
  -H "Accept: application/json" \
  -F "receipt=@/caminho/do/comprovante.png" \
  -F "notes=Pagamento realizado via PIX em 25/09 às 14h"
```

---

> **Regra de Manutenção Obrigatória**:
> Sempre que novas rotas, entidades ou comportamentos forem adicionados ou alterados no projeto, a respectiva documentação em `docs/` e o índice [docs/README.md](./README.md) **devem ser imediatamente atualizados**, incluindo exemplos em `.http` e `curl`.
