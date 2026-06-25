# Gerenciamento de Tarefas

Este diretório contém o planejamento e execução das atividades do projeto.

## Entendendo o projeto

Leia docs/architecture/00-START-HERE.md e siga todas as referências indicadas antes de executar cada tarefa.

Para entender bem o objetivo desse projeto, leia docs/architecture/02-VISION.md pois nele tem definição de planos para agora e planos futuro.

## Estrutura

```text
docs/agent/
├── README.md
├── plans/
├── tasks/
├── paused/
├── doing/
└── done/
```

### plans/

Contém documentos de planejamento.

Arquivos nesta pasta descrevem:

* objetivos
* contexto
* regras de negócio
* escopo
* estratégia de implementação
* critérios de aceite

Os arquivos em `plans/` não representam trabalho executável diretamente.

Eles servem como base para criação das tarefas.

### tasks/

Contém tarefas executáveis.

Cada tarefa deve estar associada a um plano.

As tarefas representam unidades de trabalho que podem ser executadas individualmente.

### paused/

Contém tarefas pausadas.

Utilizar esta pasta quando:

* houver bloqueios externos
* faltar informação para continuidade
* depender de decisão de negócio
* depender de outra tarefa

### doing/

Contém tarefas em andamento.

Utilizar esta pasta quando:

* a tarefa está em execução (começada, mas não finalizada ainda, mesmo que em pensamento/tinking)

### done/

Contém tarefas concluídas.

Ao concluir uma tarefa:

* mover para `done/`
* ou remover o arquivo caso ele não tenha mais valor histórico

---

# Ordem de Leitura

Antes de executar qualquer tarefa, analisar obrigatoriamente:

1. README.md
2. docs/agent/README.md
3. AGENTS.md
4. CLAUDE.md

Sempre respeite as regras de codificação contida em UNIVERSAL-CODE-STYLE-RULES.md (Code Guidelines and Code Style Rules)

Esses arquivos definem regras globais do projeto e possuem prioridade sobre qualquer instrução local.

---

# Convenção de Nomes

## Planos

Formato:

```text
nome-do-plano.md
```

Exemplo:

```text
migracao-das-funcionalidades-para-a-v2.md
```

---

## Tarefas

Formato:

```text
NNN-nome-do-plano-descricao-da-tarefa.md
```

Onde:

* NNN é um número sequencial
* nome-do-plano referencia o plano relacionado

Exemplos:

```text
001-migracao-das-funcionalidades-para-a-v2-cadastro-de-clientes.md

002-migracao-das-funcionalidades-para-a-v2-listagem-de-clientes.md

003-migracao-das-funcionalidades-para-a-v2-detalhes-do-cliente.md
```

A numeração deve refletir a ordem recomendada de execução.

---

# Planos em Rascunho

Planos com prefixo:

```text
DRAFT-
```

ou

```text
RASCUNHO-
```

não devem ser implementados.

Antes de criar tarefas para esses planos:

1. analisar o plano
2. analisar README.md
3. analisar docs/agent/README.md
4. analisar AGENTS.md
5. analisar CLAUDE.md

Após a análise:

* refinar o plano
* adequar sua estrutura ao padrão do projeto
* remover ambiguidades
* definir critérios de aceite
* definir escopo claramente

Somente após esse refinamento o prefixo poderá ser removido.

Enquanto o prefixo existir:

* não criar tarefas
* não implementar código
* não iniciar execução

---

# Processo de Trabalho

## 1. Analisar o plano

Compreender:

* objetivo
* contexto
* regras de negócio
* dependências
* riscos

## 2. Criar tarefas

Quebrar o plano em tarefas menores.

Cada tarefa deve possuir:

* objetivo claro
* escopo limitado
* critérios de aceite

## 3. Executar tarefas

Executar uma tarefa por vez.

Sempre validar:

* backend
* frontend
* integrações
* banco de dados
* regras de negócio

## 4. Finalizar

Ao concluir:

* mover para `done/`
* atualizar tarefas dependentes quando necessário

---

# Regras Importantes

* Não implementar diretamente a partir de um plano.
* Sempre criar tarefas antes da implementação.
* Não executar planos marcados como DRAFT ou RASCUNHO.
* Preservar histórico de decisões relevantes.
* Priorizar compreensão da regra de negócio antes da implementação.
* Em caso de dúvida, utilizar banco de dados, código legado e documentação como fontes de verdade.
* Utilizar Context7 para consultar documentação atualizada de Laravel, Vue, Nuxt e Inertia quando necessário.
