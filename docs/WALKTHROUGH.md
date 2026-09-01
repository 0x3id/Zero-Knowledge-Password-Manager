# Zero-Knowledge Password Manager — Technical Walkthrough & Architecture Reference

## 1. System Overview & Architecture Executed

The **Zero-Knowledge Password Manager** is a high-security, defense-in-depth credential management web application built on **Laravel 11 + Blade** and client-side cryptography utilizing the native **W3C Web Crypto API (`window.crypto.subtle`)**. 

The system guarantees the **Zero-Knowledge Principle**: the server never sees, transmits, or persists raw master passwords, unencrypted vault secrets, or the Master Encryption Key. All cryptographic operations (key derivation, AES-256-GCM vault encryption/decryption, k-Anonymity breach hashing) occur exclusively within the client's browser memory.

### Core Features Implemented:
1. **Zero-Knowledge Key Derivation (PBKDF2 + HKDF):**
   - PBKDF2-SHA256 with 600,000 iterations (OWASP 2023 minimum recommendation) derives a root Master Key from the Master Password and unique `kdf_salt`.
   - Dual-context HKDF derives two distinct, independent 256-bit keys:
     - `zkpm:encryption` $\rightarrow$ Master Encryption Key (held strictly in client memory as a non-extractable `CryptoKey`).
     - `zkpm:authentication` $\rightarrow$ Auth Hash Input (transmitted to the server, hashed with bcrypt at rest).
2. **Encrypted Vault & Category Management (AES-256-GCM):**
   - Client-side encryption of passwords and notes using fresh 12-byte random IVs per record.
   - Comprehensive category organization, real-time client-side search filtering, and reveal/copy clipboard utilities.
3. **Dual-Layer Multi-Factor Authentication (WebAuthn + TOTP):**
   - Mandatory TOTP enrollment and challenge on login.
   - Biometric/hardware security key registration and assertion via native W3C Web Authentication standard.
4. **Live Leak Detection via k-Anonymity (SHA-1):**
   - Client computes the SHA-1 hash of candidate passwords locally and queries the Have I Been Pwned API with only the first 5 hexadecimal characters.
   - Suffix matches and breach occurrence counts are evaluated locally without leaking the candidate password or its complete hash.
5. **Advanced Password Generator:**
   - Three generation modes: Random Characters, Memorable Dictionary Passphrases, and Pronounceable Syllables.
   - Real-time bit-entropy calculation ($E = L \times \log_2 N$) and offline GPU brute-force crack time estimation.
   - Automatic breach protection with auto-regeneration toggle.
   - Ephemeral in-memory history of the last 5 generated passwords.
6. **Session Management & Device Revocation:**
   - Tracking of active browser sessions (IP address, user-agent device parsing, last active timestamps, expiration).
   - Single-device revocation and bulk revocation of all other devices.
7. **10-Minute Inactivity Auto-Lock & Un-dismissible Modal:**
   - Client-side timer monitoring mouse, touch, scroll, and keyboard events.
   - Auto-lock clears in-memory keys and IndexedDB unlock blobs, presenting an un-dismissible re-authentication modal overlay.
   - Dual unlock paths: Master Password (canary verification) or WebAuthn biometrics.
8. **Account Recovery Workflow:**
   - 32-character random Recovery Key issued once during signup.
   - Multi-step recovery with email OTP identity proof gate, rate limiting, client-side blob decryption, and Master Password rotation preserving vault item decryptability.
9. **Privacy-Preserving Audit Logs:**
   - Permanent metadata-only security logs (action types, IP, device, timestamp) with zero leakage of vault item titles or plaintext values.
10. **Strict Content-Security-Policy (CSP) Middleware:**
    - Defends against XSS, clickjacking (`X-Frame-Options: DENY`, `frame-ancestors: 'none'`), and unauthorized resource connections.

---

## 2. Project Structure

