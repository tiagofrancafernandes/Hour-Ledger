# Tarefa 004: Completar Configuração i18n do Frontend

**Plano**: Beta Launch  
**Tipo**: Feature Setup  
**Prioridade**: ALTA  
**Estimativa**: 1 hora  
**Status**: Não iniciada  
**Bloqueia**: Tarefa 005 (Login com i18n)  
**Bloqueada por**: Tarefa 003 (Scripts)

---

## Objetivo

Completar a configuração de vue-i18n no frontend, com suporte para pt-BR e en, e traduções essenciais para navegação e fluxos básicos.

## Contexto

O arquivo `src/plugins/i18n.ts` existe mas está incompleto. Arquivos de locale foram criados em `src/locales/` mas as traduções ainda não foram preenchidas. O plugin não está registrado em `main.ts`.

Esta tarefa completa:
1. Plugin i18n totalmente configurado
2. Traduções base para pt-BR e en
3. Plugin registrado e ativo
4. Componentes conseguem usar `$t()`

## Critérios de Aceite

- ✅ Arquivo `src/plugins/i18n.ts` está completo
- ✅ Arquivos `src/locales/pt-BR.json` e `src/locales/en.json` preenchidos com traduções essenciais
- ✅ Plugin registrado em `src/main.ts`
- ✅ `pnpm run dev` não gera erros de i18n
- ✅ Componentes conseguem usar `$t('chave')`
- ✅ Ambas as línguas funcionam (validar via console)
- ✅ Nenhum erro no console do navegador sobre i18n
- ✅ Relatório criado

## Escopo

### IN:
- Completar arquivo `src/plugins/i18n.ts`
- Preencher `src/locales/pt-BR.json` com mínimo de traduções
- Preencher `src/locales/en.json` com traduções equivalentes
- Registrar plugin em `src/main.ts`
- Testar em múltiplos componentes
- Validar que funciona

### OUT:
- Implementação de seletor de idioma (UI)
- Suporte para mais idiomas (apenas pt-BR + en por enquanto)
- Alterações de componentes existentes além de teste

## Traduções Essenciais (Mínimo)

Seguir este conjunto mínimo de chaves:

### Auth/Login
- `auth.email` → "Email"
- `auth.password` → "Senha"
- `auth.login` → "Entrar"
- `auth.register` → "Registrar"
- `auth.remember` → "Lembrar-me"
- `auth.forgot_password` → "Esqueci minha senha"

### Navigation
- `nav.dashboard` → "Dashboard"
- `nav.wallet` → "Carteira"
- `nav.clients` → "Clientes"
- `nav.reports` → "Relatórios"
- `nav.settings` → "Configurações"
- `nav.logout` → "Sair"

### Wallet/Ledger
- `wallet.balance` → "Saldo"
- `wallet.total` → "Total"
- `wallet.entries` → "Movimentações"
- `wallet.add_entry` → "Adicionar Movimentação"
- `wallet.hours` → "Horas"
- `wallet.date` → "Data"
- `wallet.description` → "Descrição"

### Common
- `common.save` → "Salvar"
- `common.cancel` → "Cancelar"
- `common.delete` → "Deletar"
- `common.edit` → "Editar"
- `common.loading` → "Carregando..."
- `common.error` → "Erro"
- `common.success` → "Sucesso"
- `common.close` → "Fechar"

## Tarefas Técnicas

### 1. Completar `src/plugins/i18n.ts`

Criar/Editar arquivo:

```typescript
import { createI18n } from 'vue-i18n'
import type { I18n, I18nOptions } from 'vue-i18n'

// Import locale files
import ptBR from '@/locales/pt-BR.json'
import en from '@/locales/en.json'

// Detect browser language
const getBrowserLanguage = (): string => {
  const browserLang = navigator.language.split('-')[0]
  return ['pt', 'en'].includes(browserLang) ? browserLang : 'pt'
}

// i18n configuration
const options: I18nOptions = {
  legacy: false,
  locale: getBrowserLanguage(),
  fallbackLocale: 'pt',
  messages: {
    pt: ptBR,
    en: en
  },
  globalInjection: true,
  missingWarn: false,
  fallbackWarn: false
}

const i18n: I18n = createI18n(options)

export default i18n
```

**Pontos importantes**:
- `legacy: false` = Usar Composition API mode
- `fallbackLocale: 'pt'` = Se key não encontrar, usar português
- `globalInjection: true` = Torna `$t()` disponível em todos os componentes

### 2. Criar `src/locales/pt-BR.json`

