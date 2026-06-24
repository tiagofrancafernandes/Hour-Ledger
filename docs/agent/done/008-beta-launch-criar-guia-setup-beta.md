# Tarefa 008: Criar Guia de Setup para Clientes Beta

**Plano**: Beta Launch  
**Tipo**: Documentação  
**Prioridade**: ALTA  
**Estimativa**: 30 minutos  
**Status**: Não iniciada  
**Bloqueia**: Nada (finaliza ciclo beta)  
**Bloqueada por**: Tarefa 007 (Checklist validado)

---

## Objetivo

Documentar de forma clara e prática como clientes beta devem configurar e executar a aplicação em seus ambientes.

## Contexto

Clientes beta não são necessariamente técnicos. Precisam de:
1. Instruções claras e passo-a-passo
2. Resolução de problemas comuns
3. Como reportar bugs
4. Dados de teste para começar
5. Contato para suporte

## Critérios de Aceite

- ✅ Documento criado em `docs/operations/BETA-SETUP-GUIDE.md`
- ✅ Pré-requisitos claros
- ✅ Passos de instalação (Backend + Frontend)
- ✅ Como rodar o app
- ✅ Dados de teste inclusos
- ✅ Troubleshooting para problemas comuns
- ✅ Como reportar bugs
- ✅ Contato de suporte
- ✅ Documento testável por usuário não-técnico

## Escopo

### IN:
- Consolidar informações das tarefas 001-007
- Escrever com linguagem clara e simples
- Incluir screenshots/comandos completos
- Incluir troubleshooting
- Fornecer dados de teste

### OUT:
- Nenhuma modificação de código
- Nenhuma criação de features
- Nenhuma alteração de arquitetura

## Estrutura Esperada do Documento

```
BETA SETUP GUIDE
├── Introdução
├── Pré-requisitos
├── Instalação
│   ├── Backend
│   ├── Frontend
│   └── Database
├── Como Executar
├── Dados de Teste
├── Funcionalidades Principais
├── Troubleshooting
├── Como Reportar Bugs
├── Contato e Suporte
└── FAQ
```

## Tarefas Técnicas

### 1. Criar Estrutura Base

Criar arquivo: `docs/operations/BETA-SETUP-GUIDE.md`

Se não existir pasta `docs/operations/`:
```bash
mkdir -p docs/operations
```

### 2. Seção: Introdução

```markdown
# Hour Ledger Beta Setup Guide

Welcome to the Hour Ledger beta! This guide will help you set up and run the application on your machine.

**What is Hour Ledger?**
Hour Ledger is a wallet and ledger management system designed for instructors and consultants to track hours and transactions in a secure, auditable way.

**Beta Program**
This is the beta version. We appreciate your feedback and patience with any issues you find.

**Estimated Setup Time**: 15-20 minutes

**Prerequisites Check**: ✓ PHP 8.2+ ✓ Node 18+ ✓ PostgreSQL 15+
```

### 3. Seção: Pré-requisitos

```markdown
## Prerequisites

Before starting, ensure you have these installed on your computer:

### Windows
1. **PHP 8.2+**: Download from https://windows.php.net/download/
2. **Node.js 18+**: Download from https://nodejs.org/
3. **PostgreSQL 15+**: Download from https://www.postgresql.org/download/

### macOS
```bash
# Using Homebrew
brew install php@8.2 node postgresql@15
```

### Linux (Ubuntu/Debian)
```bash
sudo apt-get update
sudo apt-get install php8.2 nodejs postgresql-15
```

### Check Versions
After installation, verify in a terminal/command prompt:
```bash
php -v          # Should show 8.2+
node -v         # Should show 18+
npm -v          # Should show 8+
psql -V         # Should show 15+
```

**Having trouble?** See [Troubleshooting](#troubleshooting)
```

### 4. Seção: Instalação Backend

```markdown
## Installation

### Step 1: Download the Code

1. Visit https://github.com/YourOrg/hour-ledger
2. Click "Code" → "Download ZIP"
3. Extract to a folder (e.g., `C:\hour-ledger` or `~/hour-ledger`)

OR if you have Git:
```bash
git clone https://github.com/YourOrg/hour-ledger.git
cd hour-ledger
```

### Step 2: Setup Backend

Open a terminal and navigate to the backend folder:
```bash
cd apps/hl-drive-api
```

#### 2a. Install PHP Dependencies
```bash
composer install
```
This downloads and prepares all PHP libraries. Takes 1-2 minutes.

#### 2b. Configure Environment
Copy the example file:
```bash
cp .env.example .env
```

Edit `.env` with your database credentials:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=hl_drive_beta
DB_USERNAME=postgres
DB_PASSWORD=YOUR_POSTGRES_PASSWORD
```

#### 2c. Generate App Key
```bash
php artisan key:generate
```

