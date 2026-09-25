# Plano: Débitos Técnicos, Resiliência e Produção

**Prioridade:** Alta  
**Data de Início Planejada:** 2026-10-01  
**Data de Conclusão Planejada:** 2026-10-15  
**Origem:** `TECHNICAL-DEBT-ROADMAP.md`  
**Responsável:** Tiago França / Equipe Hour Ledger Ecosystem  

---

## 1. Objetivo

Implementar mitigações para os débitos técnicos críticos e de alta severidade identificados no amadurecimento do HL Drive, visando máxima segurança, observabilidade e performance antes do lançamento em produção em escala.

---

## 2. Escopo

### 2.1. Rate Limiting em Endpoints Públicos
- [ ] Implementar middleware de rate limit em rotas sensíveis:
  - `/api/auth/login` (ex: 5 req/min)
  - `/api/auth/register` (ex: 3 req/min)
  - `/api/auth/password-recovery/*` (ex: 3 req/min)
- [ ] Retornar status HTTP 429 Too Many Requests ao exceder limite.
- [ ] Criar testes automatizados cobrindo tentativas excessivas e desbloqueio temporal.

### 2.2. Camada de Cache com Redis
- [ ] Integrar Redis para cálculo de saldo derivado (`BalanceCalculatorService`).
- [ ] Invalidar cache de saldo em eventos de mutação (`LedgerEntry::created`, `deleted`).
- [ ] Manter fallback seguro para cálculo direto no banco caso o Redis esteja indisponível.

### 2.3. Observabilidade e Tratamento Centralizado de Erros
- [ ] Integrar driver de monitoramento de exceções (Sentry ou driver nativo).
- [ ] Aprimorar interceptor HTTP no frontend (`src/services/api.ts`) para tratamento diferenciado:
  - 401: redirecionamento e renovação de token.
  - 403: aviso explícito de falta de permissão ou tenant inativo.
  - 422: mapeamento de erros de formulário por campo.
  - 429: aviso de bloqueio temporário por rate limit com tempo restante.
  - Falha de conexão: retry inteligente para operações idempotentes.

### 2.4. Documentação de Operações e Playbooks
- [ ] Expandir `docs/operations/DEPLOYMENT.md` com checklist de pré-deploy e variáveis de ambiente.
- [ ] Criar playbook de recuperação de desastres e incidentes (`docs/operations/INCIDENT-RESPONSE.md`).

---

## 3. Critérios de Sucesso

- ✅ Proteção contra ataques de força bruta em autenticação e recuperação de senha.
- ✅ Redução da latência na consulta de saldo de carteiras em >80% com cache hit.
- ✅ 100% de testes automatizados passando.