```
PasswordManager/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── AuthenticatedSessionController.php  # Factor-1 authentication & timing-safe checks
│   │   │   │   ├── LockController.php                  # Serves vault canary & lock context
│   │   │   │   ├── RecoveryController.php              # Multi-step account recovery with OTP gate
│   │   │   │   ├── RegisteredUserController.php        # Zero-knowledge registration handler
│   │   │   │   ├── SaltLookupController.php            # KDF salt lookup with decoy generation
│   │   │   │   ├── TOTPController.php                  # TOTP controller alias
│   │   │   │   ├── TwoFactorAuthenticatedSessionController.php # Factor-2 TOTP login challenge
│   │   │   │   ├── TwoFactorSecretController.php       # TOTP enrollment & verification
│   │   │   │   ├── WebauthnAuthenticationController.php # WebAuthn assertion verification
│   │   │   │   └── WebauthnRegistrationController.php   # WebAuthn passkey management
│   │   │   ├── AuditLogController.php                  # Privacy-preserving audit log viewer
│   │   │   ├── CategoryController.php                  # Vault category CRUD & ownership checks
│   │   │   ├── DashboardController.php                 # Decrypted vault view controller
│   │   │   ├── PasswordGeneratorController.php         # Standalone password generator view
│   │   │   ├── ProfileController.php                   # Profile information and account deletion
│   │   │   ├── SessionController.php                   # Active sessions & device revocation
│   │   │   └── VaultItemController.php                 # Vault item CRUD (ciphertext payloads)
│   │   └── Middleware/
│   │       └── ContentSecurityPolicy.php               # Strict CSP, anti-clickjacking, & security headers
│   ├── Models/
│   │   ├── AuditLog.php                                # Security audit log entity
│   │   ├── Category.php                                # Vault category entity
│   │   ├── User.php                                    # Zero-knowledge authenticatable user entity
│   │   ├── VaultItem.php                               # Encrypted credential item entity
│   │   └── WebauthnCredential.php                      # WebAuthn public key credential entity
│   └── Services/
│       ├── AuditLogger.php                             # Metadata-only security event logger
│       ├── SessionManager.php                          # Session lifecycle & new device alerts
│       ├── TOTPService.php                             # Native RFC 6238 TOTP generator/verifier
│       └── WebAuthnService.php                         # Native WebAuthn Level 2 OpenSSL verifier
├── bootstrap/
│   └── app.php                                         # Application bootstrap & middleware pipeline
├── database/
│   ├── factories/
│   │   ├── CategoryFactory.php
│   │   └── UserFactory.php
│   ├── migrations/                                     # Database schema migrations
│   └── seeders/
│       └── DatabaseSeeder.php                          # Demo user, categories, and test data
├── docs/
│   ├── ARCHITECTURE.md                                 # Technical architecture specification
│   ├── CODING_STANDARDS.md                             # Strict coding & security guidelines
│   ├── SRS/                                            # IEEE 830-1998 Software Requirements
│   └── WALKTHROUGH.md                                  # Complete system walkthrough (this file)
├── resources/
│   ├── css/
│   │   └── app.css                                     # TailwindCSS styling definitions
│   ├── js/
│   │   ├── app.js                                      # Application bootstrap & module initialization
│   │   ├── auth.js                                     # Auth handlers (registration, login, recovery, TOTP)
│   │   ├── breach.js                                   # Live leak detection via k-Anonymity (SHA-1)
│   │   ├── crypto.js                                   # Core Web Crypto API cryptographic operations
│   │   ├── generator.js                                # Multi-mode password generator & entropy engine
│   │   ├── lock.js                                     # Inactivity auto-lock & re-authentication modal
│   │   ├── vault.js                                    # Vault item CRUD & category management
│   │   ├── webauthn.js                                 # WebAuthn navigator.credentials wrapper
│   │   └── webauthn-manager.js                         # Profile WebAuthn passkey management UI
│   └── views/
│       ├── audit-logs/index.blade.php                  # Audit log timeline view
│       ├── auth/                                       # Auth Blade views (login, register, totp, recovery)
│       ├── components/
│       │   └── lock-modal.blade.php                    # Un-dismissible vault lock modal component
│       ├── dashboard.blade.php                         # Main vault management interface
│       ├── generator/index.blade.php                   # Standalone password generator page
│       ├── layouts/
│       │   ├── app.blade.php                           # Authenticated layout with CSP meta & lock modal
│       │   ├── guest.blade.php                         # Guest layout
│       │   └── navigation.blade.php                    # Navigation bar with fast Lock Vault button
│       ├── profile/                                    # Profile, 2FA status, and passkey views
│       └── sessions/index.blade.php                    # Multi-device session revocation interface
├── routes/
│   ├── auth.php                                        # Guest and 2FA authentication routes
│   └── web.php                                         # Authenticated application web routes
└── tests/
    └── Feature/                                        # Comprehensive automated test suite (71 tests)
```

