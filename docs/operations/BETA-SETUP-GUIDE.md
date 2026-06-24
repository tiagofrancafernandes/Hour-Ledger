# Beta Setup Guide — Hour Ledger Ecosystem

Welcome to the **Hour Ledger Beta**! This guide will help you set up and run the application on your machine.

## What is Hour Ledger?

Hour Ledger is a wallet and ledger management system designed for instructors and consultants to track hours and transactions in a secure, auditable way.

**Estimated Setup Time**: 15–20 minutes

---

## Table of Contents

- [Prerequisites](#prerequisites)
- [Clone and Configure Project](#clone-and-configure-project)
- [Backend Setup](#backend-setup)
- [Frontend Setup](#frontend-setup)
- [Run Locally](#run-locally)
- [Test Credentials](#test-credentials)
- [Main Features](#main-features)
- [Troubleshooting](#troubleshooting)
- [Report a Bug](#report-a-bug)
- [FAQ](#frequently-asked-questions)
- [Contact & Support](#contact--support)

---

## Prerequisites

Before starting, ensure you have these installed on your computer:

### Required Software

- **PHP 8.3+** (with SQLite extension)
- **Node.js 18+**
- **Composer** (PHP dependency manager)
- **pnpm** (Node package manager)
- **Git** (optional, but recommended)

### Check Your Versions

Open a terminal/command prompt and run:

```bash
php -v          # Should show 8.3 or higher
node -v         # Should show 18 or higher
npm -v          # Should show 8 or higher
composer -v     # Should show 2.0 or higher
pnpm -v         # Should show 8 or higher
```

### Installation by Operating System

#### **Windows**

1. **PHP 8.3+**
   - Download from: https://windows.php.net/download/
   - Extract to a folder (e.g., `C:\php`)
   - Add to PATH environment variable

2. **Node.js 18+**
   - Download from: https://nodejs.org/
   - Run installer, follow instructions
   - Restart your terminal after installation

3. **Composer**
   - Download from: https://getcomposer.org/
   - Run installer

4. **pnpm**
   ```bash
   npm install -g pnpm
   ```

#### **macOS**

Using Homebrew (https://brew.sh/):

```bash
brew install php@8.3 node composer
npm install -g pnpm
```

If you don't have Homebrew, install it first:
```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

#### **Linux (Ubuntu/Debian)**

```bash
sudo apt-get update
sudo apt-get install php8.3 php8.3-sqlite3 nodejs npm composer
npm install -g pnpm
```

For other Linux distributions, consult your package manager documentation.

---

## Clone and Configure Project

### Step 1: Download the Code

**Option A: Using Git (recommended)**

```bash
git clone https://github.com/your-org/Hour-Ledger-Ecosystem.git
cd Hour-Ledger-Ecosystem
```

**Option B: Download ZIP**

1. Visit the repository
2. Click "Code" → "Download ZIP"
3. Extract to a folder
4. Open terminal in that folder

### Step 2: Verify Folder Structure

You should see:

```
Hour-Ledger-Ecosystem/
├── apps/
│   ├── hl-drive-api/        (Backend)
│   ├── hl-drive-web/        (Frontend)
│   └── ...
├── docs/
├── package.json
└── pnpm-lock.yaml
```

---

## Backend Setup

### Step 1: Navigate to Backend Folder

```bash
cd apps/hl-drive-api
```

### Step 2: Install PHP Dependencies

```bash
composer install
```

This downloads all required PHP libraries. Takes 1–2 minutes on first run.

### Step 3: Copy Environment File

```bash
cp .env.example .env.local
```

**Note**: If `.env.example` doesn't exist, the `.env` file is already configured.

### Step 4: Generate Application Key

```bash
php artisan key:generate
```

Expected output: `Application key [base64:...] set successfully.`

### Step 5: Create Database and Run Migrations

Since we're using SQLite, the database file will be created automatically:

```bash
php artisan migrate
```

Expected output: `Migration table created successfully` followed by migration confirmations.

### Step 6: Create Test User

Run the test user creation script:

```bash
php create-test-user.php
```

Expected output:
```
Test user created successfully!
Email: test@example.com
Password: password123
```

### Step 7: Create Test Admin (Optional)

If you need admin access:

```bash
php create-test-admin.php
```

Expected output:
```
Admin user created successfully!
Email: admin@example.com
Password: password123
```

---

## Frontend Setup

### Step 1: Navigate to Frontend Folder

Open a **new terminal** and navigate to the frontend:

```bash
cd apps/hl-drive-web
```

### Step 2: Install Node Dependencies

```bash
pnpm install
```

If `pnpm` is not installed, use:
```bash
npm install
```

Takes 2–3 minutes on first run.

### Step 3: Verify Environment Configuration

Check that `.env` points to the correct backend URL:

```bash
cat .env
```

You should see:
```env
VITE_API_BASE_URL=http://localhost:8000
```

If the file doesn't exist or needs changes, create it:

```bash
echo "VITE_API_BASE_URL=http://localhost:8000" > .env
```

---

## Run Locally

### Option A: Run Both Together (Recommended)

From the **root folder** of the project:

```bash
pnpm run dev:drive
```

This command starts both backend and frontend automatically.

**Expected Output:**
```
[backend] INFO  Server running on [http://127.0.0.1:8000]
[frontend] VITE v7.2.4  ready in 234 ms
[frontend] ➜  Local:   http://localhost:6010
```

### Option B: Run Manually (If Option A Doesn't Work)

**Terminal 1 – Start Backend:**

```bash
cd apps/hl-drive-api
php artisan serve
```

Expected output:
```
INFO  Server running on [http://127.0.0.1:8000]
```

**Terminal 2 – Start Frontend:**

```bash
cd apps/hl-drive-web
pnpm run dev
```

Expected output:
```
VITE v7.2.4  ready in 234 ms
➜  Local:   http://localhost:6010
```

### Step 3: Open in Browser

Open your browser and navigate to:

```
http://localhost:6010
```

You should see the **Hour Ledger login page**.

### Step 4: Stop the Application

Press `Ctrl + C` (or `Cmd + C` on macOS) in both terminals to stop the application.

---

## Test Credentials

Use these credentials to log in:

| Field | Value |
|-------|-------|
| Email | `test@example.com` |
| Password | `password123` |

**Admin Credentials** (if created):

| Field | Value |
|-------|-------|
| Email | `admin@example.com` |
| Password | `password123` |

After logging in, you'll see:
- Dashboard with account overview
- Wallet section showing your balance
- Transaction history

---

## Main Features

In this beta version, you can:

✅ **Login & Authentication**
- Email and password login
- Session persistence
- Auto-logout after 2 hours of inactivity

✅ **Dashboard**
- Quick account overview
- Recent transactions

✅ **Wallet**
- View your current balance
- See complete transaction history
- Filter transactions by date

✅ **Internationalization (i18n)**
- Switch between Portuguese and English
- Automatic browser language detection

✅ **Responsive Design**
- Full support on desktop
- Tablet support (in progress)
- Mobile support (in progress)

**Features Coming Soon:**
- Transaction creation
- User invitations
- Advanced reporting
- Multi-wallet support

---

## Troubleshooting

### Port 8000 Already in Use

**Error**: `Address already in use` when starting backend

**Solution**:

Find what's using port 8000:

```bash
# macOS/Linux:
lsof -i :8000

# Windows (PowerShell):
netstat -ano | findstr :8000
```

Then either:
1. Stop the other application
2. Change the port in `.env`:
   ```env
   APP_URL=http://localhost:8001
   ```
   Then run: `php artisan serve --port=8001`

### Port 6010 Already in Use

**Error**: `Port 6010 is already in use` when starting frontend

**Solution**:

Edit `apps/hl-drive-web/vite.config.ts` and change:

```typescript
server: {
  port: 6011  // Change from 6010 to 6011
}
```

Then restart the frontend.

### SQLite Database Error

**Error**: `Error opening database`

**Solution**:

1. Ensure the database file exists:
   ```bash
   cd apps/hl-drive-api
   ls -la database.sqlite
   ```

2. If it doesn't exist, create it manually:
   ```bash
   touch database.sqlite
   php artisan migrate
   ```

3. Ensure the file is readable/writable:
   ```bash
   chmod 644 database.sqlite
   ```

### CORS Error in Browser Console

**Error**: `Cross-Origin Request Blocked`

**Solution**:

Verify frontend `.env`:
```bash
cd apps/hl-drive-web
cat .env
```

Should show:
```env
VITE_API_BASE_URL=http://localhost:8000
```

If not, update it and restart the frontend.

### "Composer: command not found"

**Error**: `composer: command not found` or similar

**Solution**:

Reinstall Composer or check if it's in your PATH:

```bash
composer --version
```

If not found:
- **Windows**: Download installer from https://getcomposer.org/
- **macOS/Linux**: 
  ```bash
  curl -sS https://getposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
  ```

### "npm ERR!"

**Error**: Various npm/pnpm errors

**Solution**:

Clear cache and reinstall:

```bash
cd apps/hl-drive-web
pnpm store prune  # or: npm cache clean --force
rm -rf node_modules pnpm-lock.yaml
pnpm install       # or: npm install
```

### Browser Shows Blank Page

**Error**: White screen after login

**Solution**:

1. Open Developer Tools (`F12` in most browsers)
2. Go to **Console** tab
3. Look for red error messages
4. Check for **CORS errors** or **network errors**
5. If errors mention the API, ensure backend is running:
   ```bash
   curl http://localhost:8000/api/health
   ```

If you get a response, backend is running. If not, restart it.

### Login Doesn't Work

**Error**: Login page shows error after entering credentials

**Solution**:

1. Verify test user exists:
   ```bash
   cd apps/hl-drive-api
   php artisan tinker
   >>> App\Models\User::all();
   >>> exit
   ```

2. Ensure backend is running:
   ```bash
   curl http://localhost:8000/api/sanctum/csrf-cookie
   ```

3. Check `.env` CORS settings:
   ```bash
   SANCTUM_STATEFUL_DOMAINS=localhost
   FRONTEND_URL=http://localhost:6010
   ```

4. Restart both frontend and backend

### PHP Version Mismatch

**Error**: `This package requires php ^8.3` or similar

**Solution**:

Verify you have PHP 8.3+:
```bash
php -v
```

If you have an older version:
- **Windows**: Download and reinstall from https://windows.php.net/
- **macOS**: `brew install php@8.3 && brew link php@8.3`
- **Linux**: `sudo apt-get install php8.3`

Then try `composer install` again.

### Database Migration Fails

**Error**: `SQLSTATE[HY000]` or migration errors

**Solution**:

1. Check if SQLite file is corrupted:
   ```bash
   cd apps/hl-drive-api
   rm database.sqlite
   touch database.sqlite
   php artisan migrate
   ```

2. If still failing, check for syntax errors:
   ```bash
   php artisan migrate --step
   ```

3. Review the migration file that's failing

### Node Modules Issues

**Error**: `Module not found` or similar

**Solution**:

```bash
cd apps/hl-drive-web
pnpm install --force  # or: npm install --legacy-peer-deps
```

### Still Not Working?

See the **[Contact & Support](#contact--support)** section below.

---

## Report a Bug

Found an issue? Thank you for helping improve Hour Ledger!

### What We Need

1. **Title**: Brief description of the issue
2. **Steps to Reproduce**: Exact steps to recreate the problem
3. **Expected Behavior**: What should happen?
4. **Actual Behavior**: What actually happened?
5. **Browser & OS**: Chrome/Firefox/Safari? Windows/macOS/Linux?
6. **Screenshots or Logs**: Error messages or visual evidence

### Example Bug Report

```
Title: Login button doesn't respond

Steps to Reproduce:
1. Go to http://localhost:6010
2. Enter email: test@example.com
3. Enter password: password123
4. Click Login button

Expected: Should show dashboard
Actual: Page shows error "Network error" in console

Browser: Chrome 125 on Windows 11
Screenshots: [attached]
```

### How to Submit

**Email**: devtiagofranca@gmail.com  
**Subject**: `[Hour Ledger Bug] Your Title Here`

Include all information above.

---

## Frequently Asked Questions

### Q: Do I need to know how to code?

**A:** No! This guide is designed for everyone. If you can follow step-by-step instructions, you can set it up.

### Q: Can I use this in production?

**A:** No. This is a beta version for testing only. Do not use with real data or in production environments.

### Q: Will my data be deleted?

**A:** Possibly. During the beta period, we may reset the database without notice. Do not store important data.

### Q: How long is the beta?

**A:** Approximately 2–4 weeks. We'll announce the stable release date.

### Q: What if I find a critical bug?

**A:** Email us immediately at devtiagofranca@gmail.com with `[CRITICAL]` in the subject line.

### Q: Can I invite other people to beta?

**A:** Please ask first. Email devtiagofranca@gmail.com with `[Beta Invitation Request]` in the subject line.

### Q: Where do I find documentation?

**A:** See **[Contact & Support](#contact--support)** for links.

### Q: What if my antivirus blocks something?

**A:** Some antivirus software may flag installer files. This is normal. If unsure, download directly from official websites only.

### Q: How do I update to the latest beta version?

**A:** Pull the latest changes from Git:

```bash
git pull origin main
```

Then reinstall dependencies:

```bash
# Backend
cd apps/hl-drive-api
composer install

# Frontend
cd apps/hl-drive-web
pnpm install
```

### Q: Can I run backend and frontend on different machines?

**A:** Yes, but requires advanced setup. For now, keep everything on one machine.

---

## Contact & Support

### Primary Contact

**Name**: Tiago França  
**Email**: devtiagofranca@gmail.com

### Support Channels

- **Bug Reports**: devtiagofranca@gmail.com with subject `[Hour Ledger Bug]`
- **Feature Requests**: devtiagofranca@gmail.com with subject `[Hour Ledger Feature]`
- **Setup Issues**: Same email with subject `[Hour Ledger Setup Help]`

### Response Time

We aim to respond to all inquiries within 24 hours.

### Additional Resources

- **GitHub Repository**: https://github.com/your-org/Hour-Ledger-Ecosystem
- **Documentation**: Check the `/docs` folder in the repository
- **Issue Tracker**: Use GitHub Issues for technical discussions

---

## What's Next?

After setting up:

1. ✅ Log in with test credentials
2. ✅ Explore the Dashboard
3. ✅ Navigate to the Wallet section
4. ✅ Review your balance and transactions
5. ✅ Try switching between Portuguese and English
6. ✅ Test browser responsiveness (resize window)
7. ✅ Report any issues or feedback

---

## Version Information

- **Hour Ledger Version**: 1.0-beta.1
- **Last Updated**: 2026-06-24
- **Backend**: Laravel 12 + PHP 8.3
- **Frontend**: Vue 3 + Vite
- **Database**: SQLite (local)

---

**Thank you for testing Hour Ledger with us! Your feedback is invaluable.** 🙏

For more information, visit the main documentation in `docs/` folder.

---

*This guide may be updated as we receive feedback from beta testers. Check back for updates.*
