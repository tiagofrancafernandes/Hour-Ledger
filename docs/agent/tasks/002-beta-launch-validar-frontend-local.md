# Tarefa 002: Validar Frontend Localmente

**Plano**: Beta Launch  
**Tipo**: Validação  
**Prioridade**: ALTA  
**Estimativa**: 30 minutos  
**Status**: Não iniciada  
**Bloqueia**: Tarefa 003 (Scripts de Dev)  
**Bloqueada por**: Tarefa 001 (Backend deve estar validado)

---

## Objetivo

Confirmar que o frontend Vue 3 + Vite funciona localmente, carrega sem erros e consegue fazer requisições ao backend.

## Contexto

O frontend em `apps/hl-drive-web` foi movido para o monorepo. Esta tarefa valida que:

1. Dependências Node estão prontas
2. Build system (Vite) funciona
3. Dev server inicia sem erros
4. Interface carrega no navegador
5. Comunicação com backend é estabelecida

## Critérios de Aceite

- ✅ Frontend sobe em `http://localhost:5173` (ou porta do `vite.config.ts`)
- ✅ Página inicial carrega sem erros de compilação
- ✅ Console do navegador não mostra erros críticos
- ✅ Requisição ao backend (Tarefa 001) funciona
- ✅ TypeScript compila sem erros (vue-tsc)
- ✅ Linting passa (eslint)
- ✅ Relatório criado em `docs/agent/reports/2026-06-24-frontend-validation.md`

## Escopo

### IN:
- Instalar dependências (npm/pnpm install)
- Verificar configuração de portas em `vite.config.ts`
- Configurar `.env` com `VITE_API_BASE_URL` correto
- Iniciar dev server
- Testar compilação e linting
- Testar requisição ao backend

### OUT:
- Implementação de features
- Alterações de layout/UI
- Criação de componentes novos
- Refatorações de código existente

## Tarefas Técnicas

### 1. Pré-requisitos

```bash
# Verificar Node version (deve ser 18+)
node -v

# Verificar npm/pnpm
npm -v
# OU
pnpm -v

# Se não tiver pnpm, instalar globalmente:
npm install -g pnpm@latest
```

### 2. Instalar Dependências

```bash
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web

# Instalar dependências
# O projeto usa pnpm (visto em pnpm-lock.yaml)
pnpm install

# OU se preferir npm:
npm install

# Ambos devem funcionar, mas pnpm é preferido
```

**Nota**: Se houver conflito de versões, pode ignorar avisos de peer dependencies por enquanto.

### 3. Verificar Configuração

#### Vite Config

Abrir `apps/hl-drive-web/vite.config.ts` e verificar:

```typescript
// Deve conter:
export default defineConfig({
  plugins: [vue()],
  server: {
    port: 6010,           // Ou porta desejada
    host: true,           // Bind para todos os interfaces
    hmr: {
      host: '127.0.0.1',  // Para desenvolvimento local
      port: 5173,         // Ou porta do Vite
      protocol: 'http'
    }
  }
  // ... resto da config
})
```

**Importante**: Ports deve ser 5173 (padrão Vite) ou 6010 (conforme plano)

#### Environment File

Editar `apps/hl-drive-web/.env`:

```bash
# Baseado em .env.example
VITE_API_BASE_URL=http://localhost:8000

# Isso aponta para o backend que vai rodar na Tarefa 001
```

**Nota**: Backend deve estar rodando (Tarefa 001 completa)

### 4. Type Check

```bash
# Verificar se TypeScript compila
pnpm run typecheck
# OU
npm run typecheck

# Deve exibir:
# [número] errors found
# Se tiver erros, registrar no relatório
```

### 5. Lint Check

```bash
# Verificar eslint
pnpm run lint
# OU
npm run lint

# Resultado esperado:
# ✓ Sem erros
# ⚠ Avisos são ok por enquanto
```

### 6. Build Check

```bash
# Testar se consegue fazer build
pnpm run build
# OU
npm run build

# Deve criar `dist/` sem erros
# Resultado:
# ✓ built in XXms
```

### 7. Iniciar Dev Server

```bash
# Manter backend rodando em outro terminal (Tarefa 001)

# Iniciar frontend
pnpm run dev
# OU
npm run dev

# Deve exibir:
# VITE v7.2.4  ready in XXX ms
#
# ➜  Local:   http://127.0.0.1:5173/
# ➜  press h + enter to show help
```

### 8. Testar no Navegador

Abrir navegador em `http://127.0.0.1:5173/`:

```
Verificar:
- [ ] Página carrega (não fica em branco)
- [ ] CSS carrega (page tem cor/styling)
- [ ] Nenhum erro na aba Console
- [ ] Nenhum erro de TypeScript compilação
- [ ] Logo/branding é visível (se houver)
```

Abrir DevTools (F12) → Console:

```
Esperado:
- Nenhuma mensagem de erro de CORS
- Nenhuma mensagem de módulo não encontrado
- Nenhuma exceção não tratada

Se houver "Cannot find module", registrar no relatório
Se houver erro de CORS, problema é no backend (Tarefa 001)
```

### 9. Testar Comunicação com Backend

```javascript
// No console do navegador (F12):

// Teste 1: Verificar se consegue fazer fetch
fetch('http://localhost:8000/api/clients')
  .then(r => r.json())
  .then(d => console.log('Backend respondeu:', d))
  .catch(e => console.error('Erro ao conectar:', e))

// Deve resultar em:
// - Resposta do backend (mesmo que vazia ou com erro 401)
// - OU erro de conexão se backend não está rodando
```

### 10. Criar Relatório

Registrar os resultados em:

```
docs/agent/reports/2026-06-24-frontend-validation.md
```

Incluir:
- Data/hora da validação
- Versão Node
- Versão npm/pnpm
- Vite version (vite --version)
- Build bem-sucedido? (sim/não)
- Typecheck passou? (sim/não/erros)
- Lint passou? (sim/não/avisos)
- Dev server inicia? (sim/não)
- Página carrega no navegador? (sim/não)
- Conexão com backend (http://localhost:8000)? (sim/não)
- Porta Vite sendo usada (5173? 6010?)
- Erros encontrados (se houver)
- Próximos passos

## Dependências

- ✅ Tarefa 001 (Backend) deve estar validado e rodando

## Notas Importantes

1. **Versão Node**: Se tiver Node 16, pode não funcionar. Atualizar para 18+:
   ```bash
   # Via nvm
   nvm install 18
   nvm use 18
   ```

2. **Porta em conflito**: Se 5173 estiver ocupado, Vite pede outra:
   ```
   Port 5173 is in use, trying 5174 instead
   ```
   Usar a porta que Vite sugerir.

3. **Erro "Cannot find module 'vue-i18n'"**: É ok, i18n ainda não está configurado (Tarefa 004). Por enquanto pode ignorar.

4. **HMR (Hot Module Replacement)**: Se não funcionar após mudanças, é problema de configuração de porta. Registrar no relatório.

5. **Código Style**: Nenhuma alteração de código esperada. Apenas validação.

## Próxima Tarefa

Após completar com sucesso: **Tarefa 003: Configurar Scripts de Desenvolvimento no Root**

