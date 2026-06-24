# Beta Launch — Índice de Tarefas e Documentação

**Data**: 2026-06-24  
**Versão**: 1.0  
**Status**: Pronto para execução  

---

## 📋 Sumário Executivo

Este documento índice aponta para todas as tarefas e documentação necessárias para lançar a primeira versão beta do Hour Ledger.

**Objetivo Final**: Ter backend + frontend validados, i18n funcionando, e guia de setup pronto para clientes beta.

**Tempo Total Estimado**: 4-5 horas  
**Dependências**: Nenhuma (pode começar imediatamente)

---

## 📊 Estrutura de Execução

```
Dia 1 (2h):
├─ Tarefa 001: Backend validation (30min)
├─ Tarefa 002: Frontend validation (30min)
└─ Tarefa 003: Dev scripts (15min)

Dia 2 (2.5h):
├─ Tarefa 004: i18n setup (1h)
├─ Tarefa 005: Login validation (30min)
└─ Tarefa 006: Wallet validation (45min)

Dia 3 (1h):
├─ Tarefa 007: Beta checklist (30min)
└─ Tarefa 008: Beta setup guide (30min)
```

---

## 📚 Documentação de Análise

### Relatório Principal
- **Arquivo**: `docs/agent/reports/2026-06-24-analise-estado-atual-e-tarefas-beta.md`
- **Objetivo**: Análise consolidada do estado atual e proposta de tarefas
- **Leia isso**: Antes de começar qualquer tarefa

---

## ✅ Tarefas de Execução

### Fase 1: Validação Ambiente (Dia 1)

#### 📌 Tarefa 001: Validar Backend Localmente
- **Arquivo**: `docs/agent/tasks/001-beta-launch-validar-backend-local.md`
- **Prioridade**: ALTA
- **Estimativa**: 30 minutos
- **Objetivo**: PHP, composer, migrations, servidor rodando
- **Bloqueador**: Não
- **Bloqueia**: Tarefa 002 (Frontend validation)
- **Status**: Não iniciada
- **Checklist**:
  - [ ] Pré-requisitos verificados
  - [ ] Dependências instaladas
  - [ ] .env configurado
  - [ ] Migrations rodadas
  - [ ] Servidor inicia
  - [ ] Endpoints respondendo
  - [ ] Relatório criado
- **Próxima**: Tarefa 002

---

#### 📌 Tarefa 002: Validar Frontend Localmente
- **Arquivo**: `docs/agent/tasks/002-beta-launch-validar-frontend-local.md`
- **Prioridade**: ALTA
- **Estimativa**: 30 minutos
- **Objetivo**: Node, npm/pnpm, vite, conexão com backend
- **Bloqueador**: Não
- **Bloqueia**: Tarefa 003 (Scripts)
- **Status**: Não iniciada
- **Checklist**:
  - [ ] Pré-requisitos verificados
  - [ ] Dependências instaladas
  - [ ] vite.config.ts verificado
  - [ ] .env configurado
  - [ ] Dev server inicia
  - [ ] Página carrega
  - [ ] Conexão com backend OK
  - [ ] Relatório criado
- **Próxima**: Tarefa 003

---

#### 📌 Tarefa 003: Configurar Scripts de Desenvolvimento
- **Arquivo**: `docs/agent/tasks/003-beta-launch-configurar-scripts-desenvolvimento.md`
- **Prioridade**: ALTA
- **Estimativa**: 15 minutos
- **Objetivo**: Script `pnpm run dev:drive` no root
- **Bloqueador**: Não
- **Bloqueia**: Tarefa 004 (i18n)
- **Status**: Não iniciada
- **Checklist**:
  - [ ] concurrently instalado
  - [ ] Script adicionado ao root package.json
  - [ ] `pnpm run dev:drive` funciona
  - [ ] Backend em :8000
  - [ ] Frontend em :5173
  - [ ] README.md atualizado
  - [ ] Relatório criado
- **Próxima**: Tarefa 004

---

### Fase 2: Configuração i18n (Dia 2)

#### 📌 Tarefa 004: Completar Configuração i18n
- **Arquivo**: `docs/agent/tasks/004-beta-launch-configurar-i18n.md`
- **Prioridade**: ALTA
- **Estimativa**: 1 hora
- **Objetivo**: i18n com pt-BR + en, plugin registrado
- **Bloqueador**: Não
- **Bloqueia**: Tarefa 005 (Login)
- **Status**: Não iniciada
- **Checklist**:
  - [ ] `src/plugins/i18n.ts` completo
  - [ ] `src/locales/pt-BR.json` preenchido
  - [ ] `src/locales/en.json` preenchido
  - [ ] Plugin registrado em main.ts
  - [ ] Sem erros no dev server
  - [ ] $t() funciona em componentes
  - [ ] Typecheck passa
  - [ ] Build passa
  - [ ] Relatório criado
