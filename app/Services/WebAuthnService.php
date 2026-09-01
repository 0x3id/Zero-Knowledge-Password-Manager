<?php

namespace App\Services;

use App\Models\User;
use App\Models\WebauthnCredential;
use Illuminate\Support\Facades\Config;
use RuntimeException;

/**
 * Server-side WebAuthn (FIDO2) verifier for the zero-knowledge vault.
 *
 * Implements the WebAuthn Level 2 verification steps natively with PHP's
 * OpenSSL bindings (no external packages, mirroring the project's
 * zero-external-crypto-dependency rule):
 *
 *  - Registration: verify clientData (type/challenge/origin), the
 *    rpIdHash, the user-presence flag, and parse the attested credential
 *    data (AAGUID, credential ID, COSE ECDSA P-256 key) from the CBOR
 *    attestation object.
 *  - Authentication: verify clientData + authData + a raw ECDSA
 *    P-256 signature (r || s) over authenticatorData || SHA-256(clientData).
 *
 * Supported algorithm: ES256 (COSE alg -7, ECDSA over P-256), which is
 * the universally supported algorithm for both platform and roaming
 * authenticators. Attestation statements are NOT verified (attestation
 * 'none' at registration); this is acceptable for account-bound
 * credentials and keeps the MVP free of vendor CA handling.
 *
 * The relying party ID and origin are derived from `config('app.url')`.
 * WebAuthn only works in secure contexts (HTTPS), with http://localhost
 * being exempt for local development.
 */
class WebAuthnService
{
    /** COSE algorithm identifier for ES256 (ECDSA P-256 SHA-256). */
    public const ALG_ES256 = -7;

    /** User-presence flag bit in the authenticator data flags byte. */
    public const FLAG_UP = 0x01;

    /** User-verification flag bit in the authenticator data flags byte. */
    public const FLAG_UV = 0x04;

    /** Attested-credential-data-present flag bit in the flags byte. */
    public const FLAG_AT = 0x40;

    /** Validity window for any issued challenge. */
    public const CHALLENGE_TTL_MINUTES = 5;

    /** Session key under which the registration challenge is stored. */
    public const SESSION_REGISTER = 'webauthn.registration_challenge';

    /** Session key under which the login challenge is stored. */
    public const SESSION_LOGIN = 'webauthn.login_challenge';

    /**
     * Generate a cryptographically secure 32-byte challenge as base64url.
     *
     * @return string The raw challenge encoded as base64url.
     */
    public function generateChallenge(): string
    {
        return $this->base64UrlEncode(random_bytes(32));
    }

    /**
     * Build the Relying Party ID used in all WebAuthn operations.
     *
     * @return string The host part of the configured application URL.
     */
    public function relyingPartyId(): string
    {
        return (string) parse_url((string) Config::get('app.url'), PHP_URL_HOST);
    }

    /**
     * Build the exact origin string the client must report.
     *
     * WebAuthn origins are scheme + host + port. The port is omitted
     * when it is the scheme's default. http://localhost is exempt from
     * the secure-context requirement and therefore allowed explicitly.
     *
     * @return string The expected origin, e.g. "https://example.com".
     */
    public function origin(): string
    {
        $parts = parse_url((string) Config::get('app.url'));
        $scheme = (string) ($parts['scheme'] ?? 'https');
        $host = (string) ($parts['host'] ?? 'localhost');
        $port = $parts['port'] ?? null;

        if ($port !== null && ! (($scheme === 'https' && $port === 443) || ($scheme === 'http' && $port === 80))) {
            return $scheme.'://'.$host.':'.$port;
        }

        return $scheme.'://'.$host;
    }

