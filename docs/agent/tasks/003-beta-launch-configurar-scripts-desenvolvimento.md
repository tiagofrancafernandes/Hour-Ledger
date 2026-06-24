# Tarefa 003: Configurar Scripts de Desenvolvimento no Root

**Plano**: Beta Launch  
**Tipo**: Setup  
**Prioridade**: ALTA  
**Estimativa**: 15 minutos  
**Status**: Não iniciada  
**Bloqueia**: Tarefa 004 (i18n) e subsequentes  
**Bloqueada por**: Tarefa 002 (Frontend validado)

---

## Objetivo

Criar scripts convenientes no root do monorepo para rodar backend e frontend simultaneamente com um único comando.

## Contexto

Atualmente, para desenvolver, usuário precisa:
1. Abrir terminal 1, navegar para `apps/hl-drive-api`, rodar `php artisan serve`
2. Abrir terminal 2, navegar para `apps/hl-drive-web`, rodar `pnpm run dev`
3. Gerenciar 2 terminais manualmente

Objetivo é simplificar para: `pnpm run dev:drive` no root e ambos sobem.

## Critérios de Aceite

- ✅ Script `dev:drive` existe em root `package.json`
- ✅ Script usa `concurrently` para rodar ambos em paralelo
- ✅ Backend roda em porta 8000 (ou conforme .env)
- ✅ Frontend roda em porta 5173 (ou vite padrão)
- ✅ Comando `pnpm run dev:drive` inicia ambos
- ✅ Output mostra claro qual é backend vs frontend
- ✅ Ctrl+C fecha ambos gracefully
- ✅ README.md atualizado com instruções

## Escopo

### IN:
- Atualizar root `package.json` com script `dev:drive`
- Garantir `concurrently` está instalado
- Testar script
- Atualizar documentação

### OUT:
- Alterações em apps individuais
- Mudanças de configuração de portas
- Refatorações de código

## Tarefas Técnicas

### 1. Verificar Instalação de `concurrently`

```bash
# No root do projeto
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem

# Verificar se já está instalado
pnpm list concurrently
# OU
npm list -g concurrently

# Se não estiver, instalar:
pnpm add -D concurrently
# OU
npm install --save-dev concurrently
```

### 2. Atualizar Root `package.json`

Abrir `/mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/package.json`

Encontrar seção `"scripts"` e adicionar:

```json
{
  "scripts": {
    "dev:drive": "concurrently -c \"#93c5fd,#c4b5fd\" \"pnpm --filter=hl-drive-api dev\" \"pnpm --filter=hl-drive-web dev\" --names=backend,frontend --kill-others",
    
    "dev:drive:watch": "concurrently -c \"#93c5fd,#c4b5fd\" \"pnpm --filter=hl-drive-api dev\" \"pnpm --filter=hl-drive-web dev\" --names=backend,frontend",
    
    "backend:dev": "pnpm --filter=hl-drive-api dev",
    "frontend:dev": "pnpm --filter=hl-drive-web dev"
  }
}
```

**Explicação**:
- `concurrently`: Roda múltiplos comandos em paralelo
- `-c`: Cores para diferenciação (azul claro para backend, roxo para frontend)
- `--filter`: Especifica qual package rodar (filtra workspace)
- `--names`: Nomes descritivos na saída
- `--kill-others`: Mata todos os processos ao fazer Ctrl+C

### 3. Validar Configuração

Verificar que o root `package.json` tem:

```json
{
  "name": "hour-ledger",
  "workspaces": [
    "apps/*",
    "packages/*"
  ],
  "scripts": {
    "dev:drive": "..."
  }
}
```

**Importante**: Workspace deve estar configurado para pnpm encontrar apps

### 4. Testar Script

```bash
# No root
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem

# Testar script
pnpm run dev:drive

# Esperado:
# [backend] INFO  Server running on [http://127.0.0.1:8000]
# [frontend] VITE v7.2.4  ready in XXX ms
# [frontend] ➜  Local:   http://127.0.0.1:5173/

# Para parar: Ctrl+C (fecha ambos)
```

### 5. Validar Portas Corretas

Verificar em 2 navegadores/abas:
- Backend: `http://localhost:8000` → Deve responder (mesmo que erro 404)
- Frontend: `http://localhost:5173` → Deve mostrar UI

Ambos devem estar respondendo simultaneamente.

### 6. Atualizar README.md

Adicionar seção em `/mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/README.md`:

```markdown
## Development

### Quick Start (Single Command)

```bash
pnpm run dev:drive
```

This starts both backend (port 8000) and frontend (port 5173) in parallel.

### Individual Commands

```bash
# Backend only
pnpm run backend:dev

# Frontend only
pnpm run frontend:dev
```

### Environment

Ensure `.env` files are configured in:
- `apps/hl-drive-api/.env`
- `apps/hl-drive-web/.env`

See respective CLAUDE.md files for details.
```

### 7. Adicionar Scripts Auxiliares (Opcional mas Recomendado)

No root `package.json`, também considerar:

```json
{
  "scripts": {
    "backend:install": "pnpm --filter=hl-drive-api install",
    "backend:test": "pnpm --filter=hl-drive-api test",
    "backend:lint": "pnpm --filter=hl-drive-api lint",
    
    "frontend:install": "pnpm --filter=hl-drive-web install",
    "frontend:build": "pnpm --filter=hl-drive-web build",
    "frontend:lint": "pnpm --filter=hl-drive-web lint",
    
    "install": "pnpm install && pnpm backend:install && pnpm frontend:install",
    "lint": "pnpm backend:lint && pnpm frontend:lint"
  }
}
```

### 8. Testar Novamente

```bash
# Garante que tudo funciona
pnpm run dev:drive

# Deixar rodando por 30s
# Verificar que não há erros
# Ctrl+C para fechar

# Resultado esperado:
# - Ambos os processos iniciam
# - Nenhum erro de conexão
# - Ambos sobem simultaneamente
# - Um Ctrl+C fecha ambos
```

### 9. Criar Registro

Criar arquivo: `docs/agent/reports/2026-06-24-scripts-setup.md`

Incluir:
- Scripts adicionados ao root package.json
- Versão do concurrently
- Teste realizado (data/hora)
- Portas confirmadas
- Qualquer erro encontrado
- Documentação atualizada? (sim/não)

## Dependências

- ✅ Tarefa 002 (Frontend validado)

## Notas Importantes

1. **pnpm workspaces**: Este script assume que o projeto usa `pnpm` como package manager. Se usar `npm`, substituir `pnpm` por `npm` nos scripts.

2. **Filtros**: Sintaxe `--filter=name` funciona com pnpm. Para npm, pode precisar ajustar.

3. **Colors**: As cores `#93c5fd,#c4b5fd` são apenas visuais. Podem ser ajustadas conforme preferência.

4. **Matadores de porta**: Se portas ficarem "pegadas", usar:
   ```bash
   # Linux/Mac
   lsof -i :8000
   lsof -i :5173
   
   # Windows
   netstat -ano | findstr :8000
   netstat -ano | findstr :5173
   ```

5. **Timeout**: Se algum processo demore muito, `concurrently` pode fazer timeout. Ajustar conforme necessário.

## Arquivos Modificados

- ✏️ `/package.json` (adição de scripts)
- ✏️ `/README.md` (documentação)

## Próxima Tarefa

Após completar com sucesso: **Tarefa 004: Completar Configuração i18n do Frontend**

