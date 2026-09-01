# ARCHITECTURE.md

## 1. System Overview

**Zero-Knowledge Architecture Password Manager** built with **Laravel + Blade** (using **Laravel Breeze** for session scaffolding), utilizing **Client-Side Cryptography (Web Crypto API)** for all encryption/decryption, reinforced with **Dual 2FA (WebAuthn + TOTP)**, **k-Anonymity Leak Detection**, professional **Session/Device Management**, an **Audit Log**, and a **Strict Content Security Policy (CSP)**.

---

## 2. Core Architectural Principles

* **Zero-Knowledge Guarantee:** The server (Laravel) stores only auth hashes and encrypted blobs. Plaintext passwords and the Master Encryption Key never touch the network or the server.
* **Separated Key Derivation:** The Encryption Key and the Auth Hash are derived via **separate HKDF contexts** from the same Master Key, so possession of one gives no shortcut to the other.
* **Multi-Device by Design:** Login (Master Password) works identically from any device. Biometric unlock (WebAuthn) is a *per-device convenience layer*, not a replacement for the underlying key derivation.
* **Defense-in-Depth:** Mandatory 2FA (WebAuthn and/or TOTP), Strict CSP against XSS, zero-leak breach checks (k-Anonymity), non-extractable in-memory keys, and revocable sessions.

---

## 3. Database Schema (Laravel Migrations)

### `users`

* `id`: Primary Key
* `email`: String (Unique)
* `auth_hash`: String (bcrypt of client-derived `auth_hash_input`)
* `kdf_salt`: String (Unique salt for key derivation)
* `kdf_params`: JSON (algorithm, iterations/memory cost — versioned for future upgrades)
* `encrypted_recovery_blob`: Text (Encryption Key, encrypted under a key derived from the Recovery Key)
* `vault_canary`: Text (Fixed known value encrypted with the Encryption Key — used to verify a derived key is correct without touching real vault data)
* `is_2fa_enabled`: Boolean (Default: `false`) — **mandatory for all users going forward**
* `totp_secret`: Text (Nullable, encrypted at rest server-side)
* `timestamps`

### `webauthn_credentials`

* `id`: UUID Primary Key
* `user_id`: Foreign Key (`users.id`)
* `credential_id`: Text (Unique device identifier)
* `public_key`: Text (Public key for signature verification)
* `device_label`: String (User-friendly name, e.g. "iPhone – Ahmed", "MacBook – Work")
* `counter`: Unsigned Integer
* `timestamps`

### `sessions` (extends Breeze's default table)

* `id`: String Primary Key (session ID)
* `user_id`: Foreign Key (`users.id`, nullable)
* `ip_address`: String
* `device_name`: String (parsed from User-Agent)
* `location`: String (City/Country via GeoIP)
* `is_remember_me`: Boolean
* `last_active_at`: Timestamp
* `expires_at`: Timestamp (24h default, 7 days if "remember me")
* `payload`, `created_at` (Breeze defaults)

### `audit_logs`

* `id`: Primary Key
* `user_id`: Foreign Key (`users.id`)
* `action_type`: Enum (`login`, `logout`, `vault_item_created`, `vault_item_updated`, `vault_item_deleted`, `password_changed`, `webauthn_registered`, `webauthn_removed`, `2fa_totp_enabled`, `session_revoked`, `recovery_used`)
* `ip_address`: String
* `device_info`: String
* `created_at`: Timestamp
* *(No vault content or item titles are ever logged — action + metadata only)*
* *Retention policy: TBD — recommend auto-pruning entries older than a defined window (e.g. 90 days) via scheduled job*

### `categories`

* `id`: Primary Key
* `user_id`: Foreign Key (`users.id`)
* `name`: String
* `timestamps`

### `vault_items`

* `id`: Primary Key
* `user_id`: Foreign Key (`users.id`)
* `category_id`: Foreign Key (`categories.id`, Nullable)
* `title`: String
* `username`: String
* `encrypted_password`: Text (AES-256-GCM)
* `encrypted_notes`: Text (Nullable, AES-256-GCM)
* `iv`: String
* `url`: String (Nullable)
* `timestamps`

---

