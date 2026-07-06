# Multi-Tenancy Architecture

## 1. Visão Geral

Hour Ledger implementa um modelo de multi-tenancy híbrido, onde:

- **Identidade global**: Usuários são autenticados uma única vez no nível global
- **Dados tenantizados**: Todos os dados específicos de negócio são isolados por tenant em schemas PostgreSQL separados
- **Isolamento completo**: Cada tenant possui dados totalmente isolados em seu próprio schema
- **Compartilhamento seguro**: Usuários podem participar de múltiplos tenants, mas com contexto de tenant ativo durante requisições

Este modelo permite:

- Segurança robusta contra vazamento de dados entre tenants
- Escalabilidade eficiente com schemas PostgreSQL dedicados
- Simplicidade operacional sem gerenciamento complexo de múltiplos bancos
- Facilidade de backup e recovery por tenant
- Conformidade com requisitos de isolamento de dados

## 2. Arquitetura

### 2.1. Camadas de Dados

```
┌─────────────────────────────────────────────────────────┐
│  GLOBAL SCHEMA (public)                                 │
│  ┌─────────────────────────────────────────────────────┐│
│  │ users            (id, email, password, ...)         ││
│  │ tenants          (id, name, status, created_at)    ││
│  │ user_tenants     (user_id, tenant_id, role, ...)   ││
│  │ invitations      (id, email, tenant_id, status) ││
│  │ preferences      (id, user_id, key, value)         ││
│  └─────────────────────────────────────────────────────┘│
└─────────────────────────────────────────────────────────┘
            │
            ├──────────────────────────────────────────────────┐
            │                                                  │
┌──────────────────────────┐      ┌──────────────────────────┐
│  TENANT SCHEMA (tenant_1)│      │  TENANT SCHEMA (tenant_2)│
│  ┌──────────────────────┐│      │  ┌──────────────────────┐│
│  │ wallets              ││      │  │ wallets              ││
│  │ ledger_entries       ││      │  │ ledger_entries       ││
│  │ students             ││      │  │ students             ││
│  │ instructors          ││      │  │ instructors          ││
│  │ lessons              ││      │  │ lessons              ││
│  │ schedules            ││      │  │ schedules            ││
│  │ packages             ││      │  │ packages             ││
│  │ audit_logs           ││      │  │ audit_logs           ││
│  └──────────────────────┘│      │  └──────────────────────┘│
└──────────────────────────┘      └──────────────────────────┘
```

### 2.2. Separação Global vs. Tenantizado

#### Global (Schema `public`)

Dados compartilhados por toda a plataforma:

- `users`: Identificação e autenticação global
- `tenants`: Configuração de tenants
- `user_tenants`: Relacionamento usuário ↔ tenant com papéis/permissões
- `invitations`: Convites para participar de tenants
- `preferences`: Preferências globais de usuários (idioma, timezone, etc.)

Característica: Sem informação específica de negócio, apenas contexto de plataforma.

#### Tenantizado (Schema `tenant_{id}_{environment}`)

Dados específicos de cada tenant:

- `wallets`: Carteiras e saldos do tenant
- `ledger_entries`: Movimentações de ledger (imutável)
- `students`: Alunos/participantes no contexto do tenant
- `instructors`: Instrutores no contexto do tenant
- `lessons`: Aulas agendadas
- `schedules`: Agendas e disponibilidades
- `packages`: Pacotes de horas/créditos
- `audit_logs`: Histórico auditável de ações

Característica: Totalmente isolado, sem dados de outros tenants.

## 3. Fluxo de Requisição

### 3.1. Resolução de Tenant

Toda requisição API segue este fluxo:

```
1. Requisição HTTP recebida
   │
   ├─ Autenticação global (middleware)
   │  └─ Valida token JWT, extrai user_id global
   │
   ├─ Resolução de tenant (middleware/service)
   │  ├─ Extrai tenant_id do header: X-Tenant-ID
   │  ├─ Valida se user_id tem acesso ao tenant_id
   │  ├─ Define tenant_id no contexto da aplicação
   │  └─ Conecta ao schema tenant_{id}_{environment}
   │
   ├─ Execução de lógica
   │  ├─ Todas as queries usam schema ativo
   │  └─ Isolamento automático de dados
   │
   └─ Resposta retornada
      └─ Mantém isolamento de contexto
```

### 3.2. Headers Necessários

```http
Authorization: Bearer <global-jwt-token>
X-Tenant-ID: <numeric-tenant-id>
X-Product: <product-code> (opcional, ex: "drive")
```

