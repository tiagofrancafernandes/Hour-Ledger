# Testing Strategy

## Visão Geral

Hour Ledger deve ter cobertura forte de testes automatizados que validem:

1. **Arquitetura**: Multi-tenancy, boundaries, isolamento
2. **Domínio**: Wallet/Ledger, instructor-student-links, lesson consumption
3. **Segurança**: Isolamento de dados, autorização, validação de contexto

Todos os testes rodam localmente com Pest (PHP), PostgreSQL em Docker.

## Prioridade de Testes

### 1️⃣ Testes de Isolamento (Mais Crítico)

Validam que a arquitetura de multi-tenancy funciona:
- Dados de tenant 1 nunca vazam para tenant 2
- Contexto de instrutor isolado corretamente
- Soft-deletes preservam auditoria mas removem de queries

**Coverage esperada**: 95%+ para isolamento crítico

### 2️⃣ Testes de Domínio

Validam regras de negócio:
- Wallet nunca fica com saldo negativo
- Ledger é imutável (insert-only)
- Consumo de horas atômico
- Convites expiram corretamente

**Coverage esperada**: 85%+ para lógica de negócio

### 3️⃣ Testes de Integração

Validam fluxos end-to-end:
- Aluno aceita convite → cria link → acessa recursos
- Aula agendada → consumo de horas → atualiza saldo
- Múltiplos instrutores → contexto separado

**Coverage esperada**: 75%+ para fluxos críticos

### 4️⃣ Testes de Refatoração

Validam que mudanças não quebram o sistema:
- Testes de regressão para código alterado
- Integração entre módulos
- Performance de queries

## Estrutura de Testes

```
tests/
├── Unit/
│   ├── Architecture/
│   │   ├── MultiTenancyIsolationTest.php    (✅ tenant data não vaza)
│   │   ├── TenantSchemaStrategyTest.php     (✅ naming + isolation)
│   │   ├── BoundariesTest.php               (✅ Drive->Core, não Core->Drive)
│   │   └── InstructorContextTest.php        (✅ instructor scope)
│   ├── Domain/
│   │   ├── WalletTest.php                   (✅ saldo derivado)
│   │   ├── LedgerTest.php                   (✅ imutabilidade)
│   │   └── LessonConsumptionTest.php        (✅ atomicidade)
│   └── Models/
│       └── InstructorStudentLinkTest.php    (✅ lifecycle)
├── Feature/
│   ├── Architecture/
│   │   ├── InvitationFlowTest.php           (✅ convite → link → acesso)
│   │   └── MultiInstructorContextTest.php   (✅ switch instructor)
│   ├── Lessons/
│   │   └── LessonSchedulingTest.php         (✅ create → consume → balance)
│   └── Security/
│       └── CrossTenantAccessTest.php        (✅ nenhum vazamento)
└── Pest.php                                  (global setup)
```

## Cobertura Esperada por Módulo

### Ledger / Wallet (Core)
- ✅ Crédito (insert ledger entry)
- ✅ Débito (insert ledger entry, atualiza balance)
- ✅ Consumo (débito específico com validação de saldo)
- ✅ Ajuste (compensação de erros)
- ✅ Transferência (débito de uma, crédito de outra)
- ✅ Saldo derivado (agregação correta de ledger)
- ✅ Concorrência (duas transações simultâneas não geram race condition)
- ✅ Transações de banco (ACID garantido)

**Target**: 95% cobertura

### HL Drive (Domínio)
- ✅ Vínculo aluno × instrutor (criar, aceitar, rejeitar, revogar)
- ✅ Convites (send, accept, reject, expire, resend)
- ✅ Agenda (create lesson, list, delete, soft-delete preserva auditoria)
- ✅ Consumo de horas (validar saldo antes, atualizar atomicamente)
- ✅ Permissões (student com link vê dados, sem link não vê)
- ✅ Multi-instrutor (switch instructor_id, contexto isolado)

**Target**: 85% cobertura

### Multi-Tenancy
- ✅ Isolamento de schema (tenant_1_dev não vê dados de tenant_2_dev)
- ✅ Resolução de contexto (middleware seta tenant_id antes de queries)
- ✅ Scopes automáticos (models herdam BelongsToTenant, filtram automaticamente)
- ✅ Soft-delete isolado (deleted_at não vaza entre tenants)
- ✅ Proteção de schema (queries em schema errado falham)

**Target**: 98% cobertura (crítico para segurança)

## Como Rodar Testes

### Localmente (Com Docker PostgreSQL)

```bash
# 1. Criar container PostgreSQL
docker-compose up -d postgres

# 2. Criar databases de teste
docker-compose exec postgres psql -U user -c "CREATE DATABASE test_database;"

# 3. Rodar testes
php artisan test

# Ou specific:
php artisan test tests/Unit/Architecture/MultiTenancyIsolationTest.php
php artisan test tests/Feature/Architecture/InvitationFlowTest.php

# Com coverage
php artisan test --coverage --coverage-html=coverage/
```

### CI/CD (GitHub Actions)

Executado em cada push. Ver `.github/workflows/tests.yml`.

## Princípios de Teste

1. **Teste o comportamento, não a implementação** 
   - ❌ DON'T: assert que `$ledgerEntries->count() == 5`
   - ✅ DO: assert que `$wallet->balance == original + credit`

2. **Dado-Quando-Então**
   ```php
   // Dado: wallet com saldo 100
   $wallet = Wallet::factory()->balance(100)->create();
   
   // Quando: debitamos 30
   $wallet->debit(30);
   
   // Então: saldo é 70
   $this->assertEquals(70, $wallet->refresh()->balance);
   ```

3. **Um conceito por teste**
   - ❌ DON'T: `test_invitation_flow_and_lesson_creation_and_consumption`
   - ✅ DO: `test_invitation_accepted_creates_active_link`

4. **Testes isolados e independentes**
   - Use factories e seeders
   - Cada teste é autossuficiente
   - Não dependa de ordem de execução

## Métricas de Sucesso

- [ ] 100% de testes passando (79/79 existing + novos)
- [ ] Coverage de Architecture ≥ 98%
- [ ] Coverage de Domain ≥ 85%
- [ ] Coverage de HL Drive ≥ 85%
- [ ] Zero flakiness (testes determinísticos)
- [ ] Tempo de execução < 60s (suite completa)

## Referências

- `01-CONSTITUTION.md` — Princípios que os testes validam
- `02-VISION.md` — Fluxos que os testes cobrem
- `07-MULTI-TENANCY.md` — Isolamento que os testes verificam
- `07-A-TENANT-SCHEMA-STRATEGY.md` — Strategy que os testes validam
- `instructor-student-link.md` — Fluxo que os testes cobrem
