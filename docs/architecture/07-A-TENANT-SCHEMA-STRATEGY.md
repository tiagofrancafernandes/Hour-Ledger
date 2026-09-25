# Estratégia de Isolamento Multi-Tenancy por Schemas PostgreSQL

## 1. Visão Geral e Princípios Fundamentais

O ecossistema **Hour Ledger** adota o **PostgreSQL** como seu Sistema de Gerenciamento de Banco de Dados (SGBD) fixo e exclusivo.

A separação dos dados dos tenants é realizada **por Schemas PostgreSQL dedicados**, inspirada no modelo do pacote `stancl/tenancy`, combinada com um modelo de **Defesa em Profundidade (Defense-in-Depth)** na aplicação:

1. **Isolamento Físico de Banco (Camada Primária)**:
   - Cada tenant possui um schema PostgreSQL dedicado no formato `tenant_{id}_{environment}`.
   - As conexões ativas utilizam a alternância dinâmica da variável `search_path` do PostgreSQL:
     ```sql
     SET search_path TO "tenant_{id}_{environment}", "public";
     ```
   - O schema `public` atua como o catálogo central/global e fallback de resolução.

2. **Isolamento Lógico na Aplicação (Camada Secundária - Defesa em Profundidade)**:
   - Todas as entidades tenantizadas contêm a coluna identificadora `tenant_id` e utilizam o trait `BelongsToTenant`.
   - O `TenantScope` atua em modo *fail-closed*: se por qualquer motivo uma requisição não possuir contexto de tenant ativo, a query é abortada (`WHERE false`), impedindo qualquer vazamento acidental entre tenants.

---

## 2. Nomenclatura e Ambientes

### 2.1. Padrão de Nomenclatura

```text
tenant_{id}_{environment}
```

- **`tenant_`**: Prefixo obrigatório que identifica schemas pertencentes a tenants da plataforma.
- **`{id}`**: Identificador numérico único do tenant (ex: `1`, `2`, `42`).
- **`{environment}`**: Ambiente de execução (`dev`, `staging`, `prod`, `test`).

### 2.2. Exemplos de Nomenclatura

| Schema | Descrição |
| :--- | :--- |
| `public` | Base central compartilhada (identidade, tenancy, planos, subscrições) |
| `tenant_1_prod` | Dados de negócio do Tenant 1 em Produção |
| `tenant_42_staging` | Dados de negócio do Tenant 42 em Staging |
| `tenant_99_dev` | Dados de negócio do Tenant 99 em Desenvolvimento Local |
| `tenant_1_test` | Schema temporário para testes automatizados |

---

## 3. Topologia de Dados: Schema Central vs. Schemas de Tenant

```text
┌─────────────────────────────────────────────────────────────────────────┐
│                    POSTGRESQL DATABASE (hour_ledger)                    │
├─────────────────────────────────────────────────────────────────────────┤
│  SCHEMA CENTRAL: public                                                 │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │ users                      subscription_plans                     │  │
│  │ tenants                    tenant_subscriptions                   │  │
│  │ user_tenants               subscription_invoices                  │  │
│  │ invitations                subscription_payment_receipts          │  │
│  │ personal_access_tokens     roles / permissions                    │  │
│  │ preferences                activity_logs (global)                 │  │
│  └───────────────────────────────────────────────────────────────────┘  │
│                                    │                                    │
│         ┌──────────────────────────┴──────────────────────────┐         │
│         ▼                                                     ▼         │
│  SCHEMA TENANT 1: tenant_1_prod        SCHEMA TENANT 2: tenant_2_prod   │
│  ┌──────────────────────────────┐      ┌──────────────────────────────┐ │
│  │ clients                      │      │ clients                      │ │
│  │ wallets                      │      │ wallets                      │ │
│  │ ledger_entries (imutável)    │      │ ledger_entries (imutável)    │ │
│  │ credit_purchases             │      │ credit_purchases             │ │
│  │ instructor_student_links     │      │ instructor_student_links     │ │
│  │ lessons                      │      │ lessons                      │ │
│  │ packages                     │      │ packages                     │ │
│  │ tags / timers                │      │ tags / timers                │ │
│  │ import_plans                 │      │ import_plans                 │ │
│  └──────────────────────────────┘      └──────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────┘
```

### 3.1. Schema Central (`public`)

Contém as informações operacionais globais do SaaS, comuns a toda a plataforma:

- **Autenticação e Identidade**: `users`, `personal_access_tokens`, `password_resets`.
- **Governança de Tenancy**: `tenants`, `user_tenants`, `invitations`.
- **RBAC Global**: `roles`, `permissions`, `model_has_roles`, `role_has_permissions`.
- **Monetização e Assinaturas (SaaS Core)**: `subscription_plans`, `tenant_subscriptions`, `subscription_invoices`, `subscription_payment_receipts`.
- **Preferências e Configurações Globais**: `preferences`.

### 3.2. Schemas de Tenant (`tenant_{id}_{environment}`)

Contêm os dados transacionais de negócio exclusivos daquele tenant:

- **Ledger e Wallet (Core do Domínio)**:
  - `wallets`: Carteiras associadas aos clientes do tenant.
  - `ledger_entries`: Registro histórico imutável de créditos, débitos e ajustes (saldo derivado).
  - `credit_purchases`: Compras e pacotes de créditos registrados.
- **Relacionamento e Operação (HL Drive / HL Consulting)**:
  - `clients`: Alunos ou clientes gerenciados pelo tenant.
  - `instructor_student_links`: Vínculos aluno × instrutor.
  - `lessons`: Aulas e agendamentos.
  - `packages`: Pacotes de aulas.
  - `tags`, `timers`, `import_plans`, `import_plan_rows`.

---

## 4. Dinâmica de Execução e Resolução de Contexto (`search_path`)

