# Tenant Schema Strategy

## 1. Naming Convention

### 1.1. Pattern

```
tenant_{id}_{environment}
```

### 1.2. Componentes

- **tenant_**: Prefixo obrigatório
- **{id}**: ID numérico do tenant (ex: 1, 2, 42)
- **{environment}**: Ambiente (dev, staging, prod)

### 1.3. Exemplos

```
tenant_1_prod       → Tenant 1 em produção
tenant_1_staging    → Tenant 1 em staging
tenant_1_dev        → Tenant 1 em desenvolvimento
tenant_42_prod      → Tenant 42 em produção
tenant_1000_prod    → Tenant 1000 em produção
```

### 1.4. Justificativa

- **Consistência**: Fácil identificar tenant e ambiente
- **Automação**: Scripts podem processar schemas por padrão
- **Isolamento**: Evita colisões de naming
- **Escalabilidade**: Suporta milhares de tenants

## 2. Schemas por Ambiente

### 2.1. Arquitetura Multi-Ambiente

```
┌────────────────────────────────────────────────────────────────┐
│                  DESENVOLVIMENTO LOCAL                         │
├────────────────────────────────────────────────────────────────┤
│  public  (global)                                              │
│  tenant_1_dev, tenant_2_dev, ...                               │
└────────────────────────────────────────────────────────────────┘
            (Docker Compose com PostgreSQL)

        │
        │ (Testado localmente)
        │

┌────────────────────────────────────────────────────────────────┐
│                      STAGING                                   │
├────────────────────────────────────────────────────────────────┤
│  public  (global)                                              │
│  tenant_1_staging, tenant_2_staging, ...                       │
│                                                                │
│  (Replica de prod com dados de teste)                          │
└────────────────────────────────────────────────────────────────┘
        (RDS/Managed Database)

        │
        │ (Testado completo)
        │

┌────────────────────────────────────────────────────────────────┐
│                      PRODUÇÃO                                  │
├────────────────────────────────────────────────────────────────┤
│  public  (global)                                              │
│  tenant_1_prod, tenant_2_prod, ..., tenant_N_prod              │
│                                                                │
│  (Dados reais de clientes)                                     │
└────────────────────────────────────────────────────────────────┘
        (RDS/Managed Database - Alta Disponibilidade)
```

### 2.2. Criação de Schema por Ambiente

#### Desenvolvimento

```php
// Ao criar novo tenant localmente
Artisan::call('tenant:create', [
    'name' => 'Test Tenant 1',
    'environment' => 'dev',
]);

// Cria: public (se não existir), tenant_1_dev
// Popula com dados fake para testes
```

#### Staging

```php
// Ao promover do dev para staging
// Usar ferramenta de clonagem segura

// 1. Backup do schema dev
pg_dump tenant_1_dev > tenant_1_staging.sql

// 2. Restaurar em staging com dados sanitizados
// (remover dados sensíveis, emails reais, etc.)
psql -d staging < tenant_1_staging.sql

// 3. Validar integridade
php artisan schema:validate tenant_1_staging
```

#### Produção

```php
// Ao onboard novo cliente
Artisan::call('tenant:create', [
    'name' => 'Client Name',
    'environment' => 'prod',
]);

// Cria: tenant_N_prod
// Com migrations padrão, sem dados

// Cliente popula com dados via API/UI
```

## 3. Tabelas Globais vs. Tenantizadas

### 3.1. Schema Público (Global)

**Localização**: `public` schema

**Propósito**: Dados compartilhados por toda plataforma

**Tabelas**:

| Tabela | Descrição | Chave | Replicação |
|--------|-----------|-------|-----------|
| `users` | Usuários globais | id (UUID) | Multi-region |
| `tenants` | Configuração de tenants | id (BIGINT) | Multi-region |
| `user_tenants` | Relacionamento user→tenant | (user_id, tenant_id) | Multi-region |
| `invitations` | Convites para tenants | id (UUID) | Multi-region |
| `preferences` | Prefs globais (idioma, tz) | (user_id, key) | Multi-region |

**Características**:

- Sem informação específica de negócio
- Dados de identificação e contexto
- Replicado entre ambientes
- Índices em (user_id, email, tenant_id)

**Exemplo**:

```sql
CREATE SCHEMA public;

CREATE TABLE public.users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE public.tenants (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) UNIQUE,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW()
);

CREATE TABLE public.user_tenants (
    id BIGINT PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES public.users(id),
    tenant_id BIGINT NOT NULL REFERENCES public.tenants(id),
    role VARCHAR(50) DEFAULT 'member',
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(user_id, tenant_id)
);
```

### 3.2. Schemas de Tenant (Tenantizados)

**Localização**: `tenant_{id}_{environment}`

**Propósito**: Dados específicos do tenant, isolado completamente

**Tabelas Padrão**:

| Tabela | Descrição | Notas |
|--------|-----------|-------|
| `wallets` | Carteiras de usuários | Saldo derivado do ledger |
| `ledger_entries` | Movimentações (imutável) | INSERT-only, nunca UPDATE |
| `students` | Alunos/participantes | Específico de HL Drive |
| `instructors` | Instrutores | Específico de HL Drive |
| `lessons` | Aulas agendadas | Específico de HL Drive |
| `schedules` | Agendas e disponibilidades | Específico de HL Drive |
| `packages` | Pacotes de horas/créditos | Específico de HL Drive |
| `audit_logs` | Histórico auditável | Rastreia todas as ações |
| `student_instructor_links` | Relacionamentos | Vínculo aluno↔instrutor |

**Características**:

- Dados de negócio específicos do tenant
- Completamente isolado de outros tenants
- Índices em (user_id, created_at, status)
- Logs de auditoria para compliance

**Exemplo**:

```sql
-- Schema do tenant
CREATE SCHEMA tenant_1_prod;

-- Tabelas de negócio
CREATE TABLE tenant_1_prod.wallets (
    id BIGINT PRIMARY KEY,
    user_id UUID NOT NULL,
    name VARCHAR(100) NOT NULL,
    balance_cents BIGINT DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'BRL',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    INDEX (user_id, created_at)
);

CREATE TABLE tenant_1_prod.ledger_entries (
    id BIGINT PRIMARY KEY,
    wallet_id BIGINT NOT NULL REFERENCES tenant_1_prod.wallets(id),
    type VARCHAR(50) NOT NULL,
    amount_cents BIGINT NOT NULL,
    description TEXT,
    reference_id VARCHAR(100),
    created_by UUID,
    created_at TIMESTAMP DEFAULT NOW(),
    -- IMUTÁVEL: sem UPDATE, sem DELETE
    UNIQUE(reference_id),
    INDEX (wallet_id, created_at)
);

CREATE TABLE tenant_1_prod.audit_logs (
    id BIGINT PRIMARY KEY,
    user_id UUID NOT NULL,
    action VARCHAR(100) NOT NULL,
    model VARCHAR(100),
    model_id VARCHAR(100),
    changes JSONB,
    ip_address INET,
    created_at TIMESTAMP DEFAULT NOW(),
    INDEX (user_id, created_at, action)
);
```

## 4. Migration Strategy

### 4.1. Estrutura de Migrations

```
database/migrations/
├── global/              # Migrations globais (public schema)
│   ├── 2024_01_01_create_users_table.php
│   ├── 2024_01_02_create_tenants_table.php
│   └── 2024_01_03_create_user_tenants_table.php
│
├── tenant/              # Migrations de tenant (executadas em cada tenant)
│   ├── 2024_01_01_create_wallets_table.php
│   ├── 2024_01_02_create_ledger_entries_table.php
│   ├── 2024_01_03_create_audit_logs_table.php
│   └── ...
```

### 4.2. Executar Migrations

#### Global

```bash
# Cria public schema e tabelas globais
php artisan migrate --path=database/migrations/global

# Resultado: public schema com users, tenants, user_tenants, etc.
```

#### Tenant

```bash
# Cria schema para tenant_1_prod e executa migrations
php artisan migrate:tenant --tenant=1 --env=prod

# Resultado: tenant_1_prod schema com wallets, ledger_entries, etc.

# Ou todos os tenants em dev
php artisan migrate:tenant --env=dev

# Resultado: tenant_1_dev, tenant_2_dev, ... com esquemas completos
```

### 4.3. Migração de Schema Existente

Ao refatorar um schema existente:

```bash
# 1. Criar versão de teste
php artisan schema:fork tenant_1_prod --to=tenant_1_test

# 2. Executar migração em teste
php artisan migrate --path=database/migrations/schema-refactor --database=test

# 3. Validar resultado
php artisan schema:validate tenant_1_test

# 4. Se OK, aplicar em produção (com backup)
php artisan migrate --path=database/migrations/schema-refactor --database=prod

# 5. Validar produção
php artisan schema:validate tenant_1_prod
```

## 5. Backup e Recovery Strategy

### 5.1. Backup por Tenant

```bash
# Backup de um tenant específico
pg_dump \
  --schema=tenant_1_prod \
  --format=directory \
  --jobs=4 \
  postgresql://user:pass@host/dbname \
  > /backups/tenant_1_prod_$(date +%Y%m%d_%H%M%S).backup

# Resultado: Diretório com backup comprimido e paralelo
```

### 5.2. Backup Global

```bash
# Backup de todos os schemas
pg_dump \
  --format=directory \
  --jobs=4 \
  postgresql://user:pass@host/dbname \
  > /backups/full_$(date +%Y%m%d_%H%M%S).backup

# Ou apenas backup do schema public
pg_dump \
  --schema=public \
  postgresql://user:pass@host/dbname \
  > /backups/public_$(date +%Y%m%d_%H%M%S).sql
```