## 4. Key Derivation & Cryptography Workflows

### A. Master Key Derivation (Client-Side)

```php
Master Password + kdf_salt --[PBKDF2-SHA256, 600,000 iterations]--> Master Key (256-bit)
                       |
                       +--[HKDF, info="encryption"]--> Encryption Key   (kept in memory only)
                       |
                       +--[HKDF, info="authentication"]--> Auth Hash Input --> sent to server
```

> **KDF choice:** PBKDF2-SHA256 (native Web Crypto API, no external dependency, OWASP 2023 recommended minimum: 600,000 iterations). Argon2id remains an option later if a vetted WASM library is introduced, but is not required for MVP.

### B. Vault Item Encryption & Decryption

* **Encryption (Store):** `Plaintext + Encryption Key + Random 12-byte IV --[AES-256-GCM]--> encrypted_password + iv`
* **Decryption (View):** `encrypted_password + iv + Encryption Key --[AES-256-GCM]--> Plaintext`

### C. In-Memory Key Storage

* The Encryption Key is held as a **non-extractable `CryptoKey`** (via `crypto.subtle.importKey(..., extractable: false, ...)`) inside a module-scoped JS variable — **not** `sessionStorage`. This prevents a raw key export even under XSS; the key can only be *used* for crypto operations, not read out.

### D. WebAuthn-Backed Unlock (per device)

```php
On first successful unlock after login (per device):
  Encryption Key --[encrypt with key derived from WebAuthn assertion]--> stored in IndexedDB (this device only)

On auto-lock:
  In-memory Encryption Key cleared
  IndexedDB entry cleared

On unlock:
  WebAuthn assertion --> decrypts IndexedDB blob --> Encryption Key restored to memory
  (Fallback: re-derive from Master Password + kdf_salt, verified against vault_canary)

```

> Note: TOTP cannot back this flow — a 6-digit code carries no secret the client can derive a key from. Users whose only 2FA method is TOTP will always fall back to Master Password for unlock.

---

## 5. Detailed User Journeys

### 1. Sign Up

1. User enters Email and Master Password.
2. JS generates `kdf_salt`, derives Master Key → Encryption Key + Auth Hash Input.
3. JS generates a 32-character random `Recovery Key`, encrypts the Encryption Key under a key derived from it → `encrypted_recovery_blob`.
4. JS encrypts a fixed known value with the Encryption Key → `vault_canary`.
5. Payload sent to Laravel: `{ email, auth_hash_input, kdf_salt, encrypted_recovery_blob, vault_canary }`.
6. User is prompted to save the `Recovery Key` (shown once).
7. **Mandatory 2FA setup:** user registers at least one WebAuthn credential and/or a TOTP authenticator app before reaching the dashboard. Users may enable both and add multiple WebAuthn credentials (e.g. phone + laptop).

### 2. Login & 2FA Flow

