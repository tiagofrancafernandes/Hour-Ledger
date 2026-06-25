# Continuando Tarefas em Novas Sessões

## Objetivo

Permitir continuação de tarefas, projetos e planos mesmo após:
- Fim de sessão (reload do navegador)
- Execução de `/clear` (reset de contexto)
- Mudanças de sessão/día
- Recuperação após perda de contexto

**Garantia:** Todos os prompts respeitam `AGENTS.md`, `CLAUDE.md` e `UNIVERSAL-CODE-STYLE-RULES.md`

---

## 📋 Checkpoints de Arquivo (Leitura Obrigatória)

Antes de qualquer prompt de continuação, **SEMPRE** ler:

```
1. docs/agent/prompts/00-garantir-consistencia-do-projeto.md
   → Define padrões, convenções, regras de projeto
   
2. docs/architecture/02-VISION.md
   → Define roadmap, visão, prioridades

3. docs/agent/README.md
   → Define estrutura de tarefas, planos, checkpoints
```

---

## 🚀 Caso 1: Continuar Tarefa Única (Single Task)

### Cenário
Você estava trabalhando em uma tarefa e a sessão foi interrompida. Você quer continuar de onde parou.

### Prompt de Continuação

```
Vou continuar onde parei. Leia estes arquivos para contexto:

1. Ler checkpoint atual: docs/agent/checkpoints/[data-checkpoint].md
2. Ler plano da tarefa: docs/agent/plans/[tarefa-id].md (se existir)
3. Ver status do git: git log --oneline -10
4. Ver arquivos modificados: git status

Com base no checkpoint e plano, continue a tarefa atual.
Respeite AGENTS.md, CLAUDE.md e UNIVERSAL-CODE-STYLE-RULES.md.

Próximo passo: [descrever o que estava fazendo]
```

### Exemplo Prático

```
Vou continuar a Tarefa 003 (Data Leakage Prevention tests).

Lendo:
- docs/agent/checkpoints/2026-06-26-fase4-task-g-milestone-3-progress.md
- docs/architecture/02-VISION.md

Status atual: 12 testes criados, alguns warnings de variáveis não usadas.
Próximo: Executar suite completa, remover warnings, fazer commit.
```

---

## 🔀 Caso 2: Continuar com Múltiplos Subagents

### Cenário
Você estava executando tarefas em paralelo com subagents. Quer retomar múltiplas tarefas simultaneamente.

### Prompt de Continuação

```
Retomando execução paralela de múltiplas tarefas.

Lendo checkpoint: docs/agent/checkpoints/[data-status].md

Status atual de tarefas:
- Tarefa 002: [status]
- Tarefa 003: [status]
- Tarefa 004: [status]

Criar subagents para tarefas não-bloqueantes:
1. [Tarefa A] - Descrição e escopo
2. [Tarefa B] - Descrição e escopo

Executar em paralelo. Cada subagent deve:
- Ler AGENTS.md para contexto do projeto
- Respeitar UNIVERSAL-CODE-STYLE-RULES.md
- Fazer commit ao terminar
- Atualizar checkpoint

Ao terminar cada tarefa, notificar conclusão.
```

### Exemplo Prático

```
Retomando execução paralela de Tarefas 003-005.

Lendo: docs/agent/checkpoints/2026-06-26-fase4-task-g-milestone-3-progress.md

Tarefas:
- Tarefa 003: Data Leakage Prevention (12 testes) - Em progresso
- Tarefa 004: Bypass Attempts (10+ testes) - Awaiting
- Tarefa 005: Consolidação Final - Awaiting

Criar 3 subagents em paralelo:
1. Subagent 003: Continuar testes de data leakage
2. Subagent 004: Implementar bypass attempts (quando 003 terminar)
3. Subagent 005: Consolidação (quando 004 terminar)
```

---

## 📖 Caso 3: Ler Plano e Atualizar

### Cenário
Existe um plano em `docs/agent/plans/` que foi criado anteriormente. Você quer revisar, atualizar e executar.

### Prompt de Continuação

```
Vou revisar e atualizar o plano existente.

1. Ler plano: docs/agent/plans/[plano-id].md
2. Ler contexto: 
   - docs/architecture/02-VISION.md
   - docs/agent/README.md
3. Validar com: AGENTS.md, CLAUDE.md

Com base na revisão:
- Validar que o plano ainda é relevante
- Atualizar timeline/escopo se necessário
- Identificar bloqueadores
- Propor próximos passos

Depois de aprovação, criar tarefas a partir do plano atualizado.
```