### 5.3. Recovery por Tenant

#### Recuperar Tenant Deletado

```bash
# 1. Restaurar schema a partir de backup
pg_restore \
  --schema=tenant_1_prod \
  --create \
  /backups/tenant_1_prod_backup.backup

# 2. Validar dados
SELECT COUNT(*) FROM tenant_1_prod.wallets;

# 3. Retomar operação
UPDATE public.tenants SET status='active' WHERE id=1;
```

#### Recuperar Dados Específicos

```sql
-- Se ledger foi corrompido, restaurar de backup anterior
-- 1. Renomear schema atual
ALTER SCHEMA tenant_1_prod RENAME TO tenant_1_prod_corrupted;

-- 2. Restaurar backup
pg_restore --schema-only --create /backups/tenant_1_prod_backup.backup

-- 3. Copiar dados selecionados de corrupted para prod
INSERT INTO tenant_1_prod.ledger_entries
  SELECT * FROM tenant_1_prod_corrupted.ledger_entries
  WHERE created_at < '2024-01-15'::timestamp;

-- 4. Limpar
DROP SCHEMA tenant_1_prod_corrupted CASCADE;
```

### 5.4. RPO e RTO

| Métrica | Target | Notas |
|---------|--------|-------|
| RPO | 1 hora | Backups horários, perda máxima de 1h de dados |
| RTO | 15 min | Restauração de um tenant em até 15 minutos |
| Retenção | 30 dias | Backups mantidos por 30 dias |
| Teste | Semanal | Restauração de teste validada semanalmente |

## 6. Performance Considerations

### 6.1. Índices

```sql
-- Índices essenciais para cada tenant
CREATE INDEX idx_wallets_user_id 
  ON tenant_1_prod.wallets(user_id);

CREATE INDEX idx_wallets_status 
  ON tenant_1_prod.wallets(status) 
  WHERE status != 'deleted';

CREATE INDEX idx_ledger_wallet_created 
  ON tenant_1_prod.ledger_entries(wallet_id, created_at DESC);

CREATE INDEX idx_audit_logs_user_action 
  ON tenant_1_prod.audit_logs(user_id, action, created_at DESC);

CREATE INDEX idx_audit_logs_model 
  ON tenant_1_prod.audit_logs(model, model_id, created_at DESC);
```

### 6.2. Particionamento

Para tenants grandes (bilhões de ledger_entries):

```sql
-- Particionar ledger_entries por ano
CREATE TABLE tenant_1_prod.ledger_entries (
    id BIGINT,
    wallet_id BIGINT,
    type VARCHAR(50),
    amount_cents BIGINT,
    created_at TIMESTAMP,
    ...
) PARTITION BY RANGE (YEAR(created_at));

CREATE TABLE ledger_entries_2024 
    PARTITION OF ledger_entries
    FOR VALUES FROM (2024) TO (2025);

CREATE TABLE ledger_entries_2025 
    PARTITION OF ledger_entries
    FOR VALUES FROM (2025) TO (2026);
```

### 6.3. Caching de Saldo

Saldo derivado do ledger é custoso de calcular:

```sql
-- Cache desnormalizado
ALTER TABLE tenant_1_prod.wallets ADD COLUMN cached_balance_cents BIGINT;

-- Atualizar cache atomicamente com ledger entry
BEGIN;
  INSERT INTO tenant_1_prod.ledger_entries (...) VALUES (...);
  UPDATE tenant_1_prod.wallets 
    SET cached_balance_cents = cached_balance_cents + $1
    WHERE id = $2;
COMMIT;

-- Validação periódica
php artisan wallet:validate-balances --tenant=1
```

### 6.4. Connection Pooling

Cada tenant requer conexão com seu schema:

```yaml
# config/database.php

'connections' => [
    'pgsql' => [
        'host' => env('DB_HOST'),
        'pool' => [
            'min' => 5,
            'max' => 20,
        ],
        'sticky' => true,
    ],
],
```

### 6.5. Query Analysis

```bash
# Analisar queries lentas
EXPLAIN (ANALYZE, BUFFERS) 
  SELECT * FROM tenant_1_prod.wallets WHERE user_id = 'uuid-123';

# Resultado: Verificar index usage, sequential scans
# Se sequential scan: Criar índice apropriado
```

## 7. Checklist de Implementação

- [ ] Schema public criado com usuarios, tenants, user_tenants
- [ ] Migrations global executadas com sucesso
- [ ] Primeiro tenant criado (tenant_1_dev)
- [ ] Migrations de tenant executadas em tenant_1_dev
- [ ] Testes de isolamento validam que tenant_1_dev não vê dados de tenant_2_dev
- [ ] Backup automático configurado
- [ ] Restauração testada e validada
- [ ] Índices criados em todas as tabelas críticas
- [ ] Connection pooling configurado
- [ ] Monitoramento de performance setup
- [ ] Documentação de operations atualizada
