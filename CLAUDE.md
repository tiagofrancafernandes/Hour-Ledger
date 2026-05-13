# CLAUDE.md

## Instrução principal

Antes de planejar ou alterar qualquer código, leia `AGENTS.md`.

`AGENTS.md` é a fonte principal de regras do projeto.

Este arquivo contém apenas instruções específicas para uso com Claude Code.

## Uso com Claude Code

- Use modo de planejamento para tarefas não triviais.
- Não edite código durante a etapa de planejamento.
- Quando a tarefa tiver múltiplas etapas, crie ou atualize um plano em `docs/agent/plans/`.
- Após aprovação, execute uma milestone por vez.
- Atualize `docs/agent/checkpoints/` ao concluir cada milestone.
- Ao retomar uma tarefa, leia o plano aprovado e o checkpoint atual antes de continuar.
- Antes de alterar arquitetura, tenancy, ledger, wallet, autenticação ou permissões, apresente plano e aguarde aprovação.

## Prioridade de instruções

1. Instruções explícitas do usuário na conversa atual.
2. Este arquivo, apenas para comportamento específico do Claude Code.
3. `AGENTS.md`, como fonte principal de regras do projeto.
4. Código existente e padrões do repositório.
5. Documentação local em `docs/knowledge/`.

Se houver conflito entre este arquivo e `AGENTS.md`, informe o conflito antes de executar.