1. **Factor 1:** User submits email + Master Password. JS fetches `kdf_salt` (server returns a decoy salt for unknown emails to prevent user enumeration), derives `Auth Hash Input`, posts to `/api/login`.
2. **Factor 2:** Server requires either WebAuthn or TOTP (user's choice if both are enrolled) — mandatory for every login.
3. **Session Init:** On success, Breeze issues a session cookie (24h default, 7 days if "Remember Me"). A new row is written to the extended `sessions` table with device/IP/location. A device/location-based **email alert** is sent — only for a new device or new location, not every login, to avoid alert fatigue.
4. Encryption Key is derived client-side and held in memory (non-extractable). If a WebAuthn-backed unlock blob doesn't yet exist for this device, one is created after this first successful derivation.

### 3. Auto-Lock & Unlock (30-minute timeout)

1. Activity listeners reset a 30-minute timer.
2. On timeout: in-memory Encryption Key and the device's IndexedDB blob are both cleared. Lock Modal appears over the Blade view (Laravel session cookie remains valid — the user is still "logged in," just vault-locked).
3. **Unlock options:**
   - **Biometric (default, if WebAuthn enrolled on this device):** `navigator.credentials.get()` → decrypts the per-device IndexedDB blob → Encryption Key restored.
   - **Master Password (fallback, always available):** re-derive Encryption Key, verify by decrypting `vault_canary`.

### 4. Vault Item Creation & Live Breach Guard

1. User types item details and password.
2. JS computes `SHA-1(password)`, sends only the first 5 hex chars to `https://api.pwnedpasswords.com/range/...` (k-Anonymity), checks the returned suffix list locally, warns if leaked.
3. JS encrypts with AES-256-GCM using the in-memory Encryption Key, sends payload to `POST /api/vault`.
4. Audit log records `vault_item_created` (metadata only — no title/content).

### 5. Account Recovery (forgotten password or lost 2FA device)

1. User selects "Forgot Master Password" or "Lost my 2FA device."
2. User submits the `Recovery Key`. **Server-side gate required** (rate limiting + partial identity proof, e.g. email OTP) before `encrypted_recovery_blob` is released, to prevent offline brute-forcing of the recovery key.
3. JS decrypts `encrypted_recovery_blob` locally with the Recovery Key → recovers the original Encryption Key.
4. **If password reset:** user sets a new Master Password; a new `auth_hash_input` is derived and sent; the Encryption Key itself is unchanged (existing vault stays decryptable), so `encrypted_recovery_blob` and `vault_canary` are simply re-encrypted/re-verified against it.
5. **If 2FA device lost:** user registers a new WebAuthn credential and/or TOTP secret; the lost credential is revoked.

### 6. Session / Device Management

1. User views a list of active sessions/devices (device name, IP, location, last active, remember-me status) — access to this page requires light re-authentication.
2. User can revoke any individual session (deletes its row → session invalid immediately) or "Log out of all devices" (deletes all rows except current).
3. Revoking a device does **not** remotely wipe that device's local IndexedDB blob — for a lost/stolen device, "Log out of all devices" is the effective control, since it invalidates the session server-side regardless of local state.

### 7. Audit Log

1. User views a chronological list of account actions (login, logout, vault changes, 2FA changes, session revocations, recovery use) with device/IP metadata, over a configurable time window.
2. No vault content, item titles, or credentials ever appear in log entries.

---

## 6. Core Features Mechanics

### Feature 1: Advanced Password Generator

* **Modes:** Random String, Memorable Passphrases, Pronounceable Words.
* **Entropy Calculation:** Bit-entropy and estimated crack time.
* **Auto-Breach Prevention:** SHA-1 + k-Anonymity check, auto-regenerates if leaked.
* **Local Session History:** Last 5 generated passwords held in memory only (not persisted).

### Feature 2: Live Breach Guard

See Journey 4 above.

### Feature 3: Account Recovery

See Journey 5 above.

### Feature 4 (Planned — not yet designed in detail): Secure Password Sharing

User will be able to share a vault item with another user. Requires an asymmetric re-encryption layer (recipient's public key wraps a copy of the item's encryption material) so the server still never sees plaintext. **To be designed in a follow-up session.**

---

## 7. Security Hardening Policies

* **Strict CSP Header Middleware** with a **per-request nonce** (not static): `Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{per-request}'; object-src 'none';`
* **Zero External JS Crypto Dependencies:** All crypto runs via native `window.crypto.subtle`.
* **Zero-Leak API Rule:** Only 5-character SHA-1 prefixes ever leave the client for breach checks.
* **Rate Limiting (required, not yet fully specified):** strict limits on `/api/login`, account recovery endpoints, and any endpoint returning `encrypted_recovery_blob` or `kdf_salt`.
* **User Enumeration Prevention:** decoy salt returned for unknown emails on login/salt-lookup endpoints.
* **Non-Extractable Keys:** Encryption Key is never held as raw exportable bytes in JS-accessible storage.

---

## 8. Open Items / Next Design Session

* Finalize whether WebAuthn enrollment is mandatory *at* signup or allowed within a grace period.
* Specify IP-to-location provider (e.g. `ipapi.co` vs. self-hosted MaxMind GeoLite2).
* Design "Trusted Devices" (skip repeat 2FA challenges on recognized devices) — optional UX improvement.
* Full design of Secure Password Sharing (Feature 4).
* Define Audit Log retention period (storage window vs. UI display window).
* Decide exact rate-limit thresholds per endpoint.
