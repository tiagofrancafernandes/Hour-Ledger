# Implementation Plan: Local Setup & Frontend i18n

This plan covers the configuration of the local development environment and the implementation of multi-language support in the frontend.

## User Review Required

> [!IMPORTANT]
> **Dependencies:** I need to install `concurrently` at the root and `vue-i18n` in the frontend app.
> **Database:** I will execute `php artisan migrate:fresh --seed` to ensure the database is ready.

## Proposed Changes

### 1. Local Environment Setup

#### [MODIFY] [Root package.json](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/package.json)
- Add `dev:drive` script using `concurrently` to run API (port 6011) and Web (port 6010).
- Add `concurrently` to `devDependencies`.

#### [MODIFY] [Backend .env](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-api/.env)
- Set `DB_HOST=172.17.0.1`, `DB_PORT=1010`, `DB_DATABASE=hl_drive`, `DB_USERNAME=postgres`, `DB_PASSWORD=postgres`.
- Set `APP_URL=https://api.local.hldrive.com`.
- Ensure `SANCTUM_STATEFUL_DOMAINS=local.hldrive.com`.

#### [MODIFY] [Frontend vite.config.ts](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web/vite.config.ts)
- Configure `server.port = 6010`.
- Configure `server.host = true`.
- Configure `server.hmr.clientPort = 443` (since it's behind an SSL proxy).

#### [MODIFY] [Frontend .env](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web/.env)
- Set `VITE_API_BASE_URL=https://api.local.hldrive.com`.

---

### 2. Frontend Internationalization (i18n)

#### [NEW] [i18n Plugin](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web/src/plugins/i18n.ts)
- Initialize `vue-i18n` with `pt-BR` and `en` support.
- Use Composition API mode.

#### [NEW] [Locales](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web/src/locales/pt-BR.json)
- Initial translations for common terms (Login, Wallet, Balance, etc.).

#### [MODIFY] [Frontend main.ts](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web/src/main.ts)
- Register the i18n plugin.

#### [MODIFY] [LoginView.vue](file:///mnt/ext4_arquivos/projects/Hour-Ledger-Ecosystem/apps/hl-drive-web/src/views/LoginView.vue)
- Demonstrate usage of `$t` or `useI18n`.

## Verification Plan

### Automated Tests
- Run `php artisan test` (after migrations).
- Run `npm run lint` in frontend.

### Manual Verification
- Use `chrome-devtools-mcp` to:
  1. Access `https://local.hldrive.com`.
  2. Verify that the login page loads and shows translated strings.
  3. Verify that the API call to `https://api.local.hldrive.com` works.
  4. Test switching languages (if a switcher is added).
