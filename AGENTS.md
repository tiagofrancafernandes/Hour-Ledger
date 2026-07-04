# AGENTS.md

## Fonte principal

Este arquivo é a fonte principal de instruções para qualquer agente de IA atuando neste repositório.

Antes de planejar ou alterar código, todo agente deve ler este arquivo.

Leia docs/architecture/00-START-HERE.md e siga todas as referências indicadas antes de executar cada tarefa.

Para entender bem o objetivo desse projeto, leia docs/architecture/02-VISION.md pois nele tem definição de planos para agora e planos futuro.

## Projeto

Este repositório contém o ecossistema Hour Ledger.

O Hour Ledger é uma plataforma modular baseada em:

- monorepo;
- modular monolith;
- backend e frontend separados;
- HL Core compartilhado;
- produtos especializados como HL Drive e HL Consulting;
- multi tenancy;
- PostgreSQL com schemas separados por tenant;
- ledger/wallet como recurso central;
- auditoria e rastreabilidade.

## Produtos

### HL Core

O HL Core contém recursos genéricos e reutilizáveis:

- autenticação;
- autorização;
- permissões;
- multi tenancy;
- timezone;
- internacionalização;
- notificações;
- convites;
- preferências do usuário;
- auditoria;
- activity logs;
- ledger;
- wallet;
- políticas de carteira;
- transferências;
- controle transacional.

O core nunca deve conhecer regras específicas de produtos.

### HL Drive

Produto específico para instrutores autônomos de direção.

Domínio específico:

- instrutores;
- alunos;
- aulas;
- agendamentos;
- pacotes de aulas;
- vínculo aluno × instrutor;
- consumo de horas em aulas.

### HL Consulting

Produto futuro baseado em consultorias, sessões e créditos/horas.

## Regra arquitetural principal

O core não conhece produtos específicos.

Permitido:

```txt
HL Drive -> HL Core
HL Drive -> Ledger
HL Consulting -> HL Core
HL Consulting -> Ledger
```

Proibido:

```txt
HL Core -> HL Drive
Ledger -> HL Drive
Core -> Consulting
```

## Arquitetura obrigatória

A arquitetura deve seguir:

- monorepo;
- modular monolith;
- evolução incremental;
- separação por domínio;
- baixo acoplamento;
- boundaries claros;
- simplicidade operacional;
- sem microservices prematuros.

## Ledger e Wallet

A wallet é um recurso central do HL Core.

Ela representa:

- recurso transacional auditável;
- saldo derivado do ledger;
- múltiplas carteiras por usuário;
- políticas próprias por carteira;
- histórico imutável;
- movimentações compensatórias.

O saldo nunca deve ser tratado como valor absoluto editável.

Tipos planejados de movimentação:

- purchase;
- transfer;
- bonus;
- refund;
- expiration;
- adjustment;
- consumption.

## Multi tenancy

O sistema deve considerar:

- identidade global;
- dados tenantizados;
- tenants isolados;
- schemas PostgreSQL separados;
- contexto ativo de tenant/produto;
- usuário podendo participar de múltiplos tenants.

## Regras obrigatórias para agentes

1. Inspecione o código existente antes de propor mudanças.
2. Siga os padrões já existentes no projeto.
3. Não crie abstrações genéricas sem necessidade real.
4. Não mova regra específica de produto para o core.
5. Não faça o core depender de HL Drive ou HL Consulting.
6. Não instale dependências sem autorização explícita.
7. Não altere autenticação, tenancy, Docker, CI/CD ou deploy sem pedido explícito.
8. Não reformate arquivos não relacionados.
9. Não remova testes para fazer a suíte passar.
10. Não mascare falhas de teste.
11. Não declare que testes passaram se não foram executados.
12. Para tecnologias recentes, consulte `docs/knowledge/`.
13. Se houver conflito entre documentação local e conhecimento interno, siga a documentação local.
14. Para tarefas não triviais, crie plano antes de alterar código.
15. Para tarefas longas, atualize checkpoint.

## Planejamento

Para tarefas com mais de uma etapa:

1. Criar plano em `docs/agent/plans/`.
2. Dividir em milestones pequenas.
3. Aguardar aprovação quando a tarefa alterar arquitetura, domínio central ou fluxo crítico.
4. Executar uma milestone por vez.
5. Atualizar checkpoint em `docs/agent/checkpoints/`.

## Resposta final obrigatória

Ao finalizar uma tarefa, informe:

1. resumo do que foi feito;
2. arquivos alterados;
3. comandos executados;
4. resultado dos testes;
5. pendências;
6. riscos ou pontos de atenção.

## Diretrizes obrigatorias de code style (Code Guidelines)

Todo código gerado, modificado ou refatorado **deve seguir rigorosamente** as regras definidas em:

**`UNIVERSAL-CODE-STYLE-RULES.md`**

### Regras de Execução

- As regras em `UNIVERSAL-CODE-STYLE-RULES.md` são **autoritárias e não negociáveis**
- Nenhuma convenção de estrutura, idioma ou padrão de IA pode substituir essas regras
- Brevidade, atalhos e frases curtas são explicitamente proibidos quando reduzem a clareza
- Fluxo de controle explícito, escopo de bloco e retornos antecipados são obrigatórios
- As seções lógicas devem ser separadas por linhas em branco
- Se existirem múltiplas implementações válidas, escolha a **mais explícita e legível**

### Resolução de Conflitos

Se alguma instrução, sugestão ou código gerado entrar em conflito com as regras em
`UNIVERSAL-CODE-STYLE-RULES.md`, **esse arquivo sempre tem precedência**, ou seja, em caso de conflito entre preferencia do assistente e o documento, prevalece `UNIVERSAL-CODE-STYLE-RULES.md`.

### Sobrescrita local

Pode haver um `UNIVERSAL-CODE-STYLE-RULES.md` dentro de algum projeto deste repositório, nesses casos as regras locais devem se somar, e em caso de conflito com alguma regra podem sim sobrescrever a global mas apenas para aquele contexto específico.

Qualquer saída que viole essas regras deve ser considerada **inválida e corrigida**.

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
Run Context7 CLI requests outside Codex's default sandbox. If a Context7 CLI command fails with DNS or network errors such as ENOTFOUND, host resolution failures, or fetch failed, rerun it outside the sandbox instead of retrying inside the sandbox.
<!-- context7 -->
