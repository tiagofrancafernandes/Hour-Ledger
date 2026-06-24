# Tarefa 005: Validar Fluxo de Login com i18n

**Plano**: Beta Launch  
**Tipo**: Validação  
**Prioridade**: ALTA  
**Estimativa**: 30 minutos  
**Status**: Não iniciada  
**Bloqueia**: Tarefa 006 (Wallet validation)  
**Bloqueada por**: Tarefa 004 (i18n configurado)

---

## Objetivo

Confirmar que o fluxo de login funciona end-to-end com i18n ativo, comunicação com backend, e armazenamento de token.

## Contexto

Com i18n configurado (Tarefa 004), agora validamos que:
1. Login page carrega com textos traduzidos
2. Usuário consegue fazer login
3. Token/session é armazenado corretamente
4. Redirecionamento pós-login funciona
5. Nenhum erro de comunicação com backend

## Critérios de Aceite

- ✅ Login page carrega completamente
- ✅ Todos os textos estão em português (ou idioma configurado)
- ✅ Campos de email e password são interativos
- ✅ Botão de login funciona
- ✅ Requisição POST `/api/login` é feita
- ✅ Token é recebido e armazenado (localStorage/sessionStorage)
- ✅ Redirecionamento para dashboard/home acontece
- ✅ Nenhum erro CORS
- ✅ Nenhum erro de autenticação "Bearer token"
- ✅ Relatório criado

## Escopo

### IN:
- Validar página de login
- Testar entrada de credenciais
- Testar envio de formulário
- Verificar armazenamento de token
- Verificar redirecionamento
- Testar tradução dos textos

### OUT:
- Implementação de recuperação de senha
- Implementação de 2FA
- Criação de nova página de login
- Alterações de design

## Pré-requisitos

- ✅ Backend (Tarefa 001) está rodando em `http://localhost:8000`
- ✅ Frontend (Tarefa 002) está rodando em `http://localhost:5173`
- ✅ i18n (Tarefa 004) está configurado
- ✅ Scripts (Tarefa 003) funcionam

## Tarefas Técnicas

### 1. Preparar Dados de Teste

Backend precisa ter usuário de teste. Se não tiver:

```bash
# No terminal do backend (Tarefa 001 rodando)
php artisan tinker

# Dentro do tinker:
use App\Models\User;

User::create([
  'name' => 'Test User',
  'email' => 'test@example.com',
  'password' => bcrypt('password123'),
  'email_verified_at' => now()
]);

exit();
```

**Credenciais de teste**:
- Email: `test@example.com`
- Senha: `password123`

Guardar essas credenciais para uso nesta tarefa.

### 2. Verificar Endpoint de Login

Backend deve ter endpoint `POST /api/login` ou `POST /api/auth/login`.

Testar com curl:

```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'

# Esperado: resposta JSON com token
# {"token":"...","user":{...}}
```

Se não funcionar, verificar em:
- `app/Http/Controllers/Api/AuthController.php` (ou similar)
- `routes/api.php` para definição de rota

### 3. Verificar Frontend `services/api.ts`

Verificar que existe método de login:

```typescript
// Em src/services/api.ts ou similar
const login = async (email: string, password: string) => {
  const response = await fetch(`${VITE_API_BASE_URL}/api/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  })
  
  if (!response.ok) throw new Error('Login failed')
  
  const data = await response.json()
  
  // Armazenar token
  localStorage.setItem('token', data.token)
  // OU
  sessionStorage.setItem('token', data.token)
  
  return data
}
```

Se não existir, criar antes de prosseguir.

### 4. Abrir Browser e Navegar para Login

```
http://localhost:5173/
```

Esperado:
- [ ] Página carrega sem erros 404
- [ ] Textos estão em português (ou idioma configurado)
- [ ] Campo email com placeholder `{{ $t('auth.email') }}`
- [ ] Campo password com placeholder `{{ $t('auth.password') }}`
- [ ] Botão com texto `{{ $t('auth.login') }}`
- [ ] Nenhum erro no console (F12)

### 5. Testar Entrada de Dados

```
1. Clicar no campo de email
2. Digitar: test@example.com
3. Clicar no campo de password
4. Digitar: password123
5. Verificar campos preenchidos corretamente
```

Esperado:
- [ ] Campos aceitam texto
- [ ] Nenhum erro de validação prematuro

### 6. Testar Envio de Formulário

```
1. Clicar botão "Entrar"
2. Observar rede (F12 → Network)
3. Verificar requisição POST /api/login
```

Esperado no Network tab:
- [ ] Requisição POST para `http://localhost:8000/api/login`
- [ ] Status 200 (sucesso)
- [ ] Response contém `{"token":"...","user":{...}}`
- [ ] Nenhum erro 401/403/500