    /**
     * Create the `PublicKeyCredentialCreationOptions` payload.
     *
     * @param  User  $user  The user registering the credential.
     * @return array<string, mixed> The publicKey options for navigator.credentials.create().
     */
    public function createRegistrationOptions(User $user): array
    {
        $challenge = $this->generateChallenge();

        $existing = $user->webauthnCredentials()
            ->pluck('credential_id')
            ->map(fn (string $id): array => ['type' => 'public-key', 'id' => $id])
            ->all();

        // The challenge is only ever stored server-side in the session.
        session([self::SESSION_REGISTER => [
            'challenge' => $challenge,
            'expires_at' => now()->addMinutes(self::CHALLENGE_TTL_MINUTES)->getTimestamp(),
            'user_id' => $user->id,
        ]]);

        return [
            'publicKey' => [
                'rp' => [
                    'name' => (string) Config::get('app.name', 'Password Manager'),
                    'id' => $this->relyingPartyId(),
                ],
                'user' => [
                    'id' => $this->base64UrlEncode(substr(hash('sha256', (string) $user->email, true), 0, 16)),
                    'name' => $user->email,
                    'displayName' => $user->email,
                ],
                'challenge' => $challenge,
                'pubKeyCredParams' => [
                    ['type' => 'public-key', 'alg' => self::ALG_ES256],
                ],
                'timeout' => 60000,
                'excludeCredentials' => $existing,
                'authenticatorSelection' => [
                    'userVerification' => 'preferred',
                ],
                'attestation' => 'none',
            ],
        ];
    }

    /**
     * Verify a registration response and extract the credential material.
     *
     * Throws RuntimeException on ANY verification failure — no partial
     * state is persisted and the credential must not be stored.
     *
     * @param  User  $user  The user the credential is registered to.
     * @param  string  $clientDataJsonBase64Url  The clientDataJSON (base64url).
     * @param  string  $attestationObjectBase64Url  The CBOR attestation object (base64url).
     * @param  string  $rawIdBase64Url  The credential's raw ID (base64url).
     * @return array{credential_id: string, public_key: string, counter: int} The data to persist.
     *
     * @throws RuntimeException When any step of the verification fails.
     */
    public function verifyRegistration(User $user, string $clientDataJsonBase64Url, string $attestationObjectBase64Url, string $rawIdBase64Url): array
    {
        $challenge = $this->consumeChallenge(self::SESSION_REGISTER, $user->id);
        $clientData = $this->parseClientData($clientDataJsonBase64Url, 'webauthn.create', $challenge);

        $attestationObject = $this->base64UrlDecode($attestationObjectBase64Url);
        $attestation = $this->cborDecode($attestationObject);
        $authData = $attestation['authData'] ?? null;

        if (! is_string($authData)) {
            throw new RuntimeException('Invalid attestation object: missing authData.');
        }

        $parsed = $this->parseAuthData($authData);

        $this->assertUserPresence($parsed['flags']);

        if ($rawIdBase64Url !== $this->base64UrlEncode($parsed['credential_id'])) {
            throw new RuntimeException('Credential ID mismatch.');
        }

        if (isset($parsed['public_key']) === false) {
            throw new RuntimeException('Attested credential data missing from authData.');
        }

        return [
            'credential_id' => $rawIdBase64Url,
            'public_key' => 'ES256:'.base64_encode($parsed['public_key']),
            'counter' => $parsed['counter'],
        ];
    }

    /**
     * Create the `PublicKeyCredentialRequestOptions` payload for login.
     *
     * @param  User  $user  The user attempting to authenticate.
     * @return array<string, mixed> The publicKey options for navigator.credentials.get().
     */
    public function createAssertionOptions(User $user): array
    {
        $challenge = $this->generateChallenge();

        $allowCredentials = $user->webauthnCredentials()
            ->pluck('credential_id')
            ->map(fn (string $id): array => ['type' => 'public-key', 'id' => $id])
            ->all();

        session([self::SESSION_LOGIN => [
            'challenge' => $challenge,
            'expires_at' => now()->addMinutes(self::CHALLENGE_TTL_MINUTES)->getTimestamp(),
            'user_id' => $user->id,
        ]]);

        return [
            'publicKey' => [
                'challenge' => $challenge,
                'rpId' => $this->relyingPartyId(),
                'timeout' => 60000,
                'allowCredentials' => $allowCredentials,
                'userVerification' => 'preferred',
            ],
        ];
    }