### Exemplo Prático

```
Revisar plano da Fase 4.

Ler: docs/agent/plans/2026-06-fase4-multi-tenancy.md

Checklist de atualização:
✅ Status das tarefas 002-005
✅ Bloqueadores identificados
✅ Próximas tarefas após consolidação
✅ Timeline realista

Após revisão, criar tarefas para próximos milestones.
```

---

## ✏️ Caso 4: Ler Rascunho e Refinar Plano

### Cenário
Existe um rascunho de plano incompleto. Você quer refiná-lo seguindo convenções do projeto.

### Prompt de Continuação

```
Vou refinar o rascunho de plano.

1. Ler rascunho: docs/agent/plans/[rascunho].md
2. Ler guias de projeto:
   - docs/agent/prompts/00-garantir-consistencia-do-projeto.md
   - docs/architecture/02-VISION.md
   - AGENTS.md

Refinamento necessário:
- Estrutura: Título, Objetivo, Escopo, Tarefas, Timeline
- Validação: Compatibilidade com visão do projeto
- Detalhes: Dependências, bloqueadores, success criteria
- Convenções: Seguir CLAUDE.md e estilo do projeto

Após refinamento:
- Salvar plano atualizado
- Descrever mudanças
- Propor execução
```

### Exemplo Prático

```
Refinar rascunho de plano para Fase 5 (Performance & Optimization).

Ler:
- docs/agent/plans/RASCUNHO-fase5.md
- docs/architecture/02-VISION.md
- AGENTS.md

Estruturar:
1. Objetivo claro
2. Milestones com datas
3. Tarefas com critério de sucesso
4. Dependências entre tarefas
5. Timeline realista

Salvar como: docs/agent/plans/2026-07-fase5-performance.md
```

---

## ⚙️ Caso 5: Executar Tarefa Específica

### Cenário
Você sabe exatamente qual tarefa executar. Quer iniciar com contexto mínimo.

### Prompt de Continuação

```
Executar Tarefa: [ID-da-Tarefa]

Lendo contexto:
- docs/agent/plans/[plano].md (se existir)
- docs/agent/checkpoints/[checkpoint].md
- AGENTS.md para regras

Tarefa: [Nome descritivo]
Objetivo: [O que será feito]
Escopo: [Limites, o que não fazer]
Success Criteria: [Como validar conclusão]

Respeitar:
- AGENTS.md (rules, patterns, conventions)
- CLAUDE.md (code style, language)
- UNIVERSAL-CODE-STYLE-RULES.md (code formatting)

Ao terminar: Fazer commit e atualizar checkpoint.
```

### Exemplo Prático

```
Executar Tarefa 006: Performance Optimization for Multi-Tenancy

Lendo:
- docs/agent/plans/2026-07-fase5-performance.md
- docs/agent/checkpoints/2026-06-fase4-FINAL-COMPLETE.md
- AGENTS.md

Tarefa: Implementar índices de tenant_id + caching
Objetivo: Reduzir latência de queries multi-tenant por 50%
Escopo: Índices database + query caching apenas
Success: Testes de performance passando, latência < 100ms

Execução com subagents se múltiplos componentes.
```

---

## 📊 Caso 6: Executar Plano Completo

### Cenário
Um plano inteiro está pronto. Você quer iniciar a execução ordenada ou paralela.

### Prompt de Continuação

```
Executar Plano Completo: [Plano-ID]

1. Ler plano: docs/agent/plans/[plano].md
2. Validar estrutura e dependências
3. Identificar tarefas paralelas vs sequenciais
4. Ler AGENTS.md para estratégia de execução

Estratégia:
- Tarefas sequenciais: Executar uma por uma
- Tarefas paralelas: Criar subagents
- Tarefas bloqueantes: Aguardar conclusão

Para cada tarefa:
- Criar ou reusar subagent
- Passar instruções claras
- Executar em background
- Atualizar checkpoint ao terminar

Ao terminar plano: Compilar relatório de execução
```

### Exemplo Prático

