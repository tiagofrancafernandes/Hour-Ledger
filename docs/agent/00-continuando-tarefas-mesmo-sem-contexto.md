# Prompts para Continuar Tarefas

## Índice

1. [Continuar tarefa em andamento](#para-continuar-uma-tarefa-em-andamento)
2. [Iniciar múltiplas tarefas](#para-iniciar-múltiplas-tarefas-em-paralelo)
3. [Revisar plano existente](#para-revisar-e-atualizar-um-plano-existente)
4. [Refinar rascunho de plano](#para-refinar-um-rascunho-de-plano)
5. [Executar tarefa específica](#para-executar-uma-tarefa-específica)
6. [Executar plano completo](#para-executar-um-plano-completo)
7. [Resolver bloqueador](#para-resolver-um-bloqueador)
8. [Cleanup de código](#para-fazer-cleanup-de-código-antes-de-commit)
9. [Gerar checkpoint](#para-gerar-checkpoint-após-completar-tarefa)

---

## Para continuar uma tarefa em andamento:

```
Ler checkpoint: docs/agent/checkpoints/[date].md | Ler plano: docs/agent/plans/[tarefa-id].md | Status: git log -5 | Próximo passo: [o que será feito] | Respeitar AGENTS.md, CLAUDE.md, UNIVERSAL-CODE-STYLE-RULES.md | Fazer commit ao terminar
```

## Para iniciar múltiplas tarefas em paralelo:

```
Ler checkpoint: docs/agent/checkpoints/[date].md | Criar subagents para tarefas: [lista] | Cada subagent: ler AGENTS.md, fazer commit ao terminar, atualizar checkpoint | Executar em background
```

## Para revisar e atualizar um plano existente:

```
Ler plano: docs/agent/plans/[plan-id].md | Validar com VISION (docs/architecture/02-VISION.md) e AGENTS.md | Atualizar timeline/escopo se necessário | Identificar bloqueadores | Salvar versão atualizada | Propor execução
```

## Para refinar um rascunho de plano:

```
Ler rascunho: docs/agent/plans/[rascunho].md | Validar com docs/architecture/02-VISION.md e AGENTS.md | Estruturar: objetivo, milestones, tarefas, deps, timeline | Respeitar CLAUDE.md | Salvar como: docs/agent/plans/[final-name].md
```

## Para executar uma tarefa específica:

```
Tarefa: [ID] | Objetivo: [o que será feito] | Escopo: [limites] | Ler checkpoint/plano se existir | Respeitar AGENTS.md, CLAUDE.md, UNIVERSAL-CODE-STYLE-RULES.md | Fazer commit ao terminar | Atualizar checkpoint
```

## Para executar um plano completo:

```
Plano: docs/agent/plans/[plan-id].md | Ler e validar estrutura | Identificar tarefas paralelas vs sequenciais | Criar subagents conforme necessário | Executar em background | Notificar ao terminar | Compilar relatório
```

## Para resolver um bloqueador:

```
Bloqueador: [descrição] | Arquivo: [caminho] | Linha: [número] | Contexto: [2-3 linhas de código] | Erro: [mensagem completa] | Tentativas: [o que foi testado] | Respeitar código style do projeto
```

## Para fazer cleanup de código antes de commit:

```
Arquivo: [caminho] | Validar: UNIVERSAL-CODE-STYLE-RULES.md | Remover: variáveis não usadas, imports não usados, comments desnecessários | Indentação: 4 espaços | Nomes: descritivos | Rodar testes | Fazer commit com contexto claro
```

## Para gerar checkpoint após completar tarefa:

```
Tarefa: [ID] | Status: COMPLETO | Testes: [X/X passando] | Assertions: [número] | Commits: [lista de hashes] | Bloqueadores: [nenhum/descrição] | Timeline: [quanto tempo levou] | Próximos passos: [o que vem depois]
```

---

**Dica:** Copie o prompt completo (1 linha) e cole no Claude Code. Adapt [placeholders] conforme necessário.