### 4.1. Como Funciona a Alternância

No PostgreSQL, a resolução de nomes de tabelas sem qualificação explícita de schema depende da diretiva `search_path`.

Quando uma requisição HTTP ou Job é processado:

1. O `TenantMiddleware` resolve o tenant ativo a partir do cabeçalho `X-Tenant-ID`, rota ou usuário autenticado.
2. O `TenantResolver` valida se o tenant existe e está com status `active`.
3. O `TenantResolver` define o schema ativo e o `TenantMiddleware` aplica o `search_path` na conexão PostgreSQL:
   ```sql
   SET search_path TO "tenant_{id}_{environment}", "public";
   ```
4. Durante a execução da requisição:
   - Se a aplicação consulta `SELECT * FROM wallets`: o PostgreSQL busca primeiro em `tenant_{id}_{environment}`. Encontra a tabela do tenant.
   - Se a aplicação consulta `SELECT * FROM users`: o PostgreSQL busca em `tenant_{id}_{environment}`, não encontra, e resolve no fallback `public`.
5. Ao encerrar a requisição (ou em `tearDown` de testes):
   ```sql
   SET search_path TO "public";
   ```

### 4.2. Tolerância a Falhas e Resiliência

No PostgreSQL, schemas definidos em `search_path` que ainda não tenham sido criados são silenciosamente ignorados pelo otimizador de consultas, permitindo que a aplicação opere com segurança e execute rotinas de provisionamento antes da primeira consulta a dados tenantizados.

---

## 5. Ciclo de Vida do Tenant: Provisionamento e Migrações

### 5.1. Provisionamento de Novo Tenant

Ao cadastrar um novo tenant (via comando Artisan ou API de onboarding):

1. **Registro Central**: É criado o registro em `public.tenants`.
2. **Criação do Schema**: É invocada a função PostgreSQL `create_tenant_schema` ou o comando DDL:
   ```sql
   CREATE SCHEMA IF NOT EXISTS "tenant_{id}_{environment}";
   GRANT USAGE, CREATE ON SCHEMA "tenant_{id}_{environment}" TO CURRENT_USER;
   ```
3. **Execução das Migrações de Tenant**:
   As migrações de estrutura tenantizada são aplicadas ao novo schema recém-criado:
   ```bash
   php artisan tenancy:migrate --tenant={id} --environment={env}
   ```

### 5.2. Estrutura de Migrações no Projeto

```text
database/migrations/
├── 2026_06_24_000001_create_tenant_schema_function.php
├── ... (migrações centrais: users, tenants, subscription_plans, etc.)
└── tenant/
    ├── 2026_01_01_create_wallets_table.php
    ├── 2026_01_02_create_ledger_entries_table.php
    ├── 2026_01_03_create_clients_table.php
    └── 2026_01_04_create_lessons_table.php
```

---

## 6. Estratégia de Backup, Restauração e Exclusão (GDPR / LGPD)

### 6.1. Backup Granular por Tenant

Como cada tenant possui seu próprio schema, backups pontuais podem ser gerados sem paradas e sem necessidade de exportar todo o banco:

```bash
# Backup exclusivo de um único tenant
pg_dump \
  --schema="tenant_1_prod" \
  --format=custom \
  --file="/backups/tenant_1_$(date +%Y%m%d_%H%M%S).dump" \
  postgresql://postgres:postgres@localhost:5432/hour_ledger
```

### 6.2. Restauração Isolada

A restauração de dados de um cliente corrompido ou que solicitou recuperação de desastre pode ser feita sem impactar qualquer outro cliente da plataforma:

```bash
pg_restore \
  --schema="tenant_1_prod" \
  --clean \
  --dbname=hour_ledger \
  /backups/tenant_1_20260925.dump
```

### 6.3. Exclusão Limpa (Direito ao Esquecimento / Encerramento de Conta)

Para excluir permanentemente todos os dados transacionais de um tenant:

```sql
DROP SCHEMA IF EXISTS "tenant_{id}_{environment}" CASCADE;
```

Essa operação:
- Remove instantaneamente todas as tabelas, índices e dados do tenant.
- Não deixa dados órfãos espalhados por tabelas compartilhadas.
- Preserva intactos os registros centrais e de auditoria da plataforma em `public`.

---

## 7. Garantias por Testes Automatizados

A separação estrita por schema e integridade do catálogo central é validada de forma contínua através de testes automatizados dedicados:

- **`Tests\Feature\PostgresSchemaIsolationTest`**:
  - `testTenantResolverSwitchesSearchPath`: Garante que o `search_path` é ajustado ao tenant correto e restaurado para `public` ao limpar o contexto.
  - `testDataIsolationBetweenTenantSchemas`: Cria dois schemas distintos (`tenant_1_test` e `tenant_2_test`), insere dados em tabelas homônimas e comprova que o Tenant 1 não visualiza nem altera os registros do Tenant 2.
  - `testGlobalCentralTablesAreAccessibleFromTenantSchema`: Comprova que tabelas do schema central (`public.users`, `public.tenants`) continuam acessíveis e consistentes a partir de qualquer contexto de tenant via fallback do `search_path`.
  - `testDroppingTenantSchemaPreservesGlobalData`: Comprova que a destruição de um schema de tenant não corrompe nem remove dados centrais no `public`.
- **`Tests\Feature\TenancySchemaTest`**:
  - Valida a função nativa `create_tenant_schema`, geração de nomes de schema, integridade de status e validação de nomes duplicados.
- **`Tests\Feature\TenantSecurityTest` & `Tests\Feature\Architecture\CrossTenantSecurityTest`**:
  - Validam a segunda camada de proteção (coluna `tenant_id` + `TenantScope` fail-closed) contra injeções ou acessos diretos.
