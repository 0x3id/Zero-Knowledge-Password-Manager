/**
 * Client-side cryptography for the zero-knowledge vault.
 *
 * All cryptographic operations use only the native Web Crypto API
 * (window.crypto.subtle) — no external libraries, per the architecture's
 * zero-external-JS-crypto-dependency rule.
 *
 * Key hierarchy (per ARCHITECTURE.md section 4):
 *   Master Password + kdf_salt --PBKDF2-SHA256, 600k--> Master Key
 *       +--HKDF, info="zkpm:encryption"-----> Encryption Key (in memory only)
 *       +--HKDF, info="zkpm:authentication"-> Auth Hash Input (sent to server)
 *
 * IMPORTANT: the raw Encryption Key bytes are kept in a module-scoped
 * variable and are NEVER persisted outside of encrypted blobs (IndexedDB
 * unlock blob / recovery blob). The AES-GCM CryptoKey derived from them
 * is non-extractable, so even XSS cannot read out key material — it can
 * only be used for encryption/decryption operations.
 */

export const PBKDF2_ITERATIONS = 600_000;

/** Fixed plaintext used to verify a derived key against the vault canary. */
export const CANARY_PLAINTEXT = 'PasswordManager::vault-canary-v1::ok';

/** HKDF info strings — separate contexts keep the two derived keys independent. */
const HKDF_INFO_ENCRYPTION = 'zkpm:encryption';
const HKDF_INFO_AUTHENTICATION = 'zkpm:authentication';

/** In-memory holder for the raw encryption key bytes (never exported). */
let encryptionKeyBytes = null;

/** In-memory AES-GCM CryptoKey used for vault item crypto (non-extractable). */
let encryptionCryptoKey = null;

/**
 * Encode a byte array as base64 (standard, padded).
 *
 * @param {Uint8Array} bytes - The bytes to encode.
 * @returns {string} The base64 string.
 */
export function bytesToBase64(bytes) {
    let binary = '';
    for (let i = 0; i < bytes.length; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary);
}

/**
 * Decode a base64 string (standard, padded) into bytes.
 *
 * @param {string} base64 - The base64 string.
 * @returns {Uint8Array} The decoded bytes.
 * @throws {Error} When the input is not valid base64.
 */
export function base64ToBytes(base64) {
    const binary = atob(base64);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
}

/**
 * Generate cryptographically secure random bytes.
 *
 * @param {number} length - The number of bytes to generate.
 * @returns {Uint8Array} The random bytes.
 */
export function randomBytes(length) {
    const bytes = new Uint8Array(length);
    window.crypto.getRandomValues(bytes);
    return bytes;
}

/**
 * Generate a fresh random KDF salt (16 bytes, base64).
 *
 * @returns {string} The base64-encoded salt.
 */
export function generateKdfSalt() {
    return bytesToBase64(randomBytes(16));
}

/**
 * Generate a new 32-character Recovery Key (32 random bytes, base64).
 *
 * @returns {string} The recovery key.
 */
export function generateRecoveryKey() {
    return bytesToBase64(randomBytes(32));
}

/**
 * Derive the Master Key from the master password and KDF salt.
 *
 * Uses PBKDF2-SHA256 with 600,000 iterations (OWASP 2023 recommended
 * minimum for PBKDF2). The Master Key itself is not persisted; it exists
 * only long enough to derive the two purpose-separated keys.
 *
 * @param {string} masterPassword - The raw user master password.
 * @param {string} kdfSaltBase64 - The per-user salt (base64).
 * @returns {Promise<Uint8Array>} The 32-byte Master Key.
 */
export async function deriveMasterKey(masterPassword, kdfSaltBase64) {
    const passwordKey = await window.crypto.subtle.importKey(
        'raw',
        new TextEncoder().encode(masterPassword),
        'PBKDF2',
        false,
        ['deriveBits'],
    );

    const masterKey = await window.crypto.subtle.deriveBits(
        {
            name: 'PBKDF2',
            hash: 'SHA-256',
            salt: base64ToBytes(kdfSaltBase64),
            iterations: PBKDF2_ITERATIONS,
        },
        passwordKey,
        256,
    );

    return new Uint8Array(masterKey);
}