- **Próxima**: Tarefa 005

---

#### 📌 Tarefa 005: Validar Fluxo de Login
- **Arquivo**: `docs/agent/tasks/005-beta-launch-validar-login-com-i18n.md`
- **Prioridade**: ALTA
- **Estimativa**: 30 minutos
- **Objetivo**: Login funciona, token armazenado, tradução funciona
- **Bloqueador**: Não
- **Bloqueia**: Tarefa 006 (Wallet)
- **Status**: Não iniciada
- **Checklist**:
  - [ ] Login page carrega traduzido
  - [ ] Campos interativos
  - [ ] POST /api/login funciona
  - [ ] Token armazenado (localStorage/sessionStorage)
  - [ ] Redirecionamento funciona
  - [ ] Sem erro CORS
  - [ ] Sem erro de token
  - [ ] Idioma pode ser mudado
  - [ ] Logout funciona (se disponível)
  - [ ] Relatório criado
- **Próxima**: Tarefa 006

---

#### 📌 Tarefa 006: Validar Fluxo Principal (Wallet)
- **Arquivo**: `docs/agent/tasks/006-beta-launch-validar-fluxo-wallet.md`
- **Prioridade**: ALTA
- **Estimativa**: 45 minutos
- **Objetivo**: Wallet carrega, saldo correto, movimentações funcionam
- **Bloqueador**: Não
- **Bloqueia**: Tarefa 007 (Checklist)
- **Status**: Não iniciada
- **Checklist**:
  - [ ] Dados de teste criados (carteira + ledger entries)
  - [ ] Endpoints validados (/api/wallets, /api/wallets/id/balance, /api/wallets/id/entries)
  - [ ] Wallet page carrega
  - [ ] Saldo exibido corretamente
  - [ ] Movimentações listadas
  - [ ] GET requisições funcionam
  - [ ] CRUD funciona (se implementado)
  - [ ] Persistência OK (recarregar página = dados iguais)
  - [ ] Cálculo de saldo correto
  - [ ] Sem erros de console
  - [ ] Teste de cenários edge (403, 401)
  - [ ] Relatório criado
- **Próxima**: Tarefa 007

---

### Fase 3: Consolidação (Dia 3)

#### 📌 Tarefa 007: Criar Checklist de Validação Beta
- **Arquivo**: `docs/agent/tasks/007-beta-launch-criar-checklist-validacao.md`
- **Prioridade**: ALTA
- **Estimativa**: 30 minutos
- **Objetivo**: Consolidar todos relatórios, decisão final sobre prontidão
- **Bloqueador**: Não
- **Bloqueia**: Tarefa 008 (Setup guide)
- **Status**: Não iniciada
- **Checklist**:
  - [ ] Todos os 6 relatórios (001-006) lidos
  - [ ] Informações consolidadas
  - [ ] Bloqueadores identificados
  - [ ] Workarounds documentados
  - [ ] Status geral definido (PRONTO / COM RESSALVAS / NÃO PRONTO)
  - [ ] Decisão final justificada
  - [ ] Dados de teste documentados
  - [ ] Ambiente documentado
  - [ ] Documento claro e objetivo
  - [ ] Relatório criado
- **Próxima**: Tarefa 008

---

#### 📌 Tarefa 008: Criar Guia de Setup para Clientes Beta
- **Arquivo**: `docs/agent/tasks/008-beta-launch-criar-guia-setup-beta.md`
- **Prioridade**: ALTA
- **Estimativa**: 30 minutos
- **Objetivo**: Guia claro para clientes beta configurarem o ambiente
- **Bloqueador**: Não
- **Bloqueia**: Nada (finaliza ciclo)
- **Status**: Não iniciada
- **Checklist**:
  - [ ] Arquivo criado em `docs/operations/BETA-SETUP-GUIDE.md`
  - [ ] Introdução clara
  - [ ] Pré-requisitos por SO (Windows/Mac/Linux)
  - [ ] Passos de instalação backend
  - [ ] Passos de instalação frontend
  - [ ] Passos de instalação database
  - [ ] Como executar (pnpm run dev:drive)
  - [ ] Dados de teste inclusos
  - [ ] Funcionalidades listadas
  - [ ] Troubleshooting com problemas comuns
  - [ ] Como reportar bugs
  - [ ] FAQ respondido
  - [ ] Contato de suporte
  - [ ] Documento testável por não-técnico
  - [ ] Markdown válido
  - [ ] Nenhuma info sensível

---

## 🎯 Critérios de Sucesso

