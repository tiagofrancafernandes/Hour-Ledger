# Análise de Estado Atual e Tarefas para Lançamento Beta

**Data**: 2026-06-24  
**Status**: Análise completa  
**Objetivo**: Identificar o que falta para lançamento beta funcional

---

## 1. Estado Atual Consolidado

### ✅ Completo
- Estrutura de monorepo criada e validada
- Backend (Laravel 12) em `apps/hl-drive-api`
- Frontend (Vue 3 + Vite) em `apps/hl-drive-web`
- Documentação de arquitetura e convenções
- Dependências instaladas em ambos os apps
- i18n (vue-i18n) já declarado em package.json

### ⏳ Em Progresso / Parcial
- i18n: Instalado mas não configurado
  - Arquivo `src/plugins/i18n.ts` existe
  - Arquivos de locale (`src/locales/`) criados
  - Plugin ainda não registrado em `main.ts`
  - Traduções base incompletas

- Configuração de ambiente local
  - `.env` do backend parcialmente configurado
  - `.env` do frontend parcialmente configurado
  - `vite.config.ts` parcialmente configurado
  - Scripts de desenvolvimento não consolidados

### ❌ Faltando para Beta
- Validação completa do monorepo (Milestone 4 do plano de migração)
- Backend testado rodando localmente
- Frontend testado rodando localmente
- Fluxo de login validado
- Fluxo principal de wallet validado
- i18n completo e testado
- Ambiente local 100% funcional
- Scripts convenientes para desenvolvimento

---

## 2. Tarefas Necessárias para Lançamento Beta

Prioridade: **Alta** — Essas tarefas são bloqueantes para qualquer lançamento

### Fase 1: Validação e Setup Local (Imediato)

#### Tarefa 001: Validar Backend Localmente
**Objetivo**: Confirmar que o backend Laravel sobe e responde  
**Dependências**: Nenhuma  
**Estimativa**: 30 min

- [ ] Verificar PHP version (8.2+)
- [ ] Instalar dependências com composer
- [ ] Configurar `.env` com DB local
- [ ] Rodar migrations
- [ ] Testar `php artisan serve`
- [ ] Validar endpoints básicos com curl/Postman
- [ ] Criar relatório em `docs/agent/reports/2026-06-24-backend-validation.md`

**Critérios de Aceite**:
- Backend sobe em `http://localhost:8000` (ou porta configurada)
- GET `/api/health` ou endpoint similar retorna 200
- Migrations rodam sem erros
- Banco de dados criado

---

#### Tarefa 002: Validar Frontend Localmente
**Objetivo**: Confirmar que o frontend Vue sobe e conecta ao backend  
**Dependências**: Tarefa 001 (Backend validado)  
**Estimativa**: 30 min

- [ ] Verificar Node/npm versions
- [ ] Instalar dependências com pnpm/npm
- [ ] Configurar `.env` com API_URL correto
- [ ] Rodar `npm run dev`
- [ ] Verificar se carrega em `http://localhost:5173` (ou porta configurada)
- [ ] Testar se consegue fazer requisição ao backend
- [ ] Criar relatório em `docs/agent/reports/2026-06-24-frontend-validation.md`

**Critérios de Aceite**:
- Frontend sobe em `http://localhost:5173` (ou porta do vite.config.ts)
- Página carrega sem erros de compilação
- Console não tem erros críticos
- Requisição ao backend funciona

---

#### Tarefa 003: Configurar Scripts de Desenvolvimento no Root
**Objetivo**: Permitir rodar backend e frontend simultaneamente com um único comando  
**Dependências**: Tarefa 001 e 002 validadas  
**Estimativa**: 15 min

- [ ] Instalar `concurrently` no root (já no composer.json, verificar)
- [ ] Atualizar `package.json` root com script `dev:drive`
- [ ] Script deve rodar ambos em paralelo
- [ ] Validar que ambos sobem juntos
- [ ] Documentar comando no README.md

**Critérios de Aceite**:
- `pnpm run dev:drive` (ou equivalente) funciona
- Backend ativo em porta 6011 (ou configurada)
- Frontend ativo em porta 6010 (ou configurada)
- Nenhum erro de conflito de portas

---

### Fase 2: Configuração i18n (Próximo)

#### Tarefa 004: Completar Configuração i18n do Frontend
**Objetivo**: Ter i18n 100% funcional com pt-BR e en  
**Dependências**: Tarefa 003 (Frontend rodando)  
**Estimativa**: 1 hora

- [ ] Verificar arquivo `src/plugins/i18n.ts`
- [ ] Completar inicialização do vue-i18n
- [ ] Configurar suporte para pt-BR e en
- [ ] Criar arquivo `src/locales/pt-BR.json` com traduções base
- [ ] Criar arquivo `src/locales/en.json` com traduções base
- [ ] Registrar plugin em `src/main.ts`
- [ ] Testar com `$t()` em algum componente
- [ ] Validar funcionamento

**Traduções Essenciais** (mínimo para beta):
- Login (botão, labels)
- Wallet (saldo, movimentações)
- Navegação
- Mensagens de erro comuns