/**
 * Derive a purpose-specific key from the Master Key via HKDF.
 *
 * The `info` string splits the derived keys into separate contexts, so
 * possession of the auth hash input gives no shortcut to the encryption
 * key and vice versa.
 *
 * @param {Uint8Array} masterKey - The 32-byte Master Key.
 * @param {string} info - The HKDF context string.
 * @returns {Promise<Uint8Array>} The 32-byte derived key material.
 */
async function hkdfDeriveBits(masterKey, info) {
    const baseKey = await window.crypto.subtle.importKey(
        'raw',
        masterKey,
        'HKDF',
        false,
        ['deriveBits'],
    );

    const bits = await window.crypto.subtle.deriveBits(
        {
            name: 'HKDF',
            hash: 'SHA-256',
            salt: new Uint8Array(0),
            info: new TextEncoder().encode(info),
        },
        baseKey,
        256,
    );

    return new Uint8Array(bits);
}

/**
 * Derive the Encryption Key material for the vault.
 *
 * NEVER send the output to the server.
 *
 * @param {Uint8Array} masterKey - The 32-byte Master Key.
 * @returns {Promise<Uint8Array>} The 32-byte Encryption Key material.
 */
export async function deriveEncryptionKey(masterKey) {
    return hkdfDeriveBits(masterKey, HKDF_INFO_ENCRYPTION);
}

/**
 * Derive the Auth Hash Input to send to the server.
 *
 * This value IS transmitted (it is only ever stored as a bcrypt hash
 * server-side) and is useless without the corresponding encryption key.
 *
 * @param {Uint8Array} masterKey - The 32-byte Master Key.
 * @returns {Promise<string>} The base64-encoded auth hash input.
 */
export async function deriveAuthHashInput(masterKey) {
    const bits = await hkdfDeriveBits(masterKey, HKDF_INFO_AUTHENTICATION);
    return bytesToBase64(bits);
}

/**
 * Import raw AES-GCM key material as a NON-EXTRACTABLE CryptoKey.
 *
 * Non-extractability is deliberate (architecture section 4.C): the key
 * can be used for crypto operations but never read back out, so an XSS
 * payload cannot export it.
 *
 * @param {Uint8Array} keyBytes - The 32-byte AES-256 key material.
 * @returns {Promise<CryptoKey>} The non-extractable AES-GCM key.
 */
export async function importAesKey(keyBytes) {
    return window.crypto.subtle.importKey('raw', keyBytes, { name: 'AES-GCM' }, false, ['encrypt', 'decrypt']);
}

/**
 * Encrypt a string with AES-256-GCM using a fresh 12-byte IV.
 *
 * @param {CryptoKey} key - The AES-GCM CryptoKey.
 * @param {string} plaintext - The plaintext to encrypt.
 * @returns {Promise<{iv: string, ciphertext: string}>} Base64 IV and ciphertext.
 */
export async function aesGcmEncrypt(key, plaintext) {
    const iv = randomBytes(12);
    const ciphertext = await window.crypto.subtle.encrypt(
        { name: 'AES-GCM', iv },
        key,
        new TextEncoder().encode(plaintext),
    );
    return { iv: bytesToBase64(iv), ciphertext: bytesToBase64(new Uint8Array(ciphertext)) };
}

/**
 * Decrypt an AES-256-GCM payload.
 *
 * @param {CryptoKey} key - The AES-GCM CryptoKey.
 * @param {string} ciphertextBase64 - The ciphertext (base64).
 * @param {string} ivBase64 - The initialization vector (base64).
 * @returns {Promise<string>} The plaintext string.
 * @throws {Error} When authentication of the ciphertext fails.
 */
export async function aesGcmDecrypt(key, ciphertextBase64, ivBase64) {
    const plaintext = await window.crypto.subtle.decrypt(
        { name: 'AES-GCM', iv: base64ToBytes(ivBase64) },
        key,
        base64ToBytes(ciphertextBase64),
    );
    return new TextDecoder().decode(plaintext);
}

/**
 * Build the AES-GCM key derived from the Recovery Key.
 *
 * @param {string} recoveryKey - The base64 Recovery Key string.
 * @returns {Promise<CryptoKey>} The AES-GCM key wrapping the encryption key.
 */