### 3.3. Implementação Técnica

**Middleware de Tenant**:

```php
// Valida que o usuário tem acesso ao tenant solicitado
$userId = Auth::id(); // Do token JWT global
$tenantId = Request::header('X-Tenant-ID');

$hasAccess = UserTenant::where('user_id', $userId)
    ->where('tenant_id', $tenantId)
    ->exists();

if (!$hasAccess) {
    throw new UnauthorizedException('Acesso negado ao tenant');
}

// Define tenant no contexto
TenantContext::set($tenantId);

// Todas as queries após este ponto usam o schema do tenant
```

## 4. Segurança e Isolamento de Dados

### 4.1. Princípios de Isolamento

1. **Nível de Aplicação**: Middleware força tenant_id antes de acessar dados
2. **Nível de Database**: PostgreSQL schemas garantem isolamento físico
3. **Nível de Modelos**: Eloquent models fazem scope automático ao tenant ativo
4. **Nível de Queries**: Raw queries nunca devem contornar tenant scope

### 4.2. Proteção contra Vazamento

**❌ Nunca fazer**:

```php
// Perigoso: pode vazar dados de outros tenants
$users = User::all();
$wallets = Wallet::where('user_id', $userId)->get();
```

**✅ Sempre fazer**:

```php
// Seguro: força contexto de tenant
$users = User::whereIn('id', $userIds)->get();
$wallets = Wallet::forTenant()->where('user_id', $userId)->get();
```

### 4.3. Políticas de Autorização

- Autenticação global: Apenas usuários registrados podem fazer requisições
- Validação de tenant: Usuário deve ter papel no tenant solicitado
- Autorização de recurso: Recurso deve pertencer ao tenant ativo
- Auditoria: Todas as ações são registradas com user_id e tenant_id

## 5. Estrutura de Schema PostgreSQL

### 5.1. Naming Convention

```
tenant_{tenant_numeric_id}_{environment}

Exemplos:
  tenant_1_prod       → Tenant 1, ambiente produção
  tenant_1_staging    → Tenant 1, ambiente staging
  tenant_1_dev        → Tenant 1, ambiente desenvolvimento
  tenant_2_prod       → Tenant 2, ambiente produção
```

### 5.2. Schema Público

```sql
-- Schema público contém dados globais
CREATE SCHEMA public;

-- Tabelas globais
CREATE TABLE users (
    id BIGINT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE tenants (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    status ENUM ('active', 'suspended', 'deleted'),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE user_tenants (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id),
    tenant_id BIGINT NOT NULL REFERENCES tenants(id),
    role VARCHAR(50),
    created_at TIMESTAMP,
    UNIQUE(user_id, tenant_id)
);
```

### 5.3. Schema de Tenant

```sql
-- Cada tenant possui seu próprio schema
CREATE SCHEMA tenant_1_prod;

-- Tables tenant-specific
CREATE TABLE tenant_1_prod.wallets (
    id BIGINT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    name VARCHAR(255),
    balance DECIMAL(15,2),
    created_at TIMESTAMP
);

CREATE TABLE tenant_1_prod.ledger_entries (
    id BIGINT PRIMARY KEY,
    wallet_id BIGINT NOT NULL REFERENCES tenant_1_prod.wallets(id),
    type ENUM ('purchase', 'transfer', 'bonus', 'refund', 'consumption'),
    amount DECIMAL(15,2),
    description TEXT,
    created_at TIMESTAMP,
    -- IMUTÁVEL: não é updatable
);
```

### 5.4. Isolamento a Nível de Database

```sql
-- Policies de Row Level Security podem ser usadas para proteção adicional
ALTER TABLE tenant_1_prod.wallets ENABLE ROW LEVEL SECURITY;

CREATE POLICY tenant_isolation ON tenant_1_prod.wallets
    USING (TRUE)
    WITH CHECK (TRUE);
```

## 6. Mudanças de API

### 6.1. Endpoints Global (Schema `public`)

Não requerem X-Tenant-ID:

```
POST   /api/auth/register          # Criar novo usuário global
POST   /api/auth/login             # Autenticação global
GET    /api/me                     # Usuário autenticado
GET    /api/my-tenants             # Listar tenants do usuário
POST   /api/tenants                # Criar novo tenant
```

### 6.2. Endpoints Tenantizados

Requerem X-Tenant-ID:

```
GET    /api/wallet                 # Carteira do usuário no tenant
POST   /api/wallet/transfer        # Transferência
GET    /api/students               # Alunos do tenant
GET    /api/lessons                # Aulas do tenant
POST   /api/lessons                # Criar aula
```

### 6.3. Formato de Requisição

```http
GET /api/students HTTP/1.1
Host: api.example.com
Authorization: Bearer eyJhbGc...
X-Tenant-ID: 1
Accept: application/json

---

HTTP/1.1 200 OK
Content-Type: application/json

{
    "data": [
        {"id": 1, "name": "João Silva", "tenant_id": 1},
        {"id": 2, "name": "Maria Santos", "tenant_id": 1}
    ]
}
```

## 7. Decisões Arquiteturais e Trade-offs

### 7.1. Por que PostgreSQL Schemas e não Databases Separados?

**Escolha**: PostgreSQL Schemas (múltiplos por instância)

**Benefícios**:

- Simples operacionalmente: Uma instância PostgreSQL para gerenciar
- Backup eficiente: Um backup contém todos os schemas
- Elasticidade: Novos tenants criados em segundos
- Custo: Menor overhead de recursos que múltiplos Databases/instâncias
- Maintenance: Patches e atualizações em um lugar

**Trade-offs**:

- Isolamento: Menos isolamento físico que databases separados
- Limite teórico: PostgreSQL suporta milhares de schemas, mas depende de recursos
- Noisy neighbor: Um tenant mal-comportado pode afetar performance geral

**Mitigação**:

- Resource limits via cgroups/K8s
- Connection pooling por tenant
- Monitoring e alertas de performance
- SLAs separados por tenant se necessário

### 7.2. Por que Identidade Global no Schema `public`?

**Escolha**: Uma única tabela de usuários global

**Benefícios**:

- Simplicidade: Um usuário tem um ID único em toda a plataforma
- Sign-On único: Login uma vez, acesso a múltiplos tenants
- Auditoria global: Rastrear ações do usuário através de tenants
- Convites: Convidar usuário para novo tenant sem recadastrá-lo

**Trade-offs**:

- Dependência: Falha no schema público afeta toda a plataforma
- Migração: Migrar usuário entre tenants requer coordenação

**Mitigação**:

- Alta disponibilidade do schema público
- Replicação e backups freqüentes
- Testes de disaster recovery

### 7.3. Por que Ledger é Imutável?

**Escolha**: Ledger entries são INSERT-only, nunca UPDATE/DELETE

**Benefícios**:

- Auditoria: Histórico completo e confiável
- Consistência: Saldo é derivado do ledger, não é variável independente
- Compliance: Atende requisitos de rastreabilidade
- Corrigibilidade: Erros são corrigidos com compensações, não apagando história

**Trade-offs**:

- Storage: Crescimento contínuo de ledger_entries
- Queries: Saldo requer cálculo por agregação

**Mitigação**:

- Índices em (wallet_id, created_at)
- Colunas desnormalizadas de saldo por cache/atualização periódica
- Particionamento de ledger_entries se crescer muito

### 7.4. Context vs. Configuration

**Escolha**: TenantContext é runtime, não global configuration

**Benefícios**:

- Flexibilidade: Mudar tenant durante requisição (jobs, crons)
- Testabilidade: Mock diferentes tenants em testes
- Concorrência: Cada processo/coroutine tem seu próprio contexto

**Trade-offs**:

- Responsabilidade: Cada componente deve respeitar context
- Debugging: Erros de contexto são subtis

**Mitigação**:

- Middleware validação rigorosa
- Logs sempre incluem tenant_id
- Testes de integração validam isolamento

## 8. Roadmap de Implementação

### Fase 1: Fundação (Atual - Tarefa A)
- [x] Documentação de arquitetura
- [x] Tipos/Enums de tenant
- [x] Model Tenant básico
- [ ] Migrations de schemas

### Fase 2: Middleware e Context
- [ ] Middleware de autenticação global
- [ ] Middleware de resolução de tenant
- [ ] TenantContext service
- [ ] Testes de isolamento

### Fase 3: Models e Scopes
- [ ] Base de models com tenant scope
- [ ] Traits para tenant scoping automático
- [ ] Validação de isolamento

### Fase 4: API e Controllers
- [ ] Endpoints tenantizados
- [ ] Formatação de respostas com tenant_id
- [ ] Testes E2E

### Fase 5: Operações
- [ ] Migração de schemas
- [ ] Backup e recovery por tenant
- [ ] Monitoramento e alertas
