/**
 * Thin wrapper around the WebAuthn (navigator.credentials) API.
 *
 * Only native browser APIs are used. The server exchanges raw base64url
 * payloads; this module translates between the browser's ArrayBuffer
 * types and those base64url strings.
 */

/**
 * Encode bytes (Uint8Array / ArrayBuffer) as unpadded base64url.
 *
 * @param {ArrayBuffer|Uint8Array} buffer - The bytes to encode.
 * @returns {string} The base64url string.
 */
export function toBase64Url(buffer) {
    const bytes = buffer instanceof Uint8Array ? buffer : new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.length; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

/**
 * Decode an unpadded base64url string into a Uint8Array.
 *
 * @param {string} base64Url - The base64url string.
 * @returns {Uint8Array} The decoded bytes.
 */
export function fromBase64Url(base64Url) {
    const padded = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    const padding = padded.length % 4 === 0 ? '' : '='.repeat(4 - (padded.length % 4));
    const binary = atob(padded + padding);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
}

/**
 * Strip ArrayBuffer-backed structures into plain serializable objects.
 *
 * WebAuthn PublicKeyCredential fields are ArrayBuffers; the server
 * requires base64url strings instead.
 *
 * @param {PublicKeyCredential} credential - The browser credential.
 * @returns {{raw_id: string, client_data_json: string, attestation_object: string}|{raw_id: string, client_data_json: string, auth_data: string, signature: string}} The serialized payload.
 */
function serializeCredential(credential) {
    const base = {
        raw_id: toBase64Url(credential.rawId),
        client_data_json: toBase64Url(credential.response.clientDataJSON),
    };

    if (credential.response.attestationObject !== undefined) {
        return {
            ...base,
            attestation_object: toBase64Url(credential.response.attestationObject),
        };
    }

    return {
        ...base,
        auth_data: toBase64Url(credential.response.authenticatorData),
        signature: toBase64Url(credential.response.signature),
    };
}

/**
 * Convert base64url strings inside server options to the byte types the
 * browser mandates (ArrayBuffer/ArrayBufferView).
 *
 * The WebAuthn spec requires `challenge`, `user.id`, and every
 * allow/exclude credential `id` to be binary, while the server
 * serializes them as base64url strings for transport. The options are
 * deep-cloned first so the original JSON stays untouched.
 *
 * Both shapes are accepted: the full options object carrying a
 * `publicKey` wrapper (as returned by the server), or the inner
 * `publicKey` object on its own. Both are normalized so the binary
 * fields under `publicKey` are decoded.
 *
 * @param {Object} options - The PublicKeyCredential options from the server.
 * @returns {Object} The same structure with binary fields converted.
 */
function toBinaryOptions(options) {
    const converted = JSON.parse(JSON.stringify(options));

    // WebAuthn options wrap everything under `publicKey`; some callers
    // pass that inner object directly, so resolve whichever is present.
    const publicKey = converted.publicKey ?? converted;

    if (typeof publicKey.challenge === 'string') {
        publicKey.challenge = fromBase64Url(publicKey.challenge);
    }

    if (publicKey.user !== undefined && typeof publicKey.user.id === 'string') {
        publicKey.user.id = fromBase64Url(publicKey.user.id);
    }

    for (const listName of ['allowCredentials', 'excludeCredentials']) {
        if (Array.isArray(publicKey[listName])) {
            publicKey[listName] = publicKey[listName].map((credential) => (
                typeof credential.id === 'string' ? { ...credential, id: fromBase64Url(credential.id) } : credential
            ));
        }
    }

    return converted;
}

/**
 * Resolve the normalized `publicKey` sub-object from converted options,
 * regardless of whether the caller passed a `.publicKey` wrapper.
 *
 * @param {Object} binaryOptions - The converted options from `toBinaryOptions`.
 * @returns {Object} The `publicKey` object to hand to the browser API.
 */
function unwrapPublicKey(binaryOptions) {
    return binaryOptions.publicKey ?? binaryOptions;
}

/**
 * Verify the WebAuthn context requirements before any credential call.
 *
 * The browser refuses WebAuthn unless the page is a secure context
 * (HTTPS, or http://localhost for development) AND the relying party ID
 * in the server-issued options exactly matches the host the page is
 * served from. This guard turns those silent/misleading SecurityError
 * failures into an actionable message.
 *
 * @param {string|null} rpId - The relying party ID expected by the server.
 * @returns {void}
 * @throws {DOMException} When the context or RP ID does not match.
 */
function assertWebauthnContext(rpId) {
    if (!window.isSecureContext) {
        throw new DOMException(
            'WebAuthn requires a secure context. Open the site via HTTPS, or use http://localhost for local development.',
            'SecurityError',
        );
    }

    if (rpId !== null && rpId !== window.location.hostname) {
        throw new DOMException(
            `WebAuthn relying party mismatch: the server expects "${rpId}" but this page is served from "${window.location.hostname}". Open the site at http://${rpId} (or align APP_URL with your actual domain) and retry.`,
            'SecurityError',
        );
    }
}

/**
 * Create a new WebAuthn credential (registration).
 *
 * @param {Object} publicKeyOptions - The server's PublicKeyCredentialCreationOptions.
 * @returns {Promise<Object>} The serialized registration response.
 * @throws {Error} When registration is aborted or not supported.
 */
export async function createWebauthnCredential(publicKeyOptions) {
    const rpId = publicKeyOptions?.publicKey?.rp?.id ?? null;
    assertWebauthnContext(rpId);
    const binaryOptions = toBinaryOptions(publicKeyOptions);
    const credential = await navigator.credentials.create({ publicKey: unwrapPublicKey(binaryOptions) });
    return serializeCredential(credential);
}

/**
 * Get a WebAuthn assertion (login / unlock).
 *
 * @param {Object} publicKeyOptions - The server's PublicKeyCredentialRequestOptions.
 * @returns {Promise<Object>} The serialized assertion response.
 * @throws {Error} When the assertion is aborted or not supported.
 */
export async function getWebauthnAssertion(publicKeyOptions) {
    const rpId = publicKeyOptions?.publicKey?.rpId ?? publicKeyOptions?.rpId ?? null;
    assertWebauthnContext(rpId);
    const binaryOptions = toBinaryOptions(publicKeyOptions);
    const credential = await navigator.credentials.get({ publicKey: unwrapPublicKey(binaryOptions) });
    return serializeCredential(credential);
}

/**
 * Map WebAuthn error conditions to friendly messages.
 *
 * @param {Error} error - The caught error.
 * @returns {string} A human-readable message.
 */
export function webauthnErrorMessage(error) {
    if (error && error.name === 'NotAllowedError') {
        return 'Request cancelled or not allowed by the authenticator.';
    }
    if (error && error.name === 'NotSupportedError') {
        return 'This browser does not support WebAuthn.';
    }
    if (error && error.name === 'SecurityError') {
        return 'WebAuthn was refused by the browser: check the relying-party hint above, or use HTTPS / http://localhost.';
    }
    return error instanceof Error ? error.message : 'WebAuthn operation failed.';
}