---

## 3. Cryptographic Flow Documentation

### A. Client-Side Key Derivation Hierarchy

```
                      +-----------------------------+
                      |   User's Master Password    |
                      +-----------------------------+
                                     |
                                     v
                        [ + 16-byte random kdf_salt ]
                                     |
                                     v
                     +-------------------------------+
                     |         PBKDF2-SHA256         |
                     |     (600,000 Iterations)      |
                     +-------------------------------+
                                     |
                                     v
                       +---------------------------+
                       |   Master Key (256-bit)    |
                       +---------------------------+
                                     |
                    +----------------+----------------+
                    |                                 |
                    v                                 v
        [ HKDF: "zkpm:encryption" ]      [ HKDF: "zkpm:authentication" ]
                    |                                 |
                    v                                 v
      +----------------------------+    +----------------------------+
      |    Encryption Key (256b)   |    |    Auth Hash Input (256b)  |
      | (Non-Extractable CryptoKey)|    |     (Base64 Encoded)       |
      |   *Kept in Memory Only*    |    |  *Transmitted to Server*   |
      +----------------------------+    +----------------------------+
                    |                                 |
                    |                                 v
                    |                   +----------------------------+
                    |                   |     Server bcrypt Hash     |
                    |                   |   Stored in `users` Table  |
                    |                   +----------------------------+
                    |
      +-------------+-------------+
      |                           |
      v                           v
+-----------------------+   +-----------------------+
|  Vault AES-256-GCM    |   |     Vault Canary      |
|  Encryption of Items  |   | Verification Payload  |
+-----------------------+   +-----------------------+
```

### B. Vault Item Encryption & Decryption Process

* **Encryption (Store/Update):**
  1. Plaintext password and optional notes are encoded to `Uint8Array` buffers.
  2. Native `window.crypto.getRandomValues(new Uint8Array(12))` generates a unique 12-byte Initialization Vector (IV).
  3. `window.crypto.subtle.encrypt({ name: "AES-GCM", iv }, key, data)` computes the ciphertext and GCM authentication tag.
  4. Ciphertext and IV are Base64-encoded and posted to the server. The server stores ciphertext exclusively.

* **Decryption (View/Edit/Copy):**
  1. The authenticated client fetches the encrypted records from `/vault`.
  2. Base64 ciphertext and IV are decoded back to byte arrays.
  3. `window.crypto.subtle.decrypt({ name: "AES-GCM", iv }, key, ciphertext)` verifies the authentication tag and recovers plaintext in memory.
  4. Sensitive plaintexts are cleared from DOM memory when hidden or locked.

### C. Live Leak Detection (k-Anonymity Model)

```
Candidate Password
       |
       v
[ SHA-1 Hash: e.g. 5BAA61E4C9B93F3F0682250B6CF8331B7EE68FD8 ]
       |
       +---> Prefix (First 5 chars: "5BAA6") ---> Sent to HIBP Range API
       |                                          (api.pwnedpasswords.com/range/5BAA6)
       |
       |                                                    |
       |                                                    v
       |                                        Returns matching suffixes
       |                                        (e.g., 1E4C9B93F3F0682250B6...: 18456)
       |                                                    |
       v                                                    v
Local Suffix Comparison: "1E4C9B93F3F0682250B6CF8331B7EE68FD8" matches?
       |
       +---> If MATCH: Display breach alert with occurrence count.
       +---> If NO MATCH: Mark password as clean.
```

### D. Inactivity Auto-Lock & Key Lifecycle
1. **Active State:** User interactions (`pointerdown`, `keydown`, `mousemove`, `scroll`, `touchstart`) continuously reset a 30-minute inactivity timer.