### 7. Verificar Armazenamento de Token

No console (F12):

```javascript
// Verificar se token foi salvo
console.log(localStorage.getItem('token'))
// Deve exibir: "eyJ0eXAi..." (JWT token)

// OU
console.log(sessionStorage.getItem('token'))
```

Esperado:
- [ ] Token existe em localStorage ou sessionStorage
- [ ] Token é um JWT válido (começa com "eyJ...")
- [ ] Não é null ou undefined

### 8. Verificar Redirecionamento

Após login bem-sucedido:
- [ ] Página redireciona para `/dashboard` ou `/home` ou similar
- [ ] URL muda de `/login` para a nova página
- [ ] Nenhuma mensagem de erro

Se NÃO redirecionar:
- Verificar `src/router/index.ts`
- Deve ter hook `afterEach()` ou similar que verifica token
- Se tem token, ir para dashboard; se não, ir para login

### 9. Testar Permanência de Sessão

```
1. Abrir DevTools (F12)
2. Application → Storage → Local/Session Storage
3. Verificar que 'token' existe
4. Fechar aba/browser
5. Reabrir aplicação
6. Token ainda está lá?
```

Esperado:
- [ ] Token persiste em localStorage (não em sessionStorage)
- [ ] Página automaticamente reconhece como autenticado
- [ ] Não pede novo login

### 10. Testar Tradução

No console do navegador:

```javascript
// Verificar se i18n está funcionando
console.log($i18n.locale)  // Deve ser 'pt'

// Mudar para inglês manualmente
$i18n.locale = 'en'

// Textos devem mudar na página
// Email → Email (igual porque é mesma palavra)
// Senha → Password
```

Esperado:
- [ ] Mudança de `$i18n.locale` afeta textos imediatamente
- [ ] Não há erro de "Cannot read property 'auth'"

### 11. Testar Logout (Se Disponível)

Se existir botão de logout:

```
1. Clicar logout
2. Verificar que token é removido
3. Verificar que volta para login
```

### 12. Criar Relatório

Registrar em: `docs/agent/reports/2026-06-24-login-validation.md`

Incluir:
- Data/hora do teste
- Email de teste utilizado
- Login bem-sucedido? (sim/não)
- Token armazenado? (sim/não)
- Redirecionamento funcionou? (sim/não)
- Textos traduzidos? (sim/não)
- Erros encontrados (se houver)
- Próximos passos

## Problemas Comuns e Soluções

| Problema | Causa Provável | Solução |
|----------|---|---|
| 404 na página | Frontend rota não existe | Criar view LoginView.vue com rota /login |
| CORS error | Backend não permite origem | Verificar config CORS em Laravel |
| 422 Unprocessable | Validação de backend falhou | Verificar se email/senha atendem regras |
| 401 Unauthorized | Credenciais erradas | Confirmar credenciais de teste |
| Token não salva | código não chama localStorage | Verificar src/services/api.ts |
| Não redireciona | Router não tem lógica de redirect | Criar/verificar router guards |

## Dependências

- ✅ Tarefa 004 (i18n configurado)
- ✅ Tarefa 001 (Backend rodando)
- ✅ Tarefa 002 (Frontend rodando)

## Notas Importantes

1. **Não usar password real**: Sempre usar credenciais de teste

2. **Token JWT**: Seu token terá formato `header.payload.signature`. Pode decodificar em [jwt.io](https://jwt.io) para verificar conteúdo

3. **CORS**: Se der erro de CORS:
   ```php
   // Em config/cors.php no Laravel
   'allowed_origins' => ['http://localhost:5173', '*'],
   ```

4. **HttpOnly cookies**: Se backend usar HttpOnly cookies em vez de localStorage:
   - Não vai aparecer em localStorage
   - Requisições subsequentes enviam automaticamente
   - é mais seguro que JWT em localStorage

5. **Código Style**: Nenhuma alteração de código esperada nesta tarefa além de criar/verificar que existe.

## Próxima Tarefa

Após completar com sucesso: **Tarefa 006: Validar Fluxo Principal (Wallet)**