```
Executar Plano Completo: docs/agent/plans/2026-07-fase5-performance.md

Lendo plano...

Tarefas Identificadas:
1. Task 5.1: Índices database (sequencial, 2h)
2. Task 5.2: Query caching (paralelo a 5.1, 3h)
3. Task 5.3: Teste de performance (sequencial, deps: 5.1+5.2, 1h)

Execução:
- Subagent A: Task 5.1 (índices) - Start immediately
- Subagent B: Task 5.2 (caching) - Start immediately (paralelo)
- Subagent C: Task 5.3 (testes) - Start após A+B completos

Acompanhamento em tempo real. Notificação ao fim.
```

---

## 🔄 Fluxo de Recuperação Padrão

Quando retomar após `/clear` ou nova sessão, seguir este fluxo:

```
1. LER ARQUIVOS (não pular!)
   ├─ docs/agent/prompts/00-garantir-consistencia-do-projeto.md
   ├─ docs/architecture/02-VISION.md
   └─ docs/agent/README.md

2. ENTENDER STATUS
   ├─ git log --oneline -20 (últimos commits)
   ├─ git status (mudanças pendentes)
   ├─ docs/agent/checkpoints/ (checkpoint mais recente)
   └─ docs/agent/plans/ (planos ativos)

3. DECIDIR PRÓXIMO PASSO
   ├─ Continuar tarefa em progresso? (Caso 1)
   ├─ Retomar múltiplas tarefas? (Caso 2)
   ├─ Revisar plano? (Caso 3)
   ├─ Refinar plano? (Caso 4)
   ├─ Executar tarefa específica? (Caso 5)
   └─ Executar plano completo? (Caso 6)

4. EXECUTAR COM CONTEXTO
   ├─ Incluir referências de arquivo
   ├─ Respeitar AGENTS.md + CLAUDE.md
   ├─ Fazer commits após cada evolução
   └─ Atualizar checkpoints

5. VALIDAR CONCLUSÃO
   ├─ Testes passando?
   ├─ Code style OK?
   ├─ Commits e mensagens claros?
   └─ Checkpoint atualizado?
```

---

## 📝 Templates Prontos para Copiar/Colar

### Template 1: Continuar Tarefa Única
```
Continuar Tarefa: [TAREFA-ID]

Checkpoint: docs/agent/checkpoints/[data-checkpoint].md
Plano: docs/agent/plans/[tarefa-id].md

Lendo contexto e status anterior...

Próximo passo: [O que será feito agora]
Critério de sucesso: [Como validar]
```

### Template 2: Retomar Múltiplas Tarefas
```
Retomar Tarefas em Paralelo: [IDs]

Status anterior: docs/agent/checkpoints/[data-status].md
Tarefas não-bloqueantes: [Lista]
Tarefas bloqueantes: [Lista com deps]

Criar subagents:
1. [Tarefa A] - [Descrição]
2. [Tarefa B] - [Descrição]

Executar em paralelo. Notificar ao terminar.
```

### Template 3: Refinar Plano
```
Refinar Plano: [rascunho.md]

Ler: docs/architecture/02-VISION.md, AGENTS.md

Estrutura esperada:
- Objetivo e Escopo
- Milestones com datas
- Tarefas com critério de sucesso
- Dependências
- Timeline

Salvar como: docs/agent/plans/[nome-final].md
```

---

## ⚠️ Erros Comuns (Não Fazer!)

❌ **Não pule a leitura de AGENTS.md** - Contexto é essencial  
❌ **Não crie tarefas sem plano** - Sem rumo, sem propósito  
❌ **Não ignore checkpoints** - São record of truth  
❌ **Não mixe convenções** - Siga UNIVERSAL-CODE-STYLE-RULES.md  
❌ **Não faça commits sem contexto** - Mensagens devem ser claras  

---

## 📞 Quando Usar Qual Prompt?

| Situação | Prompt | Caso |
|----------|--------|------|
| Terminou sessão, mesma tarefa | Continuar Tarefa Única | 1 |
| Precisa retomar 3+ tarefas | Múltiplos Subagents | 2 |
| Plano existe, precisa revisar | Ler & Atualizar Plano | 3 |
| Rascunho incompleto | Refinar Plano | 4 |
| Sabe exata tarefa | Executar Tarefa | 5 |
| Plano pronto, começar | Executar Plano | 6 |

---

**Última atualização:** 2026-06-26  
**Responsável:** Documentação de Continuidade  
**Status:** Pronto para Uso