```json
{
  "auth": {
    "email": "Email",
    "password": "Senha",
    "login": "Entrar",
    "register": "Registrar",
    "remember": "Lembrar-me",
    "forgot_password": "Esqueci minha senha"
  },
  "nav": {
    "dashboard": "Dashboard",
    "wallet": "Carteira",
    "clients": "Clientes",
    "reports": "Relatórios",
    "settings": "Configurações",
    "logout": "Sair"
  },
  "wallet": {
    "balance": "Saldo",
    "total": "Total",
    "entries": "Movimentações",
    "add_entry": "Adicionar Movimentação",
    "hours": "Horas",
    "date": "Data",
    "description": "Descrição"
  },
  "common": {
    "save": "Salvar",
    "cancel": "Cancelar",
    "delete": "Deletar",
    "edit": "Editar",
    "loading": "Carregando...",
    "error": "Erro",
    "success": "Sucesso",
    "close": "Fechar"
  }
}
```

### 3. Criar `src/locales/en.json`

```json
{
  "auth": {
    "email": "Email",
    "password": "Password",
    "login": "Login",
    "register": "Register",
    "remember": "Remember me",
    "forgot_password": "Forgot password?"
  },
  "nav": {
    "dashboard": "Dashboard",
    "wallet": "Wallet",
    "clients": "Clients",
    "reports": "Reports",
    "settings": "Settings",
    "logout": "Logout"
  },
  "wallet": {
    "balance": "Balance",
    "total": "Total",
    "entries": "Transactions",
    "add_entry": "Add Transaction",
    "hours": "Hours",
    "date": "Date",
    "description": "Description"
  },
  "common": {
    "save": "Save",
    "cancel": "Cancel",
    "delete": "Delete",
    "edit": "Edit",
    "loading": "Loading...",
    "error": "Error",
    "success": "Success",
    "close": "Close"
  }
}
```

### 4. Registrar Plugin em `src/main.ts`

Editar `apps/hl-drive-web/src/main.ts`:

```typescript
import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import i18n from '@/plugins/i18n'

const app = createApp(App)

// Registrar i18n ANTES de mount
app.use(i18n)
app.use(router)

app.mount('#app')
```

**Ordem importante**:
1. `use(i18n)` primeiro
2. `use(router)` depois
3. `mount()` por último

### 5. Testar em Componente

Editar `src/views/LoginView.vue` (ou componente similar):

```vue
<template>
  <div>
    <h1>{{ $t('auth.login') }}</h1>
    <input :placeholder="$t('auth.email')" />
    <input :placeholder="$t('auth.password')" type="password" />
    <button>{{ $t('auth.login') }}</button>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

// Para acessar i18n via Composition API:
const { locale, t } = useI18n()

// Usar assim também funciona:
// {{ t('auth.login') }}
// Mas $t é mais conciso
</script>
```

### 6. Testar no Navegador

Rodar: `pnpm run dev`

Abrir em: `http://localhost:5173/`

No console do navegador (F12):

```javascript
// Verificar se i18n está disponível
console.log(this.$i18n)  // Em template
console.log($t('auth.login'))  // Direto

// Mudar idioma
$i18n.locale = 'en'
$i18n.locale = 'pt'
```

Verificar:
- [ ] Página carrega sem erros
- [ ] Textos aparecem traduzidos
- [ ] Console não tem erro de "Cannot read property 'auth'"
- [ ] Mudança de idioma funciona

### 7. Validar Sem Erros

```bash
# Typecheck
pnpm run typecheck

# Lint
pnpm run lint

# Build
pnpm run build

# Todos devem passar sem erro crítico
```

### 8. Criar Relatório

Registrar em: `docs/agent/reports/2026-06-24-i18n-setup.md`

Incluir:
- Data/hora de conclusão
- Arquivos criados/modificados
- Idiomas suportados (pt, en)
- Teste realizado
- Erros encontrados (se houver)
- Chaves de tradução criadas (mínimo XXX chaves)
- Próximos passos (Tarefa 005)

## Dependências

- ✅ Tarefa 003 (Scripts funcionando)

## Notas Importantes

1. **Estrutura de chaves**: Usar notação com ponto em templates:
   ```vue
   {{ $t('auth.login') }} ✓ Correto
   {{ $t('login') }} ✗ Errado (chave não vai existir)
   ```

2. **Fallback**: Se colocar `fallbackLocale: 'pt'`, qualquer chave faltando em `en.json` voltará para `pt`.

3. **Lazy loading de locales** (opcional para depois):
   ```typescript
   // Carrega locales apenas quando necessário
   // Por enquanto, fazer import estático é ok
   ```

4. **Não confundir** com i18n v10+ que é diferente. Este projeto usa v9 conforme `package.json`.

5. **Persistência de idioma** (para depois):
   - Salvar escolha em localStorage
   - Restaurar ao abrir página
   - Por enquanto, apenas browser language detection

## Arquivos Criados/Modificados

- ✏️ `src/plugins/i18n.ts` (criar/completar)
- ✏️ `src/locales/pt-BR.json` (preencher)
- ✏️ `src/locales/en.json` (preencher)
- ✏️ `src/main.ts` (registrar plugin)
- ℹ️ `docs/agent/reports/2026-06-24-i18n-setup.md` (relatório)

## Próxima Tarefa

Após completar com sucesso: **Tarefa 005: Validar Fluxo de Login com i18n**