**Critérios de Aceite**:
- Plugin carrega sem erros
- Componente consegue usar `$t('key')`
- Ambas línguas funcionam
- Não há erros de console sobre i18n

---

#### Tarefa 005: Validar Fluxo de Login com i18n
**Objetivo**: Confirmar que login funciona com i18n integrado  
**Dependências**: Tarefa 004 (i18n configurado)  
**Estimativa**: 30 min

- [ ] Testar página de login no frontend
- [ ] Validar que textos estão traduzidos
- [ ] Fazer login com credenciais de teste
- [ ] Verificar se token é armazenado corretamente
- [ ] Validar se após login a navegação funciona
- [ ] Criar relatório simples

**Critérios de Aceite**:
- Login page carrega com textos traduzidos
- É possível fazer login
- Token é armazenado (localStorage/session)
- Redirecionamento pós-login funciona

---

#### Tarefa 006: Validar Fluxo Principal (Wallet)
**Objetivo**: Confirmar que funcionalidade de wallet funciona end-to-end  
**Dependências**: Tarefa 005 (Login validado)  
**Estimativa**: 45 min

- [ ] Fazer login
- [ ] Navegar até wallet
- [ ] Verificar se saldo carrega do backend
- [ ] Testar visualização de movimentações
- [ ] Testar criação de movimentação simples (se disponível)
- [ ] Validar que dados são persistidos
- [ ] Criar relatório

**Critérios de Aceite**:
- Wallet carrega após login
- Saldo é exibido corretamente
- Movimentações são listadas
- Operações básicas funcionam (read)
- Nenhum erro de rede ou banco de dados

---

### Fase 3: Preparação para Beta (Subsequente)

#### Tarefa 007: Criar Checklist de Validação Beta
**Objetivo**: Documentar o que foi validado e o que está pronto  
**Dependências**: Tarefas 001-006 completas  
**Estimativa**: 30 min

- [ ] Consolidar todos os relatórios
- [ ] Listar o que funciona e o que não funciona
- [ ] Documentar blockers conhecidos
- [ ] Identificar dados de teste necessários
- [ ] Criar documento `docs/agent/reports/2026-06-24-beta-readiness-checklist.md`

**Critérios de Aceite**:
- Documento criado
- Status claro de cada área (backend, frontend, i18n, fluxos)
- Ações corretivas identificadas se necessário

---

#### Tarefa 008: Criar Guia de Setup para Clientes Beta
**Objetivo**: Documentar como clientes beta devem configurar o ambiente  
**Dependências**: Tarefas 001-006 completas  
**Estimativa**: 30 min

- [ ] Documentar pré-requisitos (PHP, Node, DB, etc)
- [ ] Documentar passos de instalação
- [ ] Documentar como rodar backend e frontend
- [ ] Documentar credenciais de teste
- [ ] Documentar como reportar bugs
- [ ] Criar arquivo em `docs/operations/BETA-SETUP-GUIDE.md`

**Critérios de Aceite**:
- Guia completo e testável
- Claro o suficiente para usuário não-técnico
- Inclui troubleshooting básico

---

## 3. Ordem Recomendada de Execução

```
Dia 1:
├─ 001: Backend validation
├─ 002: Frontend validation
└─ 003: Dev scripts

Dia 2:
├─ 004: i18n setup
└─ 005: Login flow

Dia 3:
├─ 006: Wallet flow
├─ 007: Beta checklist
└─ 008: Beta setup guide
```

**Tempo Total Estimado**: 4-5 horas

---

## 4. Dependências Externas Confirmadas

- PHP 8.2+ ✓ (em composer.json)
- Laravel 12 ✓
- PostgreSQL (configuração em .env)
- Node.js 18+ ✓
- Vue 3.5+ ✓
- TailwindCSS v4 ✓
- vue-i18n ✓ (no package.json)

---

## 5. Riscos Identificados

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---------------|--------|-----------|
| Versão PHP incompatível | Baixa | Alto | Validar na Tarefa 001 |
| Node/npm versão | Baixa | Alto | Validar na Tarefa 002 |
| Banco de dados não pronto | Média | Alto | Seguir .env.example |
| i18n não registrado | Alta | Médio | Checklist na Tarefa 004 |
| Porta conflitante | Média | Médio | Configurar em vite.config + .env |
| CORS bloqueando requisições | Média | Alto | Validar configuração Laravel |

---

## 6. Próximas Fases (Pós-Beta)

Após essas tarefas estarem **100% completas e validadas**, prosseguir com:

1. Planejamento da extração do HL Core (Milestone 2 de `2026-05-13-extract-hl-core-from-current-app.md`)
2. Implementação de features adicionais baseado em feedback beta
3. Otimizações de performance
4. Deployment em staging

---

## Notas Importantes

- ✅ Todas as tarefas respeitam `UNIVERSAL-CODE-STYLE-RULES.md`
- ✅ Seguem convenção de nomes da documentação
- ✅ Estão focadas no essencial para beta (MVP)
- ✅ Evitam escopo creep ou abstração prematura
- ✅ Documentação é parte integral de cada tarefa

