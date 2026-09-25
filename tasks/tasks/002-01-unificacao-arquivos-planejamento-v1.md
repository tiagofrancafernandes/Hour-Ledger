# Tarefa: Unificação dos Arquivos de Planejamento e Limpeza da Raiz

**Plano Relacionado:** [tasks/plans/002-01-unificacao-arquivos-planejamento-v1.md](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/tasks/plans/002-01-unificacao-arquivos-planejamento-v1.md)
**Status:** 🟡 Planejado
**Criado em:** 2026-09-25 20:34
**Origem:** [tasks/drafts/tasks/draft-unificacao-dos-arquivos-de-planejamento.md](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/tasks/drafts/tasks/draft-unificacao-dos-arquivos-de-planejamento.md)
**Responsável:** Tiago França / Equipe Hour Ledger Ecosystem

---

## 1. Contexto

Na raiz do repositório existem diversos relatórios pontuais, resumos e arquivos de transição gerados ao longo de iterações passadas (Fase 3, Fase 4, Beta Launch, etc.). Esses arquivos na raiz poluem a visão executiva e dificultam a identificação do estado real e atualizado do projeto.

Esta tarefa tem como objetivo analisar o conteúdo desses arquivos, resgatar e consolidar planos ativos, débitos técnicos e itens pendentes para o diretório oficial `tasks/`, arquivando ou removendo os relatórios obsoletos da raiz.

---

## 2. Arquivos Mapeados para Análise e Unificação

| Arquivo na Raiz | Descrição / Conteúdo Principal | Destino Recomendado |
|---|---|---|
| `BETA-LAUNCH-FINAL-REPORT.md` | Relatório final do lançamento Beta (Junho/2026) | Consolidar marcos em histórico e remover da raiz |
| `BETA-STATUS.md` | Status da versão Beta | Consolidar status e remover |
| `EXECUTION-SUMMARY-2026-07-04.md` | Resumo de execução de 04/07/2026 | Arquivar / remover |
| `FASE-3-FINAL-REPORT.md` | Relatório final da Fase 3 | Arquivar / remover |
| `FASE-4-MULTI-TENANCY-FINAL-REPORT.md` | Relatório da Fase 4 de multi-tenancy | Migrar decisões de arquitetura para `docs/architecture/` e remover |
| `PROGRESSO-ATUAL.txt` | Notas rápidas de progresso legado | Consolidar pendências em `tasks/doing/` e remover |
| `PROJECT-COMPLETION-SUMMARY.md` | Resumo de conclusão do projeto legado | Arquivar / remover |
| `ROADMAP.md` | Roadmap geral da raiz | Unificar com `tasks/plans/` |
| `TAREFA_G_SUMMARY.md` | Resumo da Tarefa G legada | Arquivar / remover |
| `TECHNICAL-DEBT-ROADMAP.md` | Débitos técnicos e melhorias pendentes | Converter em planos/tarefas em `tasks/plans/` |
| `TECHNICAL-TRANSITION-REPORT.md` | Relatório detalhado de transição técnica | Arquivar em `docs/knowledge/` ou remover |
| `TRANSITION-QUICK-REFERENCE.md` | Guia rápido de referência da transição | Consolidar em `docs/knowledge/` e remover |

---

## 3. Escopo de Trabalho (Checklist de Execução)

- [ ] **Fase 1: Triagem e Extração de Conteúdo Útil**
  - [ ] Ler `TECHNICAL-DEBT-ROADMAP.md` e criar tarefas estruturadas em `tasks/plans/` para débitos técnicos relevantes.
  - [ ] Ler `ROADMAP.md` e unificar as metas com os planos de produto do ecossistema (`HL Drive`, `HL Core`, `HL Consulting`).
  - [ ] Mapear quaisquer decisões arquiteturais em `FASE-4-MULTI-TENANCY-FINAL-REPORT.md` que ainda não constem em `docs/architecture/`.

- [ ] **Fase 2: Arquivamento / Remoção dos Arquivos da Raiz**
  - [ ] Remover relatórios de execução diária obsoletos (`EXECUTION-SUMMARY-2026-07-04.md`, `PROGRESSO-ATUAL.txt`).
  - [ ] Remover relatórios de fechamento de fase antigos (`BETA-LAUNCH-FINAL-REPORT.md`, `BETA-STATUS.md`, `FASE-3-FINAL-REPORT.md`, `FASE-4-MULTI-TENANCY-FINAL-REPORT.md`, `PROJECT-COMPLETION-SUMMARY.md`, `TAREFA_G_SUMMARY.md`).
  - [ ] Mover/arquivar relatórios de transição necessários para documentação permanente em `docs/history/` ou `docs/knowledge/`.

- [ ] **Fase 3: Verificação e Validação**
  - [ ] Validar que nenhum link interno de documentação foi quebrado.
  - [ ] Garantir que o diretório `tasks/` reflita com precisão o estado real do projeto (Plano 001 HL Drive e Plano 002 Unificação).
  - [ ] Atualizar referências no `README.md` se aplicável.

---

## 4. Critérios de Aceitação

- Raiz do repositório limpa, sem relatórios temporários de execuções passadas.
- Todo débito técnico e item de roadmap devidamente registrado no formato oficial de `tasks/README.md`.
- Nenhuma informação de arquitetura ou requisito de negócio perdida no processo.
- Testes automatizados continuam passando integralmente.

---

## 5. Histórico e Progresso

- **2026-09-25 20:34**: Tarefa estruturada a partir do draft em `tasks/drafts/tasks/draft-unificacao-dos-arquivos-de-planejamento.md`.
