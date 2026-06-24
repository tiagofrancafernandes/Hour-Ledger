# Tarefa 006: Validar Fluxo Principal (Wallet)

**Plano**: Beta Launch  
**Tipo**: Validação  
**Prioridade**: ALTA  
**Estimativa**: 45 minutos  
**Status**: Não iniciada  
**Bloqueia**: Tarefa 007 (Checklist beta)  
**Bloqueada por**: Tarefa 005 (Login validado)

---

## Objetivo

Confirmar que o fluxo principal da aplicação (visualização e manipulação de wallet) funciona end-to-end sem erros.

## Contexto

Wallet é o core da aplicação Hour Ledger. Esta tarefa valida que:
1. Após login, consegue acessar a wallet
2. Saldo carrega do backend
3. Movimentações são listadas corretamente
4. Criar/editar/deletar movimentações funciona (se disponível)
5. Dados persistem no banco

## Critérios de Aceite

- ✅ Página de wallet carrega após login
- ✅ Saldo é exibido (número correto)
- ✅ Movimentações são listadas
- ✅ GET `/api/wallets/{id}/balance` retorna dados corretos
- ✅ GET `/api/wallets/{id}/entries` retorna movimentações
- ✅ Operações de leitura funcionam (GET)
- ✅ Operações de escrita funcionam (POST, PUT, DELETE) se implementadas
- ✅ Nenhum erro 401/403/500
- ✅ Dados estão corretos (não há valores invertidos/incorretos)
- ✅ Relatório criado

## Escopo

### IN:
- Validar carregamento de página wallet
- Testar requisições GET para saldo e movimentações
- Testar operações CRUD (se disponível)
- Validar persistência de dados
- Verificar cálculos de saldo

### OUT:
- Implementação de novas features
- Design/layout changes
- Refatoração de código

## Pré-requisitos

- ✅ Backend (Tarefa 001) rodando
- ✅ Frontend (Tarefa 002) rodando
- ✅ Login (Tarefa 005) validado
- ✅ Usuário de teste criado e logado

## Tarefas Técnicas

### 1. Preparar Dados de Teste

Criar dados de teste no banco do backend:

```bash
# No terminal com backend rodando
php artisan tinker

# Criar carteira e entradas de ledger
use App\Models\Wallet;
use App\Models\LedgerEntry;
use App\Models\User;

$user = User::where('email', 'test@example.com')->first();

// Criar wallet
$wallet = Wallet::create([
  'user_id' => $user->id,
  'name' => 'Carteira Principal',
  'currency' => 'BRL'
]);

// Criar movimentações (ledger entries)
LedgerEntry::create([
  'wallet_id' => $wallet->id,
  'type' => 'credit',
  'amount' => 10.00,
  'description' => 'Crédito inicial',
  'created_at' => now()
]);

LedgerEntry::create([
  'wallet_id' => $wallet->id,
  'type' => 'debit',
  'amount' => 2.50,
  'description' => 'Consumo de aula',
  'created_at' => now()
]);

exit();
```

Resultado esperado: Wallet com saldo de 7.50 (10 - 2.50)

### 2. Verificar Endpoints de Wallet

Validar que backend tem esses endpoints:

```bash
# GET lista de wallets do usuário
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/wallets

# GET saldo de wallet específica
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/wallets/1/balance

# GET movimentações de wallet
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/wallets/1/entries
```

Se algum endpoint não existir:
- Verificar em `app/Http/Controllers/Api/WalletController.php`
- Ou criar se faltando

### 3. Fazer Login e Permanecer Autenticado

```
1. Ir para http://localhost:5173
2. Fazer login com test@example.com / password123
3. Após redirecionamento, estará autenticado
4. Token está em localStorage (verificar via DevTools)
```

### 4. Navegar para Wallet

Clicar no link/menu de Wallet (deve redirecionar para `/wallet` ou `/dashboard`):

Esperado:
- [ ] Página carrega
- [ ] Nenhum erro 404
- [ ] Layout é renderizado

### 5. Verificar Carregamento de Dados

Abrir DevTools → Network:

```
Observar requisições:
- GET /api/wallets → deve retornar 200
- GET /api/wallets/1/balance → deve retornar 200
- GET /api/wallets/1/entries → deve retornar 200
```

Response esperado para `/wallets/1/balance`:

```json
{
  "wallet_id": 1,
  "balance": 7.50,
  "currency": "BRL"
}
```

Response esperado para `/wallets/1/entries`:

```json
[
  {
    "id": 1,
    "wallet_id": 1,
    "type": "credit",
    "amount": 10.00,
    "description": "Crédito inicial",
    "created_at": "2026-06-24T10:00:00Z"
  },
  {
    "id": 2,
    "wallet_id": 1,
    "type": "debit",
    "amount": 2.50,
    "description": "Consumo de aula",
    "created_at": "2026-06-24T10:01:00Z"
  }
]
```

### 6. Verificar Exibição Visual

Na página de Wallet, verificar:

- [ ] Saldo é exibido corretamente (7.50 BRL)
- [ ] Movimentações aparecem em lista/tabela
- [ ] Datas estão formatadas corretamente
- [ ] Descrições aparecem
- [ ] Valores estão formatados (2 casas decimais, etc)
- [ ] Nenhum erro de console sobre undefined values

### 7. Testar Operação de Criar Movimentação (Se Disponível)

Se houver botão "Adicionar Movimentação" ou similar:

```
1. Clicar botão
2. Preencher formulário:
   - Tipo: Crédito
   - Valor: 5.00
   - Descrição: "Teste de adição"
3. Clicar "Salvar"
4. Verificar no Network se POST foi feito
5. Verificar se movimentação aparece na lista
6. Verificar se saldo foi atualizado (7.50 + 5.00 = 12.50)
```

### 8. Testar Operação de Editar (Se Disponível)

Se houver ícone/botão de editar em movimentação:

```
1. Clicar editar em uma movimentação
2. Modificar descrição ou valor
3. Clicar salvar
4. Verificar se PUT foi feito
5. Verificar se dados foram atualizados na lista
```

### 9. Testar Operação de Deletar (Se Disponível)

Se houver ícone/botão de deletar:

```
1. Clicar deletar em uma movimentação
2. Se houver confirmação, confirmar
3. Verificar se DELETE foi feito
4. Verificar se movimentação desaparece da lista
5. Verificar se saldo foi recalculado
```

### 10. Verificar Persistência

Fechar aba/browser e reabrir:

```
1. Ir para http://localhost:5173
2. Fazer login novamente
3. Ir para wallet
4. Verificar se dados são os mesmos
5. Saldo deve ser o mesmo
6. Movimentações devem ser as mesmas
```

Esperado:
- [ ] Nenhuma perda de dados
- [ ] Saldo calculado corretamente (não é hardcoded)
- [ ] Ledger é imutável (não pode editar/deletar - apenas criar)

### 11. Verificar Cálculo de Saldo

Se tiver múltiplas entradas/saídas:

```
Manual calculation:
Inicial: 0
+ 10.00 (crédito) = 10.00
- 2.50 (débito) = 7.50
+ 5.00 (crédito) = 12.50
- 1.00 (débito) = 11.50

Verificar se campo 'Balance' mostra exatamente: 11.50
```

Se não corresponder:
- Pode ser arredondamento
- Pode ser inclusão/exclusão de transação
- Registrar discrepância

### 12. Verificar Erros de Console

Abrir DevTools → Console:

Esperado:
- [ ] Nenhuma exceção vermelha
- [ ] Nenhuma mensagem de erro sobre API
- [ ] Nenhuma warning sobre componentes Vue

Se houver erros, registrar:
- Mensagem exata do erro
- Stack trace
- Quando ocorre

### 13. Testar Cenários Edge

```
1. Navegar para wallet de outro usuário (se possível)
   → Deve dar erro 403 Forbidden
2. Fazer logout e tentar acessar wallet
   → Deve redirecionar para login
3. Modificar token no localStorage para valor inválido
   → Deve dar erro 401 Unauthorized
```

### 14. Criar Relatório

Registrar em: `docs/agent/reports/2026-06-24-wallet-validation.md`

Incluir:
- Data/hora do teste
- Dados de teste criados
- Requisições feitas (endpoints testados)
- Resposta do backend (status codes, dados)
- Exibição no frontend (correto/incorreto)
- Operações testadas (read, create, update, delete)
- Persistência validada? (sim/não)
- Cálculo de saldo correto? (sim/não)
- Erros encontrados (se houver)
- Conclusão: Pronto para beta? (sim/não)

## Problemas Comuns e Soluções

| Problema | Causa | Solução |
|----------|---|---|
| 404 Wallet page | Rota não existe | Criar src/views/WalletView.vue |
| 401 em requisição wallet | Token inválido/expirado | Fazer login novamente |
| Saldo vem vazio | Backend não calcula | Verificar BalanceCalculatorService |
| Movimentações vazias | Nenhum registro no ledger | Criar dados de teste |
| Valor invertido (negativo/positivo) | Lógica de débito/crédito | Verificar LedgerEntry.amount |
| Saldo não atualiza | Revalidação não funciona | Adicionar refetch após PUT/POST |

## Dependências

- ✅ Tarefa 005 (Login validado)
- ✅ Backend endpoint para Wallet

## Notas Importantes

1. **Ledger é append-only**: Wallet não deve permitir editar movimentação antiga. Apenas criar nova para ajuste.

2. **Saldo derivado**: Balance nunca deve ser coluna no DB. Sempre calculado via SUM(ledger_entries).

3. **Precisão decimal**: Usar decimal(10,2) ou similar. Não float.

4. **Transações de banco**: Operações críticas devem usar transaction().

5. **Código Style**: Nenhuma alteração esperada nesta tarefa.

## Próxima Tarefa

Após completar com sucesso: **Tarefa 007: Criar Checklist de Validação Beta**