#### 2d. Create Database
Open another terminal and create the database:
```bash
psql -U postgres -c "CREATE DATABASE hl_drive_beta;"
```

Back in the backend terminal, run migrations:
```bash
php artisan migrate
```

Expected output: "Database prepared successfully."

#### 2e. Create Test User
```bash
php artisan tinker

use App\Models\User;
User::create([
  'name' => 'Beta Tester',
  'email' => 'beta@example.com',
  'password' => bcrypt('password123'),
  'email_verified_at' => now()
]);

exit();
```

### Step 3: Setup Frontend

In a NEW terminal, navigate to the frontend:
```bash
cd apps/hl-drive-web
```

#### 3a. Install Node Dependencies
```bash
pnpm install
# or: npm install
```

#### 3b. Configure Environment
Copy example:
```bash
cp .env.example .env
```

Edit `.env`:
```env
VITE_API_BASE_URL=http://localhost:8000
```

### Step 4: You're Ready!

Go to the next section: "How to Run"
```

### 5. Seção: Como Executar

```markdown
## How to Run

### From the Root Folder

Open a terminal in the project root folder and run:
```bash
pnpm run dev:drive
```

This starts both backend and frontend automatically.

**Expected Output:**
```
[backend] INFO  Server running on [http://127.0.0.1:8000]
[frontend] VITE v7.2.4  ready in XXX ms
[frontend] ➜  Local:   http://localhost:5173/
```

### Access the Application

Open your browser and go to:
```
http://localhost:5173/
```

You should see the login page.

### Stop the Application

Press `Ctrl + C` in the terminal to stop both backend and frontend.

### Manual Setup (If Above Doesn't Work)

**Terminal 1 - Backend:**
```bash
cd apps/hl-drive-api
php artisan serve
```

**Terminal 2 - Frontend:**
```bash
cd apps/hl-drive-web
pnpm run dev
```

Then open http://localhost:5173/ in your browser.
```

### 6. Seção: Dados de Teste

```markdown
## Test Data

Use these credentials to login:

```
Email: beta@example.com
Password: password123
```

You'll see:
- Dashboard with wallet
- Current balance
- Transaction history

**What You Can Do:**
- ✓ View your wallet balance
- ✓ See transaction history
- ✓ View reports
- ✓ Change language (Portuguese/English) - if implemented

**What's Not Yet Available:**
- ✗ Add new transactions (admin feature, coming soon)
- ✗ Invite other users (coming soon)
- ✗ Advanced reporting (coming soon)
```

### 7. Seção: Funcionalidades Principais

```markdown
## Main Features in This Beta

### 1. Login
- Email and password authentication
- Session persistence
- "Remember me" option

### 2. Dashboard
- Quick overview of your account
- Recent activity

### 3. Wallet
- View your account balance
- See all transactions
- Filter by date/type

### 4. Internationalization
- Switch between Portuguese and English
- Automatic browser language detection

### 5. Responsive Design
- Works on desktop
- Works on tablet (in progress)
- Works on mobile (in progress)
```

### 8. Seção: Troubleshooting

```markdown
## Troubleshooting

### "Port 8000 is already in use"
```bash
# Find what's using port 8000
# macOS/Linux:
lsof -i :8000

# Windows:
netstat -ano | findstr :8000

# Kill the process or change the port in apps/hl-drive-api/.env
APP_PORT=8001
```

### "Could not find Driver 'pgsql'"
PostgreSQL extension not installed for PHP.

**Windows/macOS**: Install PHP with PostgreSQL support
**Linux**: 
```bash
sudo apt-get install php8.2-pgsql
```

### "Cannot connect to database"
Check your `.env` file:
- Is PostgreSQL running? 
- Are DB_HOST/DB_PORT correct?
- Is DB_USERNAME/PASSWORD correct?

Test with:
```bash
psql -U postgres -h 127.0.0.1 -d hl_drive_beta
```

### "npm ERR!"
Try clearing cache:
```bash
npm cache clean --force
rm -rf node_modules package-lock.json
npm install
```

### "Browser shows blank page"
1. Open DevTools (F12)
2. Go to Console tab
3. Look for red errors
4. Take a screenshot and report (see below)

### "Login doesn't work"
- Did you create the test user? (see Installation Step 2e)
- Is backend running? (check terminal for errors)
- Check browser console for API errors

### Still Having Issues?
See "Contact & Support" section below.
```

### 9. Seção: Como Reportar Bugs

```markdown
## Report a Bug

Found an issue? Thank you for helping improve Hour Ledger!

### What We Need
1. **Description**: What were you trying to do?
2. **Steps to Reproduce**: How do we recreate the issue?
3. **Expected Result**: What should happen?
4. **Actual Result**: What happened instead?
5. **Screenshots**: Visual proof (if applicable)
6. **Browser/OS**: Chrome/Firefox/Safari? Windows/Mac/Linux?