async function deriveRecoveryKey(recoveryKey) {
    const digest = await window.crypto.subtle.digest('SHA-256', new TextEncoder().encode(recoveryKey));
    return importAesKey(new Uint8Array(digest));
}

/**
 * Encrypt the Encryption Key under the Recovery Key.
 *
 * @param {string} recoveryKey - The base64 Recovery Key string.
 * @param {Uint8Array} encryptionKeyBytes - The 32-byte Encryption Key.
 * @returns {Promise<string>} The JSON recovery blob {iv, ciphertext} (base64 fields).
 */
export async function createRecoveryBlob(recoveryKey, encryptionKeyBytes) {
    const key = await deriveRecoveryKey(recoveryKey);
    const { iv, ciphertext } = await aesGcmEncrypt(key, bytesToBase64(encryptionKeyBytes));
    return JSON.stringify({ iv, ciphertext });
}

/**
 * Recover the Encryption Key from the recovery blob.
 *
 * Executed entirely client-side; the Recovery Key never leaves the browser.
 *
 * @param {string} recoveryKey - The base64 Recovery Key string.
 * @param {string} recoveryBlob - The JSON recovery blob from the server.
 * @returns {Promise<Uint8Array>} The recovered 32-byte Encryption Key.
 * @throws {Error} When the recovery key does not match the blob.
 */
export async function decryptRecoveryBlob(recoveryKey, recoveryBlob) {
    const key = await deriveRecoveryKey(recoveryKey);
    const payload = JSON.parse(recoveryBlob);
    const plaintext = await aesGcmDecrypt(key, payload.ciphertext, payload.iv);
    return base64ToBytes(plaintext);
}

/**
 * Encrypt the fixed canary value with the Encryption Key.
 *
 * The ciphertext is stored server-side and later used to verify that a
 * re-derived key is correct without touching real vault data.
 *
 * @param {Uint8Array} encryptionKeyBytes - The 32-byte Encryption Key.
 * @returns {Promise<string>} The JSON canary {iv, ciphertext}.
 */
export async function createVaultCanary(encryptionKeyBytes) {
    const key = await importAesKey(encryptionKeyBytes);
    const { iv, ciphertext } = await aesGcmEncrypt(key, CANARY_PLAINTEXT);
    return JSON.stringify({ iv, ciphertext });
}

/**
 * Verify a derived key against the stored vault canary.
 *
 * @param {Uint8Array} encryptionKeyBytes - The candidate 32-byte Encryption Key.
 * @param {string} vaultCanary - The JSON canary from the server.
 * @returns {Promise<boolean>} True when the key decrypts the canary correctly.
 */
export async function verifyVaultCanary(encryptionKeyBytes, vaultCanary) {
    const key = await importAesKey(encryptionKeyBytes);
    const payload = JSON.parse(vaultCanary);

    try {
        const plaintext = await aesGcmDecrypt(key, payload.ciphertext, payload.iv);
        return plaintext === CANARY_PLAINTEXT;
    } catch (error) {
        // Authentication failure = wrong key; the UI should not distinguish.
        return false;
    }
}

/**
 * Store the in-memory encryption context after a successful unlock.
 *
 * @param {Uint8Array} keyBytes - The 32-byte Encryption Key.
 * @returns {Promise<void>}
 */
export async function unlockVault(keyBytes) {
    encryptionKeyBytes = keyBytes;
    encryptionCryptoKey = await importAesKey(keyBytes);
}

/**
 * Load the current in-memory encryption context (vault must be unlocked).
 *
 * @returns {Promise<CryptoKey|null>} The AES-GCM key, or null when locked.
 */
export async function getEncryptionKey() {
    return encryptionCryptoKey;
}

/**
 * Get the raw encryption key bytes (used only for blob creation).
 *
 * @returns {Uint8Array|null} The key bytes, or null when locked.
 */
export function getEncryptionKeyBytes() {
    return encryptionKeyBytes;
}

/**
 * Clear the in-memory key context on auto-lock.
 *
 * @returns {void}
 */
export function lockVault() {
    encryptionKeyBytes = null;
    encryptionCryptoKey = null;
}

/**
 * Encrypt a vault item payload with the in-memory key.
 *
 * @param {string} plaintext - The secret string to encrypt.
 * @returns {Promise<{iv: string, ciphertext: string}>} The encrypted payload.
 * @throws {Error} When the vault is locked.
 */
