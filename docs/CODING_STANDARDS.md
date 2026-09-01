# CODING_STANDARDS.md

This document defines the strict code formatting, commenting, and structural guidelines for the **Zero-Knowledge Password Manager** project. All AI Agents and developers **must** adhere to these rules when writing or modifying code.

---

## 1. General Code Formatting Rules

* **Indentation:** Exactly **4 spaces** per indent level (Do NOT use hard Tabs).
* **Line Length:** Soft limit of 120 characters per line.
* **Character Encoding:** UTF-8 without BOM.
* **PHP / JS Style:** PSR-12 standard for PHP / Extended Clean Code standard for JavaScript.

---

## 2. Function & Method Documentation (DocBlocks)

Every function, method, and component must include a comprehensive DocBlock explaining its purpose, security considerations, parameters, and return types.

### PHP (Laravel) Template
```php
/**
 * Explain the main purpose of the function here.
 * Include any security warnings or context if applicable.
 *
 * @param  string  $email  The user's registered email address.
 * @param  string  $authHash  The client-side generated authentication hash.
 * @return \Illuminate\Http\JsonResponse Returns session token or 2FA challenge payload.
 */
public function authenticateUser(string $email, string $authHash): JsonResponse
{
    // Implementation code here
}
```

### JavaScript (Client-Side Crypto) Template
```javascript
/**
 * Generates an AES-256-GCM encryption key from a master password and salt using PBKDF2.
 * NEVER send the output key to the server.
 *
 * @param {string} masterPassword - The raw user master password.
 * @param {string} salt - The unique user salt retrieved from server.
 * @returns {Promise<CryptoKey>} The derived Web CryptoKey object for local vault operations.
 */
async function deriveEncryptionKey(masterPassword, salt) {
    // Implementation code here
}
```

---

## 3. Inline Commenting Guidelines

* Use inline comments (`//`) to explain **WHY** something is done, especially for cryptographic steps and security logic (not **WHAT** the code does).
* Group logical steps inside functions with clear section headers.

```javascript
/**
 * Encrypts vault item payload locally using AES-256-GCM.
 *
 * @param {string} plaintext - The secret string (password/note) to encrypt.
 * @param {CryptoKey} key - The derived CryptoKey from sessionStorage.
 * @returns {Promise<{ciphertext: string, iv: string}>} The encrypted result.
 */
async function encryptVaultPayload(plaintext, key) {
    // Step 1: Generate a fresh 12-byte initialization vector (IV) for AES-GCM
    const iv = window.crypto.getRandomValues(new Uint8Array(12));

    // Step 2: Encode plaintext string into Uint8Array buffer
    const encoder = new TextEncoder();
    const encodedData = encoder.encode(plaintext);

    // Step 3: Perform AES-256-GCM encryption via native Web Crypto API
    const encryptedBuffer = await window.crypto.subtle.encrypt(
        { name: "AES-GCM", iv: iv },
        key,
        encodedData
    );

    // Step 4: Convert buffers to Base64 strings for transmission
    return {
        ciphertext: bufferToBase64(encryptedBuffer),
        iv: bufferToBase64(iv)
    };
}
```

---

## 4. Strict Type Hints & Return Types

* **PHP:** All functions MUST specify explicit parameter types and return types.
* **JavaScript:** Use JSDoc annotations for all functions and ensure fail-safe error handling using `try/catch`.

---

## 5. Summary Checklist for AI Agents

- [ ] 4-space indentation throughout the file.
- [ ] Every function has a full `/** ... */` DocBlock with `@param` and `@return`.
- [ ] Return types are explicitly declared on functions.
- [ ] Cryptographic methods contain inline comments explaining security steps.
- [ ] No hardcoded keys, secrets, or raw unhashed passwords in comments or code.
