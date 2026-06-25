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



# Leitura obrigatória

Antes de qualquer análise, leia obrigatoriamente, nesta ordem:

1. `docs/architecture/00-START-HERE.md`
2. Todos os documentos referenciados por ele.
3. `README.md`
4. `AGENTS.md`
5. `CLAUDE.md`
6. `docs/agent/README.md`

Em especial, compreenda profundamente:

* `docs/architecture/02-VISION.md`
* `docs/architecture/03-FIRST-CUSTOMER.md`
* `docs/architecture/04-CURRENT-DIRECTION.md`
* `docs/architecture/05-DECISION-FRAMEWORK.md`

Esses documentos possuem prioridade superior a qualquer plano existente.

Caso exista conflito entre um plano e a arquitetura, **a arquitetura prevalece**.

---

# Escopo da atividade

Analise cuidadosamente:

* `docs/agent/plans`
* `docs/agent/tasks`
* `docs/agent/doing`
* `docs/agent/paused`

Considere também o estado atual do código.

O objetivo é fazer com que toda a documentação de planejamento represente fielmente o projeto como ele deve evoluir hoje.

---

# O que deve ser analisado

Para cada plano e cada tarefa, responda internamente:

* Ainda faz sentido?
* Ainda está alinhado com a arquitetura?
* Ainda está alinhado com a V1?
* Ainda agrega valor?
* Já foi implementado?
* Está duplicado?
* Está desatualizado?
* Está planejando uma abstração prematura?
* Está tentando resolver um problema futuro que ainda não existe?
* Está grande demais?
* Está pequena demais?
* Está no diretório correto?

---

# Regras de decisão

## Remover

Remova documentos que:

* descrevem funcionalidades já concluídas e sem valor documental;
* estejam totalmente obsoletos;
* contradigam a arquitetura atual;
* representem ideias abandonadas.

---

## Mover

Mova documentos para:

* `done/` quando estiverem concluídos;
* `paused/` quando dependerem de decisão futura;
* `plans/` quando representarem apenas estratégia;
* `tasks/` quando forem unidades executáveis.

---

## Renomear

Padronize nomes quando necessário.

Evite nomes genéricos.

Os nomes devem permitir compreender rapidamente seu objetivo.

---

## Dividir

Quando um plano for grande demais, divida-o em tarefas menores.

---

## Unificar

Quando houver vários documentos tratando exatamente do mesmo assunto, consolide-os em um único documento.

Evite duplicação de planejamento.

---

## Atualizar

Atualize documentos quando:

* a arquitetura mudou;
* o domínio amadureceu;
* o escopo da V1 mudou;
* existirem informações antigas.

Preserve apenas o que continua válido.

---

# Restrições ao planejar

Durante esta atividades de planejamento/criação de planos/tarefas é proibido:

* alterar código-fonte;
* alterar banco de dados;
* criar novas funcionalidades;
* modificar arquitetura;
* criar novos módulos;
* criar novos packages.

Em atividades de planejamento pode alterar apenas arquivos e pastas do planejamento (plans, tasks, etc)

---

# V1 é a prioridade

Sempre utilize como referência a definição da primeira versão do produto.

A prioridade atual é entregar um produto excelente para o primeiro instrutor autônomo.

Não mantenha planos relacionados a:

* múltiplos produtos;
* abstrações preventivas;
* escalabilidade prematura;
* funcionalidades fora do escopo da V1.

Caso esses documentos possuam valor histórico, mova-os para uma área apropriada de arquivamento ou marque-os claramente como futuras iniciativas.

---

# Resultado esperado

Ao final da atividade:

* o backlog deve estar limpo;
* os planos devem refletir apenas a estratégia atual;
* as tarefas devem representar somente trabalho executável;
* não deve haver duplicações;
* não deve haver tarefas sem objetivo claro;
* não deve haver planos conflitantes com a arquitetura;
* toda a documentação deve estar alinhada com a visão do projeto.

Antes de encerrar, gere um relatório em Markdown contendo:

1. Planos removidos (com justificativa).
2. Planos renomeados.
3. Planos consolidados.
4. Novas tarefas criadas.
5. Tarefas removidas.
6. Tarefas movidas entre diretórios.
7. Inconsistências encontradas.
8. Sugestões de melhoria para o backlog.
9. Riscos identificados.
10. Avaliação final da qualidade do planejamento atual.

O relatório deve permitir revisar todas as decisões tomadas durante a curadoria.
