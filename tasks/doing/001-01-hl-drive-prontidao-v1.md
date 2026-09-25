# Plano: Prontidão Operacional do HL Drive (Instrutores e Alunos)

**Prioridade:** Alta
**Data de Início Planejada:** 2026-09-25
**Data de Conclusão Planejada:** 2026-10-15
**Responsável:** Equipe Hour Ledger Ecosystem

---

## 1. Objetivo

Tornar o produto **HL Drive** 100% funcional e pronto para uso real por instrutores autônomos de direção e seus respectivos alunos, cobrindo o ciclo completo:
1. Auto-onboarding do instrutor (registro + criação automática de tenant e permissões);
2. Conexão instrutor-aluno (convites com criação/vínculo automático de carteira);
3. Gestão e consumo de horas de aula (módulos de aulas e pacotes no backend e frontend);
4. Portal do aluno (visualização de saldo, histórico transparente de transações e solicitação de saldo);
5. Estabilidade da suíte de testes (correção de incompatibilidades entre `TenantObserver` e testes de vínculos/ledger).

---

## 2. Diagnóstico Atual

| Funcionalidade | Estado Atual | Gaps Identificados |
|---|---|---|
| **Inscrição de Instrutor** | ⚠️ Parcial | Cria apenas `User` básico; não cria Tenant nem associa roles/permissões |
| **Cadastro de Alunos** | ⚠️ Bipolar | Funciona via entidade legada `Client`, mas `InstructorStudentLink` não cria nem vincula carteiras |
| **Adição de Saldo de Horas** | ⚠️ Parcial | Funciona via Ledger manual na Wallet do Client; Pacotes (`/packages`) não existem no backend |
| **Débito de Saldo de Horas** | ⚠️ Parcial | Funciona via Ledger manual e Timers genéricos; Aulas (`/lessons`) não existem no backend |
| **Visualização de Saldo pelo Aluno** | ⚠️ Condicional | Funciona apenas se aluno tiver `customer_id` configurado manualmente; quebra via convite |
| **Compra de Saldo pelo Aluno** | ⚠️ Parcial | Funciona via `CreditPurchase` na Wallet legada; não integrada ao fluxo multi-instrutor |
| **Histórico de Transações** | ⚠️ Funcional | Ledger e pagamentos funcionam no modelo legado; visualização desacoplada de aulas |
| **Suíte de Testes Automatizados** | ❌ 40+ Falhas | `TenantObserver` bloqueia criação de fixtures sem tenant ativo em `InstructorStudentLink` e `LedgerTest` |

---

## 3. Escopo de Trabalho (Checklist de Tarefas)

### Épica 1: Correção e Estabilização da Suíte de Testes (Base)
- [x] **Task 1.1**: Corrigir execução dos testes de `InstructorStudentLinkTest` e `LedgerTest` ajustando o `TenantContext` e `TenantObserver` para fixtures de teste.
- [x] **Task 1.2**: Garantir 100% de aprovação na suíte de testes com `php artisan test` (461/461 testes aprovados).

### Épica 2: Auto-Onboarding do Instrutor
- [x] **Task 2.1**: Implementar fluxo de auto-onboarding para instrutores: ao registrar e verificar e-mail, provisionar automaticamente o Tenant do instrutor com schema PostgreSQL dedicado.
- [x] **Task 2.2**: Atribuir a role `admin` do tenant e configurar perfil profissional do instrutor.
- [ ] **Task 2.3**: Ajustar frontend (`RegisterView.vue`) para suportar o cadastro completo do instrutor autônomo.

### Épica 3: Unificação Aluno <-> Instrutor <-> Carteira
- [x] **Task 3.1**: Vincular `InstructorStudentLink` com a criação/associação de uma carteira (`Wallet`) de horas específica daquele instrutor para o aluno.
- [x] **Task 3.2**: Ao aceitar o convite (`/api/invitations/{id}/accept`), provisionar ou associar a carteira de horas do aluno no tenant do instrutor.
- [x] **Task 3.3**: Permitir que o aluno acesse o sistema e alterne entre instrutores (`active_instructor_id`), visualizando a carteira correspondente a cada um.

### Épica 4: Implementação do Domínio de Aulas e Pacotes no Backend
- [x] **Task 4.1**: Criar migrations, models e policies para `packages` (pacotes de horas de aula com valor e quantidade).
- [x] **Task 4.2**: Criar migrations, models e policies para `lessons` (aulas agendadas, realizadas, canceladas, com consumo de saldo).
- [x] **Task 4.3**: Implementar controllers e rotas da API:
  - `GET/POST/PUT/DELETE /api/packages`
  - `GET/POST/PUT /api/lessons`
  - `POST /api/lessons/{id}/complete` (debita saldo automaticamente da carteira via ledger)
  - `POST /api/lessons/{id}/cancel` (estorna saldo se necessário)
- [x] **Task 4.4**: Gerar arquivos de requisições de teste em `backend/dev-contents/demo-requests/lessons-demo.http` e `packages-demo.http`.

### Épica 5: Integração Completa do Frontend HL Drive
- [ ] **Task 5.1**: Integrar os componentes existentes (`LessonScheduler`, `LessonConsumption`, `LessonsList`, `PackageList`, `PackageForm`) nas rotas do Vue Router (`router/index.ts`).
- [ ] **Task 5.2**: Ativar o `Dashboard.vue` moderno para alunos e instrutores, substituindo os fluxos legados de agência por fluxos específicos de aulas de direção.
- [ ] **Task 5.3**: Implementar interface no portal do aluno para:
  - Visualizar saldo de horas com o instrutor ativo;
  - Histórico detalhado de aulas realizadas e débitos no ledger;
  - Aquisição/solicitação de novos pacotes de horas com envio de comprovante.

---

## 4. Critérios de Sucesso

- ✅ Instrutor consegue acessar a web, cadastrar-se e ter seu ambiente/tenant provisionado sem intervenção manual de TI.
- ✅ Instrutor consegue enviar convite por e-mail ou cadastrar o aluno diretamente, criando automaticamente a carteira de horas.
- ✅ Instrutor e aluno conseguem visualizar a carteira com o saldo atualizado em tempo real.
- ✅ Instrutor consegue agendar e concluir aula, gerando débito automático e auditável no Ledger.
- ✅ Aluno consegue visualizar extrato completo (créditos adquiridos, aulas debitadas e saldo remanescente).
- ✅ 100% dos testes unitários e de integração passando (`php artisan test` e `pnpm test`).
- ✅ Código aderente às regras de `UNIVERSAL-CODE-STYLE-RULES.md`.