2. **Timeout Event:** On 30 minutes of inactivity:
   - Module-scoped `CryptoKey` reference is set to `null` and garbage-collected.
   - Ephemeral `sessionStorage` keys are purged.
   - Per-device IndexedDB biometric unlock blobs are deleted.
   - An un-dismissible full-screen lock overlay is displayed.
   - The Laravel HTTP session cookie remains valid, distinguishing "vault locked" from "logged out".
3. **Re-Authentication:**
   - **Master Password Unlock:** Key is derived locally with PBKDF2 and tested against the server-provided `vault_canary`. If the canary decrypts to `PasswordManager::vault-canary-v1::ok`, the key is accepted and restored to memory.
   - **Biometric WebAuthn Unlock:** Platform authenticator assertion is validated by the server, which authorizes the client to decrypt its local device blob into memory.

---

## 4. Environment Setup & How to Run

### Prerequisites
- PHP 8.2 or higher with OpenSSL, SQLite/MySQL, BCMath, and Mbstring extensions.
- Composer 2.x
- Node.js (v20+ or v22 LTS) & NPM

### Step-by-Step Installation

1. **Clone the Repository & Navigate to Directory:**
   ```bash
   cd /home/eid/PasswordManager
   ```

2. **Install PHP Dependencies:**
   ```bash
   composer install
   ```

3. **Install JavaScript Dependencies:**
   ```bash
   npm install
   ```

4. **Configure Environment File:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Run Database Migrations & Seed Default Data:**
   ```bash
   php artisan migrate:fresh --seed
   ```
   *Default Seeded Demo Account:*
   - **Email:** `test@example.com`
   - **Master Password:** `Password123!` (Auth Hash Input: `zkpm-test-auth-hash-input`)
   - **TOTP 2FA Secret:** `JBSWY3DPEHPK3PXP` (Use Google Authenticator or manual code)

6. **Compile Frontend Assets:**
   - *For Production Build:*
     ```bash
     npm run build
     ```
   - *For Local Development Hot Reloading:*
     ```bash
     npm run dev
     ```

7. **Start the Laravel Development Server:**
   ```bash
   php artisan serve --port=8000
   ```
   Access the application at `http://localhost:8000`.

---

## 5. Testing & Verification Guidelines

### Automated Test Suite Execution

Run the complete Pest/PHPUnit test suite:
```bash
php artisan test
```
All **71 automated tests** covering registration, login, TOTP challenges, account recovery, categories, vault item encryption boundaries, session revocations, WebAuthn credentials, and strict CSP headers will execute and pass.

### Manual Verification Checklist

1. **Zero-Knowledge Registration:**
   - Register a new account at `/register`. Note that a 32-character Recovery Key is generated client-side and presented once.
   - Complete mandatory TOTP 2FA enrollment using an authenticator app (e.g., Google Authenticator).
2. **Vault CRUD & Live Breach Check:**
   - On `/` (Dashboard), click **"Add Item"**.
   - Type a commonly breached password (e.g., `password123`) $\rightarrow$ verify the live breach warning displays the leak count via k-Anonymity.
   - Click **"Generate Strong"** $\rightarrow$ verify a strong, clean password is generated and encrypted upon saving.
   - Click **"Reveal"** and **"Copy"** on saved items to verify client-side AES-256-GCM decryption.
3. **Category Organization:**
   - Click **"Categories"** on the dashboard, create a custom category (e.g. "Banking"), and assign it to an item.
   - Click category filter pills to verify instant client-side filtering.
4. **Auto-Lock & Manual Lock:**
   - Click **"Lock Vault"** in the top navigation bar $\rightarrow$ verify in-memory keys are cleared and the un-dismissible lock modal appears.
   - Re-enter the Master Password $\rightarrow$ verify the canary verifies and vault access is immediately restored.
5. **Multi-Device Session Revocation:**
   - Visit `/sessions` $\rightarrow$ inspect active devices.
   - Open a secondary browser or incognito window, log in, and verify the new session appears.
   - Click **"Revoke Device"** or **"Revoke All Other Devices"** $\rightarrow$ verify the session is immediately invalidated.
6. **Account Recovery:**
   - Log out, navigate to `/recovery`, request an email OTP, submit the OTP with your 32-character Recovery Key, and set a new Master Password.
   - Verify that your vault items remain decryptable with the recovered encryption key.
