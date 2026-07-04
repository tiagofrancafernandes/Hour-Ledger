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

* `docs/agent/done/` quando estiverem concluídos;
* `docs/agent/paused/` quando dependerem de decisão futura;
* `docs/agent/plans/` quando representarem apenas estratégia;
* `docs/agent/tasks/` quando forem unidades executáveis.

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

# Restrições

Durante esta atividade é proibido:

* alterar código-fonte;
* alterar banco de dados;
* criar novas funcionalidades;
* modificar arquitetura;
* criar novos módulos;
* criar novos packages.

Esta atividade é exclusivamente de organização do backlog.

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