export async function encryptVaultPayload(plaintext) {
    if (encryptionCryptoKey === null) {
        throw new Error('Vault is locked.');
    }
    return aesGcmEncrypt(encryptionCryptoKey, plaintext);
}

/**
 * Decrypt a vault item payload with the in-memory key.
 *
 * @param {string} ciphertextBase64 - The ciphertext (base64).
 * @param {string} ivBase64 - The IV (base64).
 * @returns {Promise<string>} The plaintext string.
 * @throws {Error} When the vault is locked or the ciphertext fails auth.
 */
export async function decryptVaultPayload(ciphertextBase64, ivBase64) {
    if (encryptionCryptoKey === null) {
        throw new Error('Vault is locked.');
    }
    return aesGcmDecrypt(encryptionCryptoKey, ciphertextBase64, ivBase64);
}

/**
 * Persist the per-device unlock blob in IndexedDB.
 *
 * The blob holds the raw Encryption Key encrypted under a random per-device
 * unlock key. Storage is cleared on every auto-lock; presence of the blob
 * is what enables the biometric unlock path.
 *
 * @param {Uint8Array} encryptionKeyBytes - The 32-byte Encryption Key.
 * @returns {Promise<void>}
 */
export async function saveUnlockBlob(encryptionKeyBytes) {
    const unlockKeyBytes = randomBytes(32);
    const unlockKey = await importAesKey(unlockKeyBytes);
    const { iv, ciphertext } = await aesGcmEncrypt(unlockKey, bytesToBase64(encryptionKeyBytes));

    const database = await openBlobDatabase();
    await new Promise((resolve, reject) => {
        const transaction = database.transaction('unlock_blobs', 'readwrite');
        transaction.objectStore('unlock_blobs').put({ id: 'blob', iv, ciphertext });
        transaction.objectStore('unlock_blobs').put({ id: 'unlock_key', value: bytesToBase64(unlockKeyBytes) });
        transaction.oncomplete = resolve;
        transaction.onerror = () => reject(transaction.error);
    });
}

/**
 * Read and decrypt the IndexedDB unlock blob.
 *
 * @returns {Promise<Uint8Array|null>} The Encryption Key, or null when absent.
 */
export async function readUnlockBlob() {
    const database = await openBlobDatabase();
    const blobEntry = await getBlobRecord(database, 'blob');
    const unlockKeyEntry = await getBlobRecord(database, 'unlock_key');

    if (blobEntry === undefined || unlockKeyEntry === undefined) {
        return null;
    }

    const unlockKey = await importAesKey(base64ToBytes(unlockKeyEntry.value));
    const plaintext = await aesGcmDecrypt(unlockKey, blobEntry.ciphertext, blobEntry.iv);

    return base64ToBytes(plaintext);
}

/**
 * Clear the IndexedDB unlock blob (auto-lock / sign-out).
 *
 * @returns {Promise<void>}
 */
export async function clearUnlockBlob() {
    const database = await openBlobDatabase();
    await new Promise((resolve, reject) => {
        const transaction = database.transaction('unlock_blobs', 'readwrite');
        transaction.objectStore('unlock_blobs').clear();
        transaction.oncomplete = resolve;
        transaction.onerror = () => reject(transaction.error);
    });
}

/**
 * Open (and lazily create) the IndexedDB database for unlock blobs.
 *
 * @returns {Promise<IDBDatabase>} The opened database.
 */
function openBlobDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('zkpm-vault', 1);

        request.onupgradeneeded = () => {
            request.result.createObjectStore('unlock_blobs', { keyPath: 'id' });
        };
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}

/**
 * Fetch a single record from the unlock-blob object store.
 *
 * @param {IDBDatabase} database - The open database.
 * @param {string} id - The record key.
 * @returns {Promise<Object|undefined>} The record, or undefined.
 */
function getBlobRecord(database, id) {
    return new Promise((resolve, reject) => {
        const request = database.transaction('unlock_blobs', 'readonly').objectStore('unlock_blobs').get(id);
        request.onsuccess = () => resolve(request.result);
        request.onerror = () => reject(request.error);
    });
}
