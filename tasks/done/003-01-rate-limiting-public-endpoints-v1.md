# Tarefa: Rate Limiting em Endpoints Públicos de Autenticação

**Plano Relacionado:** [tasks/plans/doing/003-01-debitos-tecnicos-e-resiliencia-v1.md](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/tasks/plans/doing/003-01-debitos-tecnicos-e-resiliencia-v1.md)  
**Status:** ✅ Concluído  
**Concluído em:** 2026-09-25 21:13  
**Responsável:** Tiago França / Equipe Hour Ledger Ecosystem  

---

## 1. Contexto

Endpoints públicos como login, cadastro e recuperação de senha anteriormente não possuíam limite restritivo de requisições por IP, permitindo potenciais ataques de força bruta contra credenciais e negação de serviço (DoS). Esta tarefa implementou rate limiters específicos do Laravel com retorno 429 Too Many Requests e cabeçalho `Retry-After`.

---

## 2. Escopo de Trabalho

- [x] Definir limiters no `AppServiceProvider` / `boot()`:
  - `login`: 5 requisições por minuto por IP (`Limit::perMinute(5)->by($key)`).
  - `register`: 3 requisições por minuto por IP.
  - `password-recovery`: 3 requisições por minuto por IP.
- [x] Aplicar middlewares `throttle:login`, `throttle:register` e `throttle:password-recovery` nas rotas correspondentes em `routes/api.php`.
- [x] Garantir que o formato da resposta JSON 429 seja claro e padronizado com `Retry-After`.
- [x] Criar suíte de testes automatizados `tests/Feature/RateLimitingTest.php` cobrindo:
  - 5 tentativas de login permitidas, 6ª tentativa bloqueada com 429.
  - Tentativas de registro bloqueadas na 4ª tentativa com 429.
  - Tentativas de recuperação de senha bloqueadas na 4ª tentativa com 429.
- [x] Validar que requisições normais continuam funcionando normalmente.

---

## 3. Critérios de Aceitação

- ✅ Código segue rigorosamente `UNIVERSAL-CODE-STYLE-RULES.md`.
- ✅ Testes automatizados em `tests/Feature/RateLimitingTest.php` passam 100% (3/3 testes, 17 asserções).
- ✅ Sem regressão nos demais testes da suíte (113/113 testes passando).

---

## 4. Resultado Final

Implementação do rate limiting de endpoints de autenticação finalizada com sucesso, protegendo `/api/auth/login`, `/api/auth/register` e `/api/auth/password-recovery/*` contra ataques de força bruta. Suíte de testes `RateLimitingTest` criada e aprovada.