Todas as tarefas completadas = **Pronto para Beta**

Indicadores:
- ✅ Backend rodando localmente sem erros
- ✅ Frontend rodando localmente sem erros
- ✅ Login funciona e-end-to-end
- ✅ Wallet (feature principal) funciona
- ✅ i18n ativo e funcional
- ✅ Guia de setup pronto para clientes
- ✅ Relatório de checklist criado
- ✅ Nenhum blocker crítico

---

## 🚀 Próximos Passos Após Beta

1. **Compartilhar guia** com clientes beta
2. **Recolher feedback** durante 2-4 semanas
3. **Priorizar issues** baseado em feedback
4. **Iniciar planejamento** de Milestone Extração Core
5. **Comunicar roadmap** público

---

## 📝 Arquivos de Referência

### Documentação de Projeto
- `AGENTS.md` — Regras arquiteturais (leia antes de começar)
- `CLAUDE.md` — Instruções locais para Claude Code
- `UNIVERSAL-CODE-STYLE-RULES.md` — Padrões de código (obrigatório)

### Planos Relacionados
- `docs/agent/plans/2026-05-13-migrate-to-monorepo.md`
- `docs/agent/plans/2026-05-13-local-setup-and-i18n.md`
- `docs/agent/plans/2026-05-13-extract-hl-core-from-current-app.md`

### Checkpoints
- `docs/agent/checkpoints/2026-05-13-migrate-to-monorepo.md`
- `docs/agent/checkpoints/2026-05-13-local-setup-and-i18n.md`

---

## 💡 Tips para Execução Eficiente

1. **Faça sequencialmente**: Cada tarefa depende da anterior
2. **Leia o arquivo de tarefa completamente** antes de começar
3. **Acompanhe o checklist** dentro de cada tarefa
4. **Crie relatórios** — são base para análise
5. **Se prender**: Registre o problema no relatório, prossiga com workaround se possível
6. **Atualize status** no índice (Este arquivo) após cada tarefa
7. **Mantenha histórico**: Não apague relatórios antigos

---

## 🔄 Como Usar Este Índice

### Para Começar
1. Ler este arquivo (você está aqui ✓)
2. Ler `2026-06-24-analise-estado-atual-e-tarefas-beta.md`
3. Ir para Tarefa 001

### Para Continuar
1. Abrir arquivo de tarefa (ex: `001-beta-launch-validar-backend-local.md`)
2. Seguir passos técnicos
3. Criar relatório
4. Voltar aqui e marcar como completo
5. Ir para próxima tarefa

### Para Revisar
1. Ler relatórios individuais (em `docs/agent/reports/`)
2. Ler Tarefa 007 (Beta Checklist)
3. Decidir se pronto para lançar

---

## ✏️ Status de Execução

Marque conforme avança:

```
Fase 1: Validação Ambiente
- [ ] Tarefa 001: Backend validation
- [ ] Tarefa 002: Frontend validation
- [ ] Tarefa 003: Dev scripts

Fase 2: Configuração i18n
- [ ] Tarefa 004: i18n setup
- [ ] Tarefa 005: Login validation
- [ ] Tarefa 006: Wallet validation

Fase 3: Consolidação
- [ ] Tarefa 007: Beta checklist
- [ ] Tarefa 008: Setup guide

Status Geral: 
- [ ] 0-2 tarefas (Começando)
- [ ] 3-5 tarefas (Em progresso)
- [ ] 6-7 tarefas (Quase pronto)
- [ ] 8 tarefas (✅ PRONTO PARA BETA)
```

---

## 📞 Contato & Suporte

Se tiver dúvidas durante a execução das tarefas:

1. Verifique o section "Troubleshooting" dentro do arquivo de tarefa
2. Procure na FAQ da tarefa
3. Consulte `AGENTS.md` para contexto arquitetural
4. Verifique exemplos no código existente

---

## 🎓 Referências Rápidas

### Comandos Frequentes

```bash
# Root do projeto
cd /mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem

# Backend
cd apps/hl-drive-api
php artisan serve
php artisan migrate
php artisan tinker

# Frontend
cd apps/hl-drive-web
pnpm run dev
pnpm run build
pnpm run typecheck
pnpm run lint

# Root (após Tarefa 003)
pnpm run dev:drive
```

### Portas Padrão

- Backend: `http://localhost:8000`
- Frontend: `http://localhost:5173`
- Database: PostgreSQL na porta 5432

### Credenciais de Teste

- Email: `test@example.com` (criado na Tarefa 001)
- Senha: `password123`
- Database: `hl_drive_dev`
- DB User: `postgres`

---

**Última Atualização**: 2026-06-24  
**Versão**: 1.0  
**Status**: Pronto para Execução ✅