### Example
```
**Title:** Login button doesn't work
**Steps:** 1. Go to login page 2. Enter email and password 3. Click login
**Expected:** Should show dashboard
**Actual:** Page shows error "Network error"
**Browser:** Chrome on Windows 10
```

### How to Submit
Email to: beta-support@example.com
Subject: [Bug Report] Your Title Here

Include all the information above.
```

### 10. Seção: FAQ

```markdown
## Frequently Asked Questions

**Q: Do I need to know how to code?**
A: No! This guide is for everyone.

**Q: Can I use this in production?**
A: No, this is a beta. Only for testing.

**Q: Will my data be deleted?**
A: Possibly. We may reset the database during beta.

**Q: How long is beta?**
A: Approximately 2-4 weeks. We'll announce when stable version is ready.

**Q: What if I find a really bad bug?**
A: Email us immediately at beta-support@example.com

**Q: Can I invite friends to beta?**
A: Please ask first. Send to beta-support@example.com

**Q: Where do I find more information?**
A: Visit our documentation at docs.example.com
```

### 11. Seção: Contato e Suporte

```markdown
## Contact & Support

**Beta Program Manager**
Name: John Doe
Email: john@example.com

**Technical Support**
Email: beta-support@example.com
Response time: 24 hours

**Bug Reports**
Submit to: bugs@example.com
Or use GitHub Issues: https://github.com/YourOrg/hour-ledger/issues

**General Questions**
Slack Channel: #hour-ledger-beta
Invite: https://join.slack.com/...

**Feature Requests**
Email: features@example.com
Include: What you want, Why you want it, How you'd use it
```

### 12. Footer

```markdown
---

**Hour Ledger Beta**  
Version: 1.0-beta.1  
Last Updated: 2026-06-24  
Thank you for testing with us! 🙏
```

### 13. Adicionar Índice no Topo (Opcional)

```markdown
**Table of Contents**
- [Introdução](#introdução)
- [Pré-requisitos](#pré-requisitos)
- [Instalação](#instalação)
- [Como Executar](#como-executar)
- [Dados de Teste](#dados-de-teste)
- [Funcionalidades](#funcionalidades)
- [Troubleshooting](#troubleshooting)
- [Report Bugs](#report-a-bug)
- [FAQ](#frequently-asked-questions)
- [Suporte](#contact--support)
```

## Validação

### Checklist de Completude

- [ ] Documento criado em `docs/operations/BETA-SETUP-GUIDE.md`
- [ ] Seção de introdução clara e acolhedora
- [ ] Pré-requisitos com instruções por SO (Windows/Mac/Linux)
- [ ] Passos de instalação detalhados
- [ ] Cada passo inclui o comando completo
- [ ] Dados de teste são fornecidos
- [ ] Seção de troubleshooting cobre problemas comuns
- [ ] Como reportar bugs está claro
- [ ] Contato de suporte está completo
- [ ] FAQ responde perguntas óbvias
- [ ] Documento é testável por alguém não-técnico
- [ ] Markdown está válido (sem erros de sintaxe)
- [ ] Links funcionam (se há links internos)
- [ ] Nenhuma informação sensível (senhas reais, etc)

### Teste de Usabilidade (Opcional)

Se possível:
1. Pedir para alguém não-técnico ler o guia
2. Pedir que siga os passos
3. Registrar onde ficou confuso
4. Atualizar o documento

## Dependências

- ✅ Tarefas 001-007 devem estar completas
- ✅ Dados de teste criados (Tarefa 006)
- ✅ Contato de suporte deve estar configurado

## Notas Importantes

1. **Linguagem clara**: Evitar jargão técnico. Se usar, explicar.

2. **Concisão**: Não exagerar em detalhes desnecessários.

3. **Otimismo**: Tonalidade acolhedora. Beta é emocionante!

4. **Atualização**: Este guia pode evoluir com feedback dos beta testers.

5. **Públicos diferentes**: 
   - CTO/Tech lead: Pode pular para "How to Run"
   - Usuario business: Precisa de tudo
   - Não-técnico: Precisa de muito detalhe no troubleshooting

## Próxima Tarefa (Pós-Beta)

Após feedback dos clientes beta: **Planejamento de Milestone Extraction Core**

## Sucesso!

Parabéns! Ao completar esta tarefa, terá:

✅ Backend validado e documentado  
✅ Frontend validado e documentado  
✅ i18n funcionando  
✅ Login funcionando  
✅ Wallet (feature principal) funcionando  
✅ Guia claro para clientes beta  
✅ Pronto para lançamento beta!

**Próximo passo**: Compartilhar este guia com clientes beta e coletar feedback.