    /**
     * Verify a login assertion and return the authenticated credential.
     *
     * The credential's signature counter MUST NOT decrease, which detects
     * cloned/imported authenticators.
     *
     * @param  User  $user  The user claiming this assertion.
     * @param  string  $authDataBase64Url  The authenticator data (base64url).
     * @param  string  $clientDataJsonBase64Url  The clientDataJSON (base64url).
     * @param  string  $signatureBase64Url  The raw ECDSA signature r||s (base64url).
     * @param  string  $rawIdBase64Url  The credential ID used (base64url).
     * @return WebauthnCredential The verified credential, counter updated.
     *
     * @throws RuntimeException When any step of the verification fails.
     */
    public function verifyAssertion(User $user, string $authDataBase64Url, string $clientDataJsonBase64Url, string $signatureBase64Url, string $rawIdBase64Url): WebauthnCredential
    {
        $challenge = $this->consumeChallenge(self::SESSION_LOGIN, $user->id);
        $clientData = $this->parseClientData($clientDataJsonBase64Url, 'webauthn.get', $challenge);

        $authData = $this->base64UrlDecode($authDataBase64Url);

        if (strlen($authData) < 37) {
            throw new RuntimeException('Authenticator data too short.');
        }

        $parsed = $this->parseAuthData($authData);
        $this->assertUserPresence($parsed['flags']);

        $credential = $user->webauthnCredentials()
            ->where('credential_id', $rawIdBase64Url)
            ->first();

        if ($credential === null) {
            throw new RuntimeException('Credential not found for this user.');
        }

        $newCounter = $parsed['counter'];
        if ($credential->counter > 0 && $newCounter < $credential->counter) {
            // A decreased counter implies the authenticator was cloned.
            throw new RuntimeException('Signature counter decreased; authenticator may be cloned.');
        }

        $publicKey = $this->decodeStoredPublicKey((string) $credential->public_key);

        $clientDataHash = hash('sha256', $this->base64UrlDecode($clientDataJsonBase64Url), true);
        $signature = $this->base64UrlDecode($signatureBase64Url);
        $signatureDer = $this->signatureToDer($signature);

        if (openssl_verify($authData.$clientDataHash, $signatureDer, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
            throw new RuntimeException('Invalid assertion signature.');
        }

        $credential->update(['counter' => $newCounter]);

        return $credential;
    }

    /**
     * Delete the session challenge for a user (defense in depth).
     *
     * @param  string  $sessionKey  One of the SESSION_* constants.
     * @param  int  $userId  The user the challenge belongs to.
     */
    public function forgetChallenge(string $sessionKey, int $userId): void
    {
        $stored = session($sessionKey);

        if (is_array($stored) && ($stored['user_id'] ?? null) === $userId) {
            session()->forget($sessionKey);
        }
    }

    /**
     * Parse and validate the clientDataJSON payload.
     *
     * @param  string  $clientDataJsonBase64Url  The clientDataJSON (base64url).
     * @param  string  $expectedType  The expected `type` field value.
     * @param  string  $expectedChallenge  The challenge issued by the server.
     * @return array{type: string, challenge: string, origin: string} The validated payload.
     *
     * @throws RuntimeException When the client data fails any check.
     */
    private function parseClientData(string $clientDataJsonBase64Url, string $expectedType, string $expectedChallenge): array
    {
        $json = $this->base64UrlDecode($clientDataJsonBase64Url);
        $data = json_decode($json, true);

        if (! is_array($data)) {
            throw new RuntimeException('clientDataJSON is not valid JSON.');
        }

        if (($data['type'] ?? null) !== $expectedType) {
            throw new RuntimeException('Unexpected client data type.');
        }

        if (! isset($data['challenge']) || ! hash_equals($expectedChallenge, (string) $data['challenge'])) {
            throw new RuntimeException('Challenge mismatch.');
        }

        if (! isset($data['origin']) || ! hash_equals($this->origin(), (string) $data['origin'])) {
            throw new RuntimeException('Origin mismatch.');
        }

        return $data;
    }

    /**
     * Fetch the stored challenge, enforce expiry, and consume it.
     *
     * @param  string  $sessionKey  One of the SESSION_* constants.
     * @param  int  $userId  The user the challenge must belong to.
     * @return string The challenge string.
     *
     * @throws RuntimeException When the challenge is missing or expired.
     */
    private function consumeChallenge(string $sessionKey, int $userId): string
    {
        $stored = session($sessionKey);

        if (! is_array($stored) || ($stored['user_id'] ?? null) !== $userId) {
            throw new RuntimeException('No pending WebAuthn challenge for this session.');
        }

        if (($stored['expires_at'] ?? 0) < now()->getTimestamp()) {
            session()->forget($sessionKey);
            throw new RuntimeException('WebAuthn challenge expired; please try again.');
        }

        session()->forget($sessionKey);

        return (string) $stored['challenge'];
    }

    /**
     * Parse the authenticator data structure.
     *
     * Format: rpIdHash(32) || flags(1) || signCount(4) || attested data?
     *
     * @param  string  $authData  The raw authenticator data bytes.
     * @return array{flags: int, counter: int, credential_id: string|null, public_key: string|null} Parsed fields.
     *
     * @throws RuntimeException When the structure is malformed.
     */
    private function parseAuthData(string $authData): array
    {
        $rpIdHash = substr($authData, 0, 32);
        $expectedHash = hash('sha256', $this->relyingPartyId(), true);

        if (! hash_equals($expectedHash, $rpIdHash)) {
            throw new RuntimeException('rpIdHash mismatch.');
        }

        $flags = ord($authData[32]);
        $counter = unpack('N', substr($authData, 33, 4))[1];
        $offset = 37;

        $result = ['flags' => $flags, 'counter' => $counter, 'credential_id' => null, 'public_key' => null];

        if (($flags & self::FLAG_AT) !== 0) {
            if (strlen($authData) < $offset + 18) {
                throw new RuntimeException('Attested credential data truncated.');
            }

            $offset += 16; // AAGUID
            $credentialIdLength = unpack('n', substr($authData, $offset, 2))[1];
            $offset += 2;

            if (strlen($authData) < $offset + $credentialIdLength) {
                throw new RuntimeException('Credential ID truncated.');
            }

            $result['credential_id'] = substr($authData, $offset, $credentialIdLength);
            $offset += $credentialIdLength;

            $coseKey = substr($authData, $offset);
            $result['public_key'] = $this->parseCoseEcKey($coseKey);
        }

        return $result;
    }

    /**
     * Parse a COSE EC2 (ES256) public key and extract the raw P-256 point.
     *
     * @param  string  $coseKey  The COSE_Key CBOR bytes.
     * @return string The raw 64-byte uncompressed point x||y.
     *
     * @throws RuntimeException When the key is not ES256 / P-256.
     */
    private function parseCoseEcKey(string $coseKey): string
    {
        $map = $this->cborDecode($coseKey);

        if (! is_array($map) || ($map[1] ?? null) !== 2) {
            throw new RuntimeException('COSE key type must be EC2.');
        }

        if (($map[3] ?? null) !== self::ALG_ES256) {
            throw new RuntimeException('Unsupported COSE algorithm; only ES256 (-7) is supported.');
        }

        if (($map[-1] ?? null) !== 1) {
            throw new RuntimeException('COSE curve must be P-256 (1).');
        }

        $x = $map[-2] ?? null;
        $y = $map[-3] ?? null;

        if (! is_string($x) || ! is_string($y) || strlen($x) !== 32 || strlen($y) !== 32) {
            throw new RuntimeException('COSE key coordinates must each be 32 bytes.');
        }

        return $x.$y;
    }

    /**
     * Decode a stored public key string back into a PEM SubjectPublicKeyInfo.
     *
     * @param  string  $stored  The stored value, prefixed with the algorithm tag.
     * @return string The PEM-encoded SPKI suitable for openssl_verify().
     *
     * @throws RuntimeException When the stored key is malformed.
     */
    private function decodeStoredPublicKey(string $stored): string
    {
        if (! str_starts_with($stored, 'ES256:')) {
            throw new RuntimeException('Unsupported stored public key format.');
        }

        $raw = base64_decode(substr($stored, 6), true);

        if ($raw === false || strlen($raw) !== 64) {
            throw new RuntimeException('Stored public key is malformed.');
        }

        return $this->ecPointToPem($raw);
    }

    /**
     * Encode a raw P-256 point into a DER SubjectPublicKeyInfo PEM block.
     *
     * @param  string  $point  The 64-byte uncompressed point x||y.
     * @return string The PEM string.
     */
    private function ecPointToPem(string $point): string
    {
        // id-ecPublicKey (1.2.840.10045.2.1) and prime256v1 (1.2.840.10045.3.1.7).
        $algorithm = "\x30\x0d\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
        $bitString = "\x03".chr(65)."\x00".$point;

        $spki = "\x30".$this->derLength(strlen($algorithm) + strlen($bitString))
            .$algorithm.$bitString;

        $pem = "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(base64_encode($spki), 64, "\n")
            ."-----END PUBLIC KEY-----\n";

        return $pem;
    }

    /**
     * Convert a raw ECDSA signature (r||s, 32 bytes each) into DER encoding.
     *
     * @param  string  $signature  The 64-byte raw signature.
     * @return string The DER-encoded ECDSA-Sig-Value.
     *
     * @throws RuntimeException When the signature length is not 64 bytes.
     */
    private function signatureToDer(string $signature): string
    {
        if (strlen($signature) !== 64) {
            throw new RuntimeException('Raw ECDSA signature must be 64 bytes.');
        }

        $r = $this->stripLeadingZeros(substr($signature, 0, 32));
        $s = $this->stripLeadingZeros(substr($signature, 32, 32));

        $r = "\x02".$this->derLength(strlen($r)).$r;
        $s = "\x02".$this->derLength(strlen($s)).$s;

        return "\x30".$this->derLength(strlen($r) + strlen($s)).$r.$s;
    }

    /**
     * Strip leading zero bytes and prepend a sign byte when the high bit is set.
     *
     * @param  string  $integer  The raw integer bytes.
     * @return string The normalized INTEGER content.
     */
    private function stripLeadingZeros(string $integer): string
    {
        $integer = ltrim($integer, "\x00");

        if ($integer === '') {
            return "\x00";
        }

        if ((ord($integer[0]) & 0x80) !== 0) {
            $integer = "\x00".$integer;
        }

        return $integer;
    }

    /**
     * Encode a length into DER length bytes.
     *
     * @param  int  $length  The payload length.
     * @return string The DER length encoding.
     */
    private function derLength(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xFF).$bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)).$bytes;
    }

    /**
     * Assert that the user-presence flag (UP) was set.
     *
     * @param  int  $flags  The authenticator data flags byte.
     *
     * @throws RuntimeException When UP is not set.
     */
    private function assertUserPresence(int $flags): void
    {
        if (($flags & self::FLAG_UP) === 0) {
            throw new RuntimeException('User presence (UP) not confirmed by authenticator.');
        }
    }

    /**
     * Decode a minimal, definite-length subset of CBOR.
     *
     * Supports the types required by WebAuthn attestation objects and
     * COSE keys: unsigned/negative integers, byte strings, UTF-8 strings,
     * arrays, maps, tags (transparently unwrapped), and the simple values
     * true/false/null.
     *
     * @param  string  $data  The CBOR payload.
     * @param  int  $offset  The read offset (updated by reference).
     * @return mixed The decoded PHP value.
     *
     * @throws RuntimeException When the payload uses unsupported CBOR.
     */
    private function cborDecode(string $data, int &$offset = 0): mixed
    {
        if ($offset >= strlen($data)) {
            throw new RuntimeException('Unexpected end of CBOR data.');
        }

        $initial = ord($data[$offset++]);
        $major = $initial >> 5;
        $additional = $initial & 0x1F;
        $length = $this->cborLength($additional, $data, $offset);

        return match ($major) {
            0 => $length,
            1 => -1 - $length,
            2 => $this->cborBytes($data, $offset, $length),
            3 => $this->cborBytes($data, $offset, $length),
            4 => $this->cborArray($data, $offset, $length),
            5 => $this->cborMap($data, $offset, $length),
            6 => $this->cborDecode($data, $offset),
            7 => $this->cborSimple($additional),
            default => throw new RuntimeException('Unsupported CBOR major type.'),
        };
    }

    /**
     * Resolve the length value for a CBOR header.
     *
     * @param  int  $additional  The low 5 bits of the initial byte.
     * @param  string  $data  The full payload.
     * @param  int  $offset  The read offset (updated by reference).
     * @return int The item length.
     */
    private function cborLength(int $additional, string $data, int &$offset): int
    {
        if ($additional < 24) {
            return $additional;
        }

        $bytes = match ($additional) {
            24 => 1,
            25 => 2,
            26 => 4,
            27 => 8,
            default => throw new RuntimeException('Indefinite-length CBOR is not supported.'),
        };

        if ($offset + $bytes > strlen($data)) {
            throw new RuntimeException('Truncated CBOR length field.');
        }

        $value = 0;
        for ($i = 0; $i < $bytes; $i++) {
            $value = ($value << 8) | ord($data[$offset++]);
        }

        return $value;
    }

    /**
     * Extract a byte/string value of the given length from the payload.
     *
     * @param  string  $data  The full payload.
     * @param  int  $offset  The read offset (updated by reference).
     * @param  int  $length  The number of bytes to read.
     * @return string The extracted bytes.
     */
    private function cborBytes(string $data, int &$offset, int $length): string
    {
        if ($offset + $length > strlen($data)) {
            throw new RuntimeException('Truncated CBOR string.');
        }

        $bytes = substr($data, $offset, $length);
        $offset += $length;

        return $bytes;
    }

    /**
     * Decode a CBOR array into a PHP list.
     *
     * @param  string  $data  The full payload.
     * @param  int  $offset  The read offset (updated by reference).
     * @param  int  $length  The number of elements.
     * @return array<int, mixed> The decoded array.
     */
    private function cborArray(string $data, int &$offset, int $length): array
    {
        $items = [];
        for ($i = 0; $i < $length; $i++) {
            $items[] = $this->cborDecode($data, $offset);
        }

        return $items;
    }

    /**
     * Decode a CBOR map into a PHP associative array.
     *
     * @param  string  $data  The full payload.
     * @param  int  $offset  The read offset (updated by reference).
     * @param  int  $length  The number of key/value pairs.
     * @return array<int|string, mixed> The decoded map.
     */
    private function cborMap(string $data, int &$offset, int $length): array
    {
        $map = [];
        for ($i = 0; $i < $length; $i++) {
            $key = $this->cborDecode($data, $offset);
            $map[$key] = $this->cborDecode($data, $offset);
        }

        return $map;
    }

    /**
     * Resolve CBOR simple values.
     *
     * @param  int  $additional  The low 5 bits of the initial byte.
     * @return bool|null The resolved simple value.
     *
     * @throws RuntimeException When the simple value is unsupported.
     */
    private function cborSimple(int $additional): ?bool
    {
        return match ($additional) {
            20 => false,
            21 => true,
            22 => null,
            default => throw new RuntimeException('Unsupported CBOR simple value.'),
        };
    }

    /**
     * Encode bytes as base64url without padding.
     *
     * @param  string  $bytes  The raw bytes.
     * @return string The base64url string.
     */
    private function base64UrlEncode(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /**
     * Decode a base64url string back into raw bytes.
     *
     * @param  string  $base64Url  The base64url string.
     * @return string The raw bytes.
     *
     * @throws RuntimeException When the string is not valid base64url.
     */
    private function base64UrlDecode(string $base64Url): string
    {
        $decoded = base64_decode(strtr($base64Url, '-_', '+/'), true);

        if ($decoded === false) {
            throw new RuntimeException('Invalid base64url payload.');
        }

        return $decoded;
    }
}
