<p align="center">
  <img src="https://img.shields.io/badge/Zero--Knowledge%20Password%20Manager-100%25%20Client%20Side%20Crypto-0f1720?style=for-the-badge&labelColor=0f1720&color=22c55e" alt="Zero-Knowledge Password Manager">
</p>

# ZeroKnowledgePM — Zero-Knowledge Password Manager

> Your secrets, encrypted and unlocked **only in your browser**. The server stores ciphertext — nothing else.

[![PHP](https://img.shields.io/badge/PHP-%5E8.3-8892BF?logo=php&logoColor=white&style=flat-square)](composer.json)
[![Laravel](https://img.shields.io/badge/Laravel-13-red?logo=laravel&logoColor=white&style=flat-square)](composer.json)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg?style=flat-square)](LICENSE)
<!-- TODO: wire up a real CI badge once a pipeline (GitHub Actions / other) is configured.
     Example:  [![Tests](https://img.shields.io/github/actions/workflow/status/<owner>/<repo>/tests.yml?branch=main&style=flat-square)] -->
<!-- TODO: replace the version badge source with your real repository/packagist registration once published. -->

---

## Table of Contents

- [Overview](#overview)
- [Why Zero-Knowledge?](#why-zero-knowledge)
- [Key Features](#key-features)
- [Screenshots / Demo](#screenshots--demo)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Getting Started (Local Development)](#getting-started-local-development)
- [Running in Production](#running-in-production)
- [Security](#security)
- [Contributing](#contributing)
- [License](#license)

---

## Overview

**ZeroKnowledgePM** is a web-based password manager built with **Laravel + Blade** and the browser-native **Web Crypto API**. It implements a true **zero-knowledge** architecture: your master password and vault secrets are never transmitted to, or stored by, the server.

Every encryption and decryption operation happens in browser memory with **AES-256-GCM** and keys derived on the client via **PBKDF2-SHA256 + HKDF**. The server only ever sees:

- A **bcrypt** hash of a client-side derived authentication value.
- **Ciphertext blobs** (`vault items`, `encrypted_recovery_blob`, `vault_canary`).
- Non-sensitive metadata (device sessions, audit events, categories).

Even if the database is stolen, the attacker holds only encrypted data they cannot decrypt without your master password _and_ your recovery key.

## Why Zero-Knowledge?

Traditional password managers store (or can technically access) your secrets server-side. A zero-knowledge design removes that entire attack surface:

- **No plaintext, ever.** Credentials, master passwords, and encryption keys never leave the browser except as ciphertext.
- **Local key derivation.** A 600,000-iteration PBKDF2-SHA256 derivation plus separate HKDF contexts derive *distinct* **encryption** and **authentication** keys, so knowing one yields no shortcut to the other.
- **Mandatory 2FA.** TOTP (and optional biometric WebAuthn passkeys) harden account access — enforced app-wide, not as an afterthought.

## Key Features

_Every item below is implemented and exercised by the test suite (`php artisan test`: 99 passing)._

- **Client-side zero-knowledge vault** — AES-256-GCM encryption in browser memory via the Web Crypto API; the vault canary verifies key correctness locally.
- **WebAuthn passkeys** — register biometric/security keys (`navigator.credentials`) for one-tap authentication and biometric vault unlock.
- **Mandatory TOTP / 2FA** — enforced TOTP enrollment with encrypted-at-rest secrets; a fully-protected account = TOTP + WebAuthn.
- **k-Anonymity breach detection** — passwords are checked against **Have I Been Pwned's Pwned Passwords API** entirely client-side: only a 5-char SHA-1 prefix ever leaves the browser (`resources/js/breach.js`). No API key required.
- **Password generator** — random-character, memorable-passphrase, and pronounceable-word modes with strength/crack-time estimates and per-generation breach checks (generation history is in-memory only).
- **Session & device management** — active-session dashboard with device names, location, remember-me flags, per-session expiry, "revoke others", and new-device email alerts.
- **Privacy-preserving audit log** — account events (`login`, `vault_created`, `password_changed`, `webauthn_registered`, ...) recorded and surfaced to the owner; device/IP metadata included without vault contents.
- **Account recovery** — email-OTP gate + Recovery Key that decrypts the `encrypted_recovery_blob` locally; the server never sees the Recovery Key.
- **Mandatory email verification** — queued, signed verification links with rate-limited resend.
- **"Vault Console" design system** — dark/light themes, RTL Arabic + English localization (433 translation keys), responsive layouts, and a custom line-icon set.
- **Defense-in-depth hardening** — strict per-request-nonce CSP, HTTPS enforcement in production, rate limiting on every auth endpoint, UUID keys, and encrypted sensitive columns.

## Screenshots / Demo

<!-- TODO: Add a real screenshot of the dashboard here.
  <img src="docs/screenshots/dashboard.png" alt="Dashboard" width="700"> -->

<!-- TODO: Add a short GIF walking through create → copy → 2FA → unlock.
  <img src="docs/screenshots/demo.gif" alt="Demo"> -->

<!-- TODO: Add a screenshot of the WebAuthn registration flow and the sessions screen. -->

## Tech Stack

| Layer            | Technology                                                             |
| ---------------- | ---------------------------------------------------------------------- |
| Backend          | PHP `^8.3` + [Laravel 13](https://laravel.com) (Blade, Eloquent, queues) |
| Auth scaffold    | Laravel Breeze (session-based, customized for zero-knowledge auth)     |
| Frontend         | Blade + Alpine.js 3 + Tailwind CSS v3 (compiled with Vite 8)           |
| Client crypto    | Native **Web Crypto API** — PBKDF2-SHA256, HKDF-SHA256, AES-256-GCM, SHA-1 (breach) |
| WebAuthn         | Browser `navigator.credentials` (registration, assertions, passkeys)   |
| Database         | MySQL (default) · SQLite supported for local/CI                        |
| Queue            | `database` driver (`jobs` / `failed_jobs` / `job_batches` tables)      |
| Cache / Sessions | `database` driver                                                      |
| Mail             | SMTP (provider-agnostic; Brevo config in `.env.example`)               |
| Testing          | Pest PHP (`tests/`, 99 passing)                                        |

## Architecture

Zero-knowledge architecture splits responsibilities cleanly between the browser and the server:

1. **Client** — derives the Master Key from your Master Password + KDF salt, expands it into separate encryption and authentication keys via HKDF, encrypts every vault item with AES-256-GCM, and checks passwords against Pwned Passwords using k-Anonymity.
2. **Server** — stores only hashes and ciphertext, verifies your derived auth value against the bcrypt hash, issues/validates WebAuthn & TOTP challenges, records audit events, and manages revocable sessions.

The server's only "secret knowledge" is a bcrypt hash — it can never recover your encryption key or your passwords.

For the full detail: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md), the [software requirements spec](docs/SRS/SRS.md), the [technical walkthrough](docs/WALKTHROUGH.md), and [coding standards](docs/CODING_STANDARDS.md).

## Getting Started (Local Development)

### Prerequisites

- **PHP `^8.3`** (tested on 8.3) with extensions used by Laravel (`pdo_mysql`, `mbstring`, `openssl`, etc.)
- **Composer** ([getcomposer.org](https://getcomposer.org))
- **Node.js + npm** (recent LTS) for the frontend build
- **MySQL** (or SQLite for a zero-config start)
- **A queue worker** — see [_"Start the queue worker"_](#start-the-queue-worker) below (required for email)

### 1. Clone the repository

```bash
git clone git@github.com:<your-org>/PasswordManager.git
cd PasswordManager
```

### 2. Install dependencies

```bash
composer install
npm install
```

### 3. Configure the environment

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and set:

| Variable               | Notes                                                                 |
| ---------------------- | --------------------------------------------------------------------- |
| `APP_URL`              | `http://localhost:8000` for local development                         |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | Your MySQL database credentials                |
| `MAIL_*`               | Your SMTP provider's credentials (Brevo placeholders are shown)       |

### 4. Run migrations

```bash
php artisan migrate
```

> **Tip:** `composer run setup` runs install → env copy → key generation → migrate → `npm run build` in one shot.

### 5. Start the queue worker

Email delivery — **verification links, recovery codes, and new-device alerts** — goes through Laravel's **`database` queue**. You must run a worker or emails will stay queued but never send:

```bash
php artisan queue:work --tries=3
```

> Production note: run this under a process manager (e.g. Supervisor) so it stays up permanently — see [Running in Production](#running-in-production).

### 6. Run the dev server & frontend

```bash
npm run dev        # Vite dev server (HMR)
php artisan serve  # Laravel at http://localhost:8000
```

Open **http://localhost:8000**.

> `composer run dev` also boots server + queue worker + logs + Vite together via `concurrently`.
>
> **WebAuthn requires a secure context.** For local development use `http://localhost` (browser treats it as secure); on any other host you'll need HTTPS.

### 7. Optional: seed a demo user

```bash
php artisan db:seed
```

Creates a demonstration account (`test@example.com`) with prefilled TOTP and categories. **Development-only seed data** — not used in production.

### Third-party services

- **Pwned Passwords (breach check)** — client-side k-Anonymity call, **no key required**. Allowed by CSP automatically.
- **GeoIP (session location)** — *intentionally not wired*. Session locations show "unknown" until you pick a provider (e.g. ipinfo / MaxMind). Open item in `docs/ARCHITECTURE.md`.
- **Mail** — any SMTP provider works; get credentials from your provider's dashboard and set `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`.

## Running in Production

See the production-readiness notes applied across this repository:

- **Queue worker under Supervisor** — email depends on it:

```ini
# /etc/supervisor/conf.d/zkpm-worker.conf
[program:zkpm-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/PasswordManager/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killedasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/PasswordManager/storage/logs/queue-worker.log
stopwaitsecs=3600
```

- **Cache everything**:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
npm run build   # production assets
```

- **HTTPS** — enforced automatically when `APP_ENV=production` (see `app/Providers/AppServiceProvider.php`). Set `APP_URL` to your HTTPS origin and `SESSION_SECURE_COOKIE=true`.
- **Logging** — use `LOG_LEVEL=warning` (or `error`) and `LOG_STACK=daily` in production.
- **Failed email jobs** — retained in the `failed_jobs` table; inspect with `php artisan queue:failed` and retry with `php artisan queue:retry all`.
- Cytoscape servers / queue monitoring, key rotation (`APP_PREVIOUS_KEYS`), and other operational decisions are documented in `.env.example`.

## Security

**The zero-knowledge guarantee** is the core of this project:

- Vault secrets, master passwords, and encryption keys **never leave the browser** in plaintext.
- Only bcrypt auth hashes, ciphertext, and non-sensitive metadata are stored server-side.
- **Mandatory 2FA** (TOTP, plus optional WebAuthn passkeys) protects account access.
- Strict **Content-Security-Policy** with a per-request nonce, HTTPS-only URLs in production, and rate-limited auth endpoints.

If you believe you've found a security vulnerability, please report it privately rather than opening a public issue:

**security@example.com** <!-- TODO: replace with your real security contact -->
We aim to acknowledge reports within 48 hours.

## Contributing

Contributions are welcome! Please:

1. Read [docs/CODING_STANDARDS.md](docs/CODING_STANDARDS.md) first — the project enforces strict formatting and commenting rules (Pint).
2. Follow the module pattern: server endpoints are thin, all crypto is client-side (`resources/js/`).
3. Run the test suite before opening a PR:

```bash
composer run test      # runs php artisan test (Pest)
npm run build          # frontend must build cleanly
```

## License

**MIT** — see [`LICENSE`](LICENSE).

<!-- TODO: confirm and add the actual LICENSE file. The repo currently targets MIT; adjust if you
     prefer a different license (AGPL/GPL are common for security tooling). -->
Eiiiiiiiiiiiiiiiiiiiiiiiiiid
