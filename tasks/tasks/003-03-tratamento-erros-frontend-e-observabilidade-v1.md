# Tarefa: Tratamento Aprimorado de Erros no Frontend e Observabilidade

**Plano Relacionado:** [tasks/plans/doing/003-01-debitos-tecnicos-e-resiliencia-v1.md](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/tasks/plans/doing/003-01-debitos-tecnicos-e-resiliencia-v1.md)  
**Status:** 🟡 Planejado  
**Criado em:** 2026-09-25 21:10  
**Responsável:** Tiago França / Equipe Hour Ledger Ecosystem  

---

## 1. Contexto

Melhorar o tratamento de respostas de erro da API no cliente HTTP do frontend (`apps/hl-drive-web/src/services/api.ts`), exibindo mensagens contextuais ao usuário para status 401, 403 (falta de permissão ou tenant inativo), 422 (validação de formulário) e 429 (rate limit).

---

## 2. Escopo de Trabalho

- [ ] Ajustar interceptor Axios em `src/services/api.ts` para tratar respostas 429 com aviso de tempo restante (`Retry-After`).
- [ ] Mapear mensagens amigáveis em português para falhas de rede e timeouts.
- [ ] Atualizar composable `useToast` para mensagens de erro estruturadas.
- [ ] Validar com testes unitários no frontend.

---

## 3. Critérios de Aceitação

- Feedback visual claro para o usuário em qualquer falha de rede ou validação.
- Sem erros de TypeScript na compilação (`pnpm check` / `npm run build`).
