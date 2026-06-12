# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**800speedyCRM** is a full-stack service/job management CRM built on **Laravel 9 (PHP)** + **Vue.js 2 SPA**. It handles invoicing, job dispatching, technician management, inventory, and payroll. Originally based on the "Acculance" open-source package (v4.0.5), heavily customized for a tire/battery service business.

## Commands

### Setup
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate
php artisan db:seed
```

### Development
```bash
php artisan serve          # Backend on http://localhost:8000
npm run watch              # Compile & watch frontend assets
npm run hot                # Hot-reload dev server
```

### Build & Deploy
```bash
npm run production         # Minified, versioned frontend assets
php artisan migrate --force
```

### Testing & Linting
```bash
./vendor/bin/phpunit       # PHP unit/feature tests (config: phpunit.xml)
npm run lint               # ESLint with auto-fix for .js and .vue files
./vendor/bin/pint          # Laravel PHP code formatter
```

## Architecture

### Request Flow
Browser → `public/index.php` → Laravel router → one of three route files:
- `routes/api.php` — JWT-protected REST API (all Vue SPA data)
- `routes/web.php` — Landing page + PDF generation endpoints
- `routes/spa.php` — Additional SPA-served routes

### Backend (`app/`)
- **`Http/Controllers/API/`** — 40+ domain controllers (Invoice, Job, Employee, Product, etc.)
- **`Models/`** — 57 Eloquent models; key ones: `User`, `Invoice`, `Job`, `Employee`, `Product`, `TechnicianBatteryStock`
- **`Http/Resources/`** — API response transformers (JSON:API-like)
- **`Http/Requests/`** — Form validation classes
- **`Observers/`** — Model event hooks (e.g., battery movement on job update)
- **`Helpers/`** — `Helper.php`, `CurrencyHelper.php`

Authentication uses **JWT** (`php-open-source-saver/jwt-auth`). The `auth:api` middleware guards all protected API routes. Tokens are stored client-side in localStorage.

### Frontend (`resources/js/`)
- **`app.js`** — Vue app initialization; registers global plugins and components
- **`router/routes.js`** — 50+ route definitions mapping paths to page components
- **`pages/`** — Page-level Vue components (one per major feature)
- **`components/`** — Reusable Vue components (modals, tables, form inputs, etc.)
- **`store/`** — Vuex store with modules: `auth`, `lang`, `operations`
- **`layouts/`** — Shell layouts (sidebar, topbar, etc.)
- **`middleware/`** — Vue Router navigation guards (auth checks, role checks)
- **`plugins/`** — Vue plugin wrappers (i18n, etc.)

Webpack entry: `resources/js/app.js` → `public/dist/js/`. Alias `~` resolves to `resources/js/`.

The single Blade view `resources/views/spa.blade.php` serves the `<div id="app">` mount point for the entire SPA.

### State Management
Vuex modules:
- `auth` — current user, JWT token, login/logout actions
- `lang` — active locale, translations loaded from `resources/lang/`
- `operations` — shared UI state (loading flags, notifications)

### Key Business Flows
- **Jobs**: Status flow `Assigned → DCC → On The Way → Reached → Job Started → Job Completed`. Jobs track `paid_amount` / `due_amount` and can consume battery stock from a technician's allocated inventory.
- **Battery/Inventory**: Two-level tracking — main warehouse stock and per-technician allocated stock (`technician_battery_stocks`, `technician_battery_movements`).
- **Invoices**: Products → subtotal → tax → transport fee → discount → total → payment tracking → PDF export via DomPDF.
- **RBAC**: `roles`, `permissions`, `user_role`, `user_permission` tables with middleware enforcing access.

### Database
MySQL via Eloquent. Migrations in `database/migrations/`. Key junction tables: `invoice_products`, `invoice_payments`, `job_journeys`, `user_role`, `user_permission`.

## Environment Variables
Critical `.env` keys beyond standard Laravel:
- `JWT_SECRET`, `JWT_TTL`, `JWT_ALGO` — JWT auth config
- `TWILIO_ACCOUNT_SID`, `TWILIO_AUTH_TOKEN`, `TWILIO_FROM` — SMS notifications
- `WOOCOMMERCE_*` — Optional WooCommerce store sync
- `IS_DEMO_MODE` — Restricts destructive operations when `true`
- `APP_VERSION` — Displayed in UI

## Conventions
- API responses use Laravel API Resources (`app/Http/Resources/`); always return through a Resource, not raw model data.
- Frontend API calls go through Axios (no fetch). Base URL and JWT header injection are configured in `resources/js/app.js`.
- Translations use Vue-i18n; string keys live in `resources/lang/` (13+ languages). Use `$t('key')` in templates.
- PDF exports are server-rendered Blade views (not client-side); routes are in `routes/web.php` and `routes/spa.php`.
