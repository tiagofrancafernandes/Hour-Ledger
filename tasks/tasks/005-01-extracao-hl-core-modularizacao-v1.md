# Tarefa: Mapeamento e Extração dos Packages Core

**Plano Relacionado:** [tasks/plans/005-01-extracao-hl-core-modularizacao-v1.md](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/tasks/plans/005-01-extracao-hl-core-modularizacao-v1.md)  
**Status:** 🟡 Planejado  
**Criado em:** 2026-09-25 21:10  
**Responsável:** Tiago França / Equipe Hour Ledger Ecosystem  

---

## 1. Contexto

Estruturar e migrar modelos, traits e serviços genéricos de `apps/hl-drive-api` para `packages/backend/core` e `packages/backend/ledger`, preparando o ecossistema para o modular monolith multi-produto.

---

## 2. Escopo de Trabalho

- [ ] Mapear classes centrais do HL Core (`User`, `Tenant`, `BelongsToTenant`, `TenantScope`, `TenantResolver`).
- [ ] Configurar autoload PSR-4 dos pacotes locais no `composer.json` principal.
- [ ] Mover classes e atualizar namespaces e imports.
- [ ] Validar suíte de testes completa sem quebra de dependências.
