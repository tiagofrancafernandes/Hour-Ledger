# Iniciativas Pós-V1 (Arquivadas durante Architecture Freeze)

## ⚠️ Importante

Os arquivos neste diretório foram **arquivados** durante o ARCHITECTURE FREEZE do projeto Hour Ledger.

Eles representam ideias e spikes para explorar **APÓS** a conclusão e validação do HL Drive V1.

**Nenhum dos planos aqui deve ser implementado durante o freeze.**

---

## Planos Arquivados

### 1. Extração Progressiva do HL Core

**Arquivo**: `2026-05-13-extract-hl-core-from-current-app.md`

**Objetivo**: Planejar a extração de módulos genéricos do core compartilhado a partir da aplicação atual de forma incremental.

**Por que foi arquivado**:
- Cria novos packages (proibido durante freeze)
- Baseado em expectativa de reutilização futura (abstração preventiva)
- Apenas justificado após HL Drive V1 estar completo e validado

**Quando retomar**:
- Após validação do HL Drive V1 com primeiro cliente
- Quando segunda necessidade comprovada surgir
- Com aprovação da arquitetura para sair do freeze

---

### 2. Extração de Ledger/Wallet para Package Compartilhado

**Arquivo**: `2026-05-13-ledger-wallet-extraction-execution-plan.md`

**Objetivo**: Extrair Ledger e Wallet para packages compartilhados (`packages/backend/ledger` e `packages/frontend/wallet`) para reutilização entre produtos.

**Por que foi arquivado**:
- Abstração preventiva
- Cria novos packages (violação de freeze)
- Ledger é genérico, mas ainda não há segundo consumidor justificado

**Quando retomar**:
- Quando HL Consulting ou outro produto precisar de ledger
- Com aprovação explícita de sair do freeze

---

### 3. Lista de Tarefas para Extração de Core

**Arquivo**: `2026-05-13-extract-hl-core-task-list.md`

**Objetivo**: Decompor extração de Core em tarefas executáveis.

**Status**: Pré-requisito do plano 1

**Quando retomar**: Junto com plano 1, após V1

---

## Próximos Passos Após V1

### 1. Revisar Pressupostos (1-2 semanas)
- [ ] Validar que HL Drive V1 funciona em produção
- [ ] Coletar feedback do primeiro cliente (instrutor)
- [ ] Identificar padrões de reutilização real (não hipotético)
- [ ] Avaliar se novos produtos justificam abstração

### 2. Sair do Architecture Freeze
- [ ] Documentar decisão
- [ ] Atualizar ARCHITECTURE-FREEZE.md
- [ ] Comunicar ao time

### 3. Reavaliação Arquitetural
- [ ] Revisar estes spikes com realidade comprovada
- [ ] Ajustar escopo de acordo com necessidades reais
- [ ] Verificar se código atual já é reutilizável sem extração

### 4. Execução Incremental
Se aprovado:
1. Criar task plan com milestones pequenas
2. Implementar uma spike de cada vez
3. Validar cada extração antes de próxima
4. Manter testes cobrindo reutilização

---

## Princípios que Continuam Válidos

- **Domínio antes de arquitetura**: Novos produtos primeiro, abstrações depois
- **Core nasce da reutilização comprovada**: Não para reutilização hipotética
- **Simplicidade primeiro**: Extrair apenas quando complexidade justificar
- **Ledger é fonte de verdade**: Nunca mudar esse princípio

---

## Referências

- Ver `docs/architecture/02-VISION.md` para contexto de múltiplos produtos
- Ver `docs/architecture/04-DECISION-FRAMEWORK.md` para critérios de decisão
- Ver `docs/execution-history/` para andamento das phases 2-4

---

**Status**: ARQUIVADOS - NÃO IMPLEMENTAR  
**Última atualização**: 2026-06-25  
**Revisão pendente**: Após V1 validado em produção
