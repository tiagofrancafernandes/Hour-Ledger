# Relatório — Milestone 1: Inventário de Domínio e Código

Data: 2026-05-13
Projeto: Hour Ledger Ecosystem
Fase: Extração do HL Core

## 1. Visão Geral

O sistema atual apresenta uma arquitetura altamente genérica, facilitando a extração do HL Core. Embora o produto atual seja comercializado como "HL Drive", o código base não contém termos específicos de autoescolas (como "instrutor", "aluno" ou "veículo"), utilizando em vez disso termos abstratos como "Client", "User", "Wallet" e "Timer".

## 2. Inventário Backend (apps/hl-drive-api)

### 2.1. Modelos (Models)

| Item | Classificação | Justificativa |
| :--- | :--- | :--- |
| `User` | `CORE_CANDIDATE` | Autenticação, perfis e roles (Spatie). |
| `Client` | `CORE_CANDIDATE` | Representa o Tenant ou Conta principal. |
| `Wallet` | `CORE_CANDIDATE` | Recurso central de saldo de horas. |
| `LedgerEntry` | `CORE_CANDIDATE` | Registro imutável de transações. |
| `Timer` / `TimerCycle` | `CORE_CANDIDATE` | Infraestrutura de tracking de tempo. |
| `Invoice` / `InvoiceItem` | `CORE_CANDIDATE` | Faturamento e cobrança. |
| `CreditPurchase` / `Payment` | `CORE_CANDIDATE` | Fluxo de aquisição de créditos. |
| `Tag` | `CORE_CANDIDATE` | Categorização flexível. |
| `ProductService` | `CORE_CANDIDATE` | Catálogo para faturamento. |
| `ImportPlan` / `Row` | `CORE_CANDIDATE` | Sistema de importação de dados. |

### 2.2. Controladores (Controllers/Api)

| Item | Classificação | Justificativa |
| :--- | :--- | :--- |
| `AuthController` | `CORE_CANDIDATE` | Login, Registro e Recuperação. |
| `WalletController` | `CORE_CANDIDATE` | Gestão de carteiras. |
| `LedgerEntryController` | `CORE_CANDIDATE` | Visualização de extrato. |
| `TimerController` | `CORE_CANDIDATE` | Controle de sessões de tempo. |
| `InvoiceController` | `CORE_CANDIDATE` | Gestão de faturas. |
| `ReportController` | `CORE_CANDIDATE` | Relatórios de saldo e uso. |

## 3. Inventário Frontend (apps/hl-drive-web)

### 3.1. Estado e Lógica (Stores/Composables)

| Item | Classificação | Justificativa |
| :--- | :--- | :--- |
| Store: `auth` | `CORE_CANDIDATE` | Sessão do usuário. |
| Store: `wallet` | `CORE_CANDIDATE` | Dados de saldo. |
| Store: `timer` | `CORE_CANDIDATE` | Estado do cronômetro. |
| Composable: `useApi` | `CORE_CANDIDATE` | Wrapper do Axios. |
| Composable: `usePermissions` | `CORE_CANDIDATE` | Lógica de ACL. |

### 3.2. Componentes (tw-ui)

| Item | Classificação | Justificativa |
| :--- | :--- | :--- |
| `CButton`, `CInput`, etc. | `CORE_CANDIDATE` | Design System básico. |
| `UIPageHeader` | `CORE_CANDIDATE` | Layout comum. |

## 4. Itens DRIVE_SPECIFIC

Não foram encontrados componentes de código (Modelos, Controllers ou Views) que contenham regras de negócio exclusivas do domínio de instrutores de direção. O "HL Drive" atualmente é uma aplicação do "Hour Ledger Core" com branding e configurações específicas (que provavelmente vivem em `.env` ou seeder).

## 5. Conclusão

A Milestone 1 confirma que a extração pode ser feita quase em sua totalidade para o `packages/backend/core` e outros pacotes core, pois a base já é agnóstica a produto.

Próximo passo: **Milestone 2 — Planejar extração de Ledger / Wallet**.

---
**Agente:** Antigravity (AI Agent)
