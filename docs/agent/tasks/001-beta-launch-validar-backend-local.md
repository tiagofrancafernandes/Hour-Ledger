# Tarefa 001: Validar Backend Localmente

**Plano**: Beta Launch  
**Tipo**: Validação  
**Prioridade**: ALTA  
**Estimativa**: 30 minutos  
**Status**: Não iniciada

---

## Objetivo

Confirmar que o backend Laravel 12 funciona localmente, responde a requisições e se conecta corretamente ao banco de dados.

## Contexto

O backend em `apps/hl-drive-api` foi movido do repositório anterior para o monorepo. Esta tarefa valida que:

1. Dependências PHP estão prontas
2. Configuração de ambiente está correta
3. Banco de dados pode ser criado/migrado
4. Servidor Laravel inicia sem erros
5. Endpoints básicos respondem

## Critérios de Aceite

- ✅ Backend sobe em `http://localhost:8000` (ou porta do `APP_URL` em `.env`)
- ✅ GET `/api/health` ou endpoint similar retorna HTTP 200
- ✅ Migrations rodam sem erros (banco está atualizado)
- ✅ Banco de dados PostgreSQL conecta corretamente
- ✅ Nenhum erro fatal no console
- ✅ Relatório criado em `docs/agent/reports/2026-06-24-backend-validation.md`

## Escopo

### IN:
- Instalar dependências (composer install)
- Configurar `.env` com credenciais de banco local
- Rodar migrations
- Iniciar servidor
- Testar endpoints básicos

### OUT:
- Refatorações de código
- Implementações de features
- Testes automatizados (unitários/integração)
- Otimizações de performance
- Alterações de arquitetura

## Tarefas Técnicas

### 1. Pré-requisitos

```bash
# Verificar PHP version (deve ser 8.2+)
php -v

# Verificar se composer está instalado
composer -V

# Verificar se PostgreSQL está disponível
psql --version
# OU via Docker se usar container
```

Se algum pré-requisito faltar:
- Instalar PHP 8.2+ via apt/brew/etc
- Instalar Composer
- Instalar PostgreSQL 15+ (ou usar Docker)

### 2. Instalar Dependências

```bash
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-api

# Instalar dependências PHP
composer install

# Isso deve funcionar sem erros
# Se houver erro de memory, rodar:
# composer install --no-dev
```

### 3. Configurar `.env`

Editar `apps/hl-drive-api/.env`:

```bash
# Use .env.example como base se necessário
cp .env.example .env

# Editar com credenciais locais (mínimo necessário):
APP_NAME=HourLedger
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hl_drive_dev
DB_USERNAME=postgres
DB_PASSWORD=postgres

# Cache
CACHE_DRIVER=file
SESSION_DRIVER=cookie

# Queue
QUEUE_CONNECTION=sync
```

**Nota**: Se usar Docker para DB, ajustar `DB_HOST` para `host.docker.internal` ou IP do container

### 4. Gerar APP_KEY (se necessário)

```bash
# Se APP_KEY estiver vazio em .env
php artisan key:generate
```

### 5. Criar/Atualizar Banco de Dados

```bash
# Criar banco de dados (manualmente ou via Laravel)
# Assumindo PostgreSQL rodando localmente:

# Opção A: Via psql
psql -U postgres -c "CREATE DATABASE hl_drive_dev;"

# Opção B: Laravel faz automaticamente se estiver configurado corretamente

# Rodar migrations
php artisan migrate

# Se erro: [ERROR] database not exist, rodar:
# php artisan migrate:install
# php artisan migrate
```

### 6. Verificar Instalação Inicial

```bash
# Listar rotas disponíveis
php artisan route:list --path=api

# Deve listar endpoints da API
```

### 7. Iniciar Servidor

```bash
# Forma 1: PHP Built-in Server
php artisan serve

# Deve exibir:
# INFO  Server running on [http://127.0.0.1:8000].

# Forma 2: Se usar Sail/Docker
# ./vendor/bin/sail up

# Manter este comando rodando em um terminal separado
```

### 8. Testar Endpoints Básicos

Em outro terminal:

```bash
# Testar um endpoint simples
# Verificar se a API responde

# Opção A: curl
curl http://localhost:8000/api/clients

# Deve retornar:
# - HTTP 200 e resposta JSON (com erro de auth é ok por enquanto)
# - OU HTTP 401 Unauthorized (esperado se exigir token)

# Opção B: Postman
# Abrir Postman e fazer GET em http://localhost:8000/api/clients

# Opção C: Verificar logs
# Abrir em browser: http://localhost:8000 (pode mostrar erro 404, é ok)
```

### 9. Criar Relatório

Registrar os resultados em:

```
docs/agent/reports/2026-06-24-backend-validation.md
```

Incluir:
- Data/hora da validação
- Versão PHP
- Versão Laravel (php artisan --version)
- Conexão DB bem-sucedida? (sim/não)
- Migrations rodaram? (sim/não/problemas)
- Servidor inicia? (sim/não)
- Endpoints respondendo? (sim/não)
- Portas usadas (APP_URL, DB_PORT)
- Erros encontrados (se houver)
- Próximos passos

## Dependências

Nenhuma tarefa anterior é bloqueante.

## Notas Importantes

1. **Banco de dados**: Se não tiver PostgreSQL instalado, pode usar SQLite temporariamente:
   ```bash
   DB_CONNECTION=sqlite
   DB_DATABASE=database.sqlite
   ```
   Mas isso não será usado em produção.

2. **Erros comuns**:
   - "Database does not exist" → Criar DB manualmente ou usar SQLite
   - "Unable to locate factory with name" → Rodar migrations
   - "Connection refused" → Verificar DB_HOST e DB_PORT

3. **Se usar Docker**:
   ```bash
   docker compose --env-file .env.docker up -d
   docker compose exec backend php artisan migrate
   docker compose exec backend php artisan serve
   ```

4. **Código Style**: Nenhuma alteração de código é esperada nesta tarefa. Se encontrar código que viola `UNIVERSAL-CODE-STYLE-RULES.md`, apenas registrar no relatório.

## Próxima Tarefa

Após completar com sucesso: **Tarefa 002: Validar Frontend Localmente**

