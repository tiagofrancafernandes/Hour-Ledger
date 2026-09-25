# Plano: Sistema de Planos, Carência, Bloqueio Read-Only e Pagamentos (SaaS Subscriptions)

**Prioridade:** Alta  
**Data de Início:** 2026-09-25  
**Responsável:** Equipe Hour Ledger Ecosystem  

---

## 1. Objetivo

Implementar o módulo completo de **Planos e Assinaturas (SaaS Subscriptions)** para o ecossistema Hour Ledger / HL Drive, permitindo que todo instrutor (Tenant) possua um plano de assinatura do sistema, com regras automatizadas para:
1. **Período de Carência Configurável:** Tolerância de atraso de até N dias (padrão 5 dias, ajustável via config da API `config('billing.grace_period_days')`).
2. **Alertas e Banners de Cobrança:** Exibição de banner no painel informando erro/atraso no pagamento enquanto estiver dentro da carência ou período estendido manualmente pelo Super Admin, direcionando para a tela de pagamento.
3. **Modo Somente Leitura (Read-Only):** Bloqueio automático de mutações (POST, PUT, PATCH, DELETE) caso a carência expire sem prorrogação, mantendo apenas a leitura/consulta de históricos.
4. **Pagamento Online e Comprovante PIX Offline:** Geração de dados de pagamento (PIX copia e cola, PicPay, etc.) e upload de comprovante de pagamento offline para análise.
5. **Painel Super Admin:** Moderação de pagamentos (aprovar com ativação do ciclo ou rejeitar com justificativa) e prorrogação manual da carência.
6. **Histórico de Pagamentos:** Consulta ordenada pelos pagamentos mais recentes primeiro.
7. **Seeders Completos:** Cobertura de todos os cenários (em dia, carência ativa, prorrogado por admin, bloqueado/read-only, sob análise com comprovante).

---

## 2. Modelagem e Arquitetura

### Entidades no Banco de Dados (Schema Público / SaaS Global)

1. **`plans`**:
   - `id`: bigIncrements
   - `name`: string (ex: 'Instrutor Autônomo Mensal')
   - `slug`: string (unique)
   - `description`: text nullable
   - `price`: decimal(10,2) (ex: 79.90)
   - `interval_days`: integer (padrão: 30)
   - `is_active`: boolean (default: true)
   - `created_at`, `updated_at`

2. **`tenant_subscriptions`**:
   - `id`: bigIncrements
   - `tenant_id`: foreignId -> `tenants` (cascade)
   - `plan_id`: foreignId -> `plans` (restrict)
   - `status`: enum/string (`active`, `past_due`, `suspended`, `canceled`)
   - `current_period_start`: datetime
   - `current_period_end`: datetime
   - `grace_period_ends_at`: datetime nullable
   - `extended_until`: datetime nullable
   - `price`: decimal(10,2)
   - `notes`: text nullable
   - `created_at`, `updated_at`

3. **`subscription_payments`**:
   - `id`: bigIncrements
   - `tenant_subscription_id`: foreignId -> `tenant_subscriptions` (cascade)
   - `tenant_id`: foreignId -> `tenants` (cascade)
   - `user_id`: foreignId -> `users` (quem gerou/pagou)
   - `amount`: decimal(10,2)
   - `payment_method`: string (`pix_online`, `picpay`, `pix_offline`, `credit_card`)
   - `status`: string (`pending`, `under_review`, `approved`, `rejected`)
   - `due_date`: date
   - `paid_at`: datetime nullable
   - `pix_code`: text nullable
   - `receipt_path`: string nullable
   - `rejection_reason`: text nullable
   - `reviewed_by`: foreignId -> `users` nullable
   - `reviewed_at`: datetime nullable
   - `created_at`, `updated_at`

---

## 3. Escopo de Trabalho (Checklist de Tarefas)

### Épica 1: Configuração e Camada de Banco de Dados
- [x] **Task 1.1**: Criar arquivo `config/billing.php` com configurações de carência (`grace_period_days` = 5), dados para pagamento PIX/PicPay e mensagens padrão.
- [x] **Task 1.2**: Criar migrations para `plans`, `tenant_subscriptions` e `subscription_payments`.
- [x] **Task 1.3**: Criar Models (`Plan`, `TenantSubscription`, `SubscriptionPayment`) com relacionamentos, casts, enums e métodos auxiliares de status (`isPastDue()`, `isInGracePeriod()`, `isReadOnly()`, `canPerformMutations()`).

### Épica 2: Middleware de Enforce e Serviço de Assinatura
- [x] **Task 2.1**: Implementar `SubscriptionService` com métodos para calcular status, carência, gerar intenção de pagamento, processar upload de comprovante, prorrogar prazo, aprovar e rejeitar pagamento.
- [x] **Task 2.2**: Criar middleware `EnforceSubscriptionStatusMiddleware`:
  - Se a assinatura estiver em modo somente leitura (`isReadOnly()`), interceptar requisições que alterem dados (`POST`, `PUT`, `PATCH`, `DELETE`) em rotas de negócio (aulas, pacotes, alunos, carteiras), retornando status 402 Payment Required.
  - Liberar rotas `GET` para consulta de dados históricos e rotas de `/api/subscription/*` para pagamento.
- [x] **Task 2.3**: Registrar middleware no Kernel / bootstrap.

### Épica 3: Controllers e Rotas da API
- [x] **Task 3.1**: Criar rotas do Instrutor (`/api/subscription`):
  - `GET /api/subscription`: resumo da assinatura, status, flags de banner (`show_grace_banner`, `is_read_only`), dias restantes.
  - `GET /api/subscription/payments`: histórico de pagamentos ordenado por `created_at DESC` (últimos pagamentos primeiro).
  - `POST /api/subscription/pay`: gerar pagamento online (PIX cópia e cola).
  - `POST /api/subscription/upload-receipt`: upload do comprovante de pagamento PIX offline (mudando para `under_review`).
- [x] **Task 3.2**: Criar rotas do Super Admin (`/api/admin/subscriptions` e `/api/admin/payments`):
  - `GET /api/admin/subscriptions`: listar assinaturas de todos os tenants com filtros.
  - `GET /api/admin/payments`: listar pagamentos para moderação.
  - `POST /api/admin/payments/{id}/approve`: aprovar pagamento, avançando o ciclo e ativando a assinatura.
  - `POST /api/admin/payments/{id}/reject`: rejeitar pagamento com justificativa.
  - `POST /api/admin/subscriptions/{id}/extend`: estender manualmente o prazo limite de carência (`extended_until`).

### Épica 4: Seeders de Todos os Cenários
- [x] **Task 4.1**: Criar `SubscriptionScenarioSeeder` cobrindo:
  1. Instrutor com plano Ativo (Em Dia).
  2. Instrutor em Atraso dentro da Carência (5 dias).
  3. Instrutor em Atraso com Prorrogação Manual concedida pelo Admin.
  4. Instrutor com Carência Expirada (Bloqueado em Modo Somente Leitura).
  5. Instrutor com Comprovante PIX Offline sob análise do Admin.
  6. Super Admin do SaaS com permissões de gestão de assinaturas.

### Épica 5: Testes Automatizados e Requisições `.http`
- [x] **Task 5.1**: Escrever testes automatizados cobrindo todos os fluxos e regras de negócio (`SubscriptionTest.php`).
- [x] **Task 5.2**: Gerar arquivo de demonstração `subscriptions-demo.http` em `backend/dev-contents/demo-requests/` e `apps/hl-drive-api/dev-contents/demo-requests/`.
- [x] **Task 5.3**: Garantir 100% de aprovação na suíte completa do PHPUnit.
