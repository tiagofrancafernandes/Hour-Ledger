# GEMINI.md

## Instrução principal

Antes de planejar ou alterar qualquer código, leia `AGENTS.md`.

`AGENTS.md` é a fonte principal de regras do projeto.

Este arquivo contém diretrizes e instruções específicas para uso com Google Gemini / Antigravity / Gemini CLI.

Leia `docs/architecture/00-START-HERE.md` e siga todas as referências indicadas antes de executar cada tarefa.

Para entender bem o objetivo desse projeto, leia `docs/architecture/02-VISION.md` pois nele constam definições para os planos atuais e futuros.

---

## Uso com Gemini / Antigravity

- Inspecione sempre o código e a estrutura existente antes de propor ou realizar mudanças.
- Para tarefas não triviais ou com múltiplas etapas:
  - Crie ou atualize o plano de ação em `docs/agent/plans/`.
  - Divida a execução em milestones pequenas.
  - Aguarde aprovação prévia antes de alterar arquitetura central, tenancy, ledger, wallet, autenticação, permissões, infraestrutura Docker ou pipelines de CI/CD.
  - Execute uma milestone por vez.
  - Atualize os checkpoints em `docs/agent/checkpoints/` ao concluir cada etapa.
  - Ao retomar uma tarefa, leia o plano aprovado e o checkpoint atual antes de prosseguir.
- Respeite rigorosamente os limites e fronteiras arquiteturais do monorepo:
  - O **HL Core** é genérico e reutilizável; ele **nunca** deve conhecer nem depender de produtos específicos (como HL Drive ou HL Consulting).
  - Regras de negócio de produtos devem residir exclusivamente em seus domínios.
- Ao criar ou alterar rotas no backend, gere ou atualize os respectivos arquivos de requisição de demonstração (`.http`) em `backend/dev-contents/demo-requests/` com o sufixo `-demo`, usando `tenants-demo.http` como template base.
- Sempre mantenha as documentações e o índice geral (`docs/README.md`) atualizados ao criar ou modificar recursos ou rotas no ecossistema.
- Crie documentações acompanhadas de exemplos práticos sempre que possível (especialmente requisições HTTP em arquivos `.http` em `backend/dev-contents/demo-requests/` ou comandos `curl`) e mantenha-os rigorosamente atualizados e funcionais.

---

## Diretrizes de Código e Estilo

Todo código gerado, modificado ou refatorado deve seguir rigorosamente:

- **`UNIVERSAL-CODE-STYLE-RULES.md`**:
  - Retornos antecipados (*early returns* / *guard clauses*).
  - Padrão sem `else` (*else-less*) e sem blocos aninhados desnecessários.
  - Chaves obrigatórias e escopos explícitos (nunca use comandos de controle em linha única).
  - Separação de blocos lógicos por linhas em branco para garantir legibilidade vertical.
  - Fail-fast na validação de entradas e ausência de comportamento implícito.

---

## Resposta Final Obrigatória

Ao finalizar uma tarefa, forneça sempre um relatório com a seguinte estrutura:

1. **Resumo do que foi feito**
2. **Arquivos alterados**
3. **Comandos executados**
4. **Resultado dos testes**
5. **Pendências**
6. **Riscos ou pontos de atenção**

---

## Prioridade de Instruções

1. Instruções explícitas do usuário na conversa atual.
2. Este arquivo (`GEMINI.md`), para diretrizes específicas do ambiente Gemini / Antigravity.
3. `AGENTS.md`, como fonte principal de regras do projeto.
4. `UNIVERSAL-CODE-STYLE-RULES.md`, como padrão inegociável de código.
5. Código existente e padrões estabelecidos no repositório.
6. Documentação local em `docs/architecture/` e `docs/knowledge/`.

Se houver qualquer divergência ou conflito entre este arquivo e `AGENTS.md`, consulte o usuário ou reporte a dúvida antes de prosseguir.

---

<!-- context7 -->
Use the `ctx7` CLI to fetch current documentation whenever the user asks about a library, framework, SDK, API, CLI tool, or cloud service -- even well-known ones like React, Next.js, Prisma, Express, Tailwind, Django, or Spring Boot. This includes API syntax, configuration, version migration, library-specific debugging, setup instructions, and CLI tool usage. Use even when you think you know the answer -- your training data may not reflect recent changes. Prefer this over web search for library docs.

Do not use for: refactoring, writing scripts from scratch, debugging business logic, code review, or general programming concepts.

## Steps

1. Resolve library: `npx ctx7@latest library <name> "<user's question>"` — use the official library name with proper punctuation (e.g., "Next.js" not "nextjs", "Customer.io" not "customerio", "Three.js" not "threejs")
2. Pick the best match (ID format: `/org/project`) by: exact name match, description relevance, code snippet count, source reputation (High/Medium preferred), and benchmark score (higher is better). If results don't look right, try alternate names or queries (e.g., "next.js" not "nextjs", or rephrase the question)
3. Fetch docs: `npx ctx7@latest docs <libraryId> "<user's question>"`
4. Answer using the fetched documentation

You MUST call `library` first to get a valid ID unless the user provides one directly in `/org/project` format. Use the user's full question as the query -- specific and detailed queries return better results than vague single words. Do not run more than 3 commands per question. Do not include sensitive information (API keys, passwords, credentials) in queries.

For version-specific docs, use `/org/project/version` from the `library` output (e.g., `/vercel/next.js/v14.3.0`).

If a command fails with a quota error, inform the user and suggest `npx ctx7@latest login` or setting `CONTEXT7_API_KEY` env var for higher limits. Do not silently fall back to training data.
<!-- context7 -->
