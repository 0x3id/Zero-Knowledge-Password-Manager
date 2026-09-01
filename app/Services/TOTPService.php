<?php

namespace App\Services;

use RuntimeException;

/**
 * RFC 6238 TOTP implementation used for the second authentication factor.
 *
 * Uses the standard SHA-1 algorithm, 30-second time steps, and 6-digit
 * codes, which is interoperable with all mainstream authenticator apps
 * (Google Authenticator, 1Password, Authy, etc.). The shared secret is
 * stored encrypted at rest via Eloquent's `encrypted` cast; only the
 * server needs it — the client never performs TOTP cryptography.
 */
class TOTPService
{
    /** Number of time steps allowed on each side of the current one. */
    public const WINDOW = 1;

    /** Seconds per time step (RFC 6238 default). */
    public const PERIOD = 30;

    /** Number of code digits. */
    public const DIGITS = 6;

    /**
     * Generate a new random TOTP shared secret (160-bit, base32 encoded).
     *
     * @return string The base32 secret without padding.
     */
    public function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    /**
     * Build the otpauth:// provisioning URI for authenticator apps.
     *
     * @param  string  $secret  The base32 shared secret.
     * @param  string  $email  The user's email, used as the account label.
     * @return string The otpauth URI.
     */
    public function otpauthUri(string $secret, string $email): string
    {
        $issuer = (string) config('app.name', 'Password Manager');

        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer),
            self::DIGITS,
            self::PERIOD,
        );
    }

    /**
     * Verify a 6-digit TOTP code against a secret within the time window.
     *
     * A constant-time comparison is used per code to avoid leaking
     * partial matches.
     *
     * @param  string  $secret  The base32 shared secret.
     * @param  string  $code  The 6-digit code provided by the user.
     * @return bool True when the code is valid within the window.
     */
    public function verify(string $secret, string $code, ?int $timestamp = null): bool
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $counter = (int) floor((($timestamp ?? time()) / self::PERIOD));

        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            $expected = $this->generateCode($secret, $counter + $i);
            if (hash_equals($expected, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate the TOTP code valid at a given timestamp.
     *
     * Exposed as a public convenience for tests and troubleshooting; the
     * server never needs to generate codes in production.
     *
     * @param  string  $secret  The base32 shared secret.
     * @param  int|null  $timestamp  The unix timestamp (defaults to now).
     * @return string The zero-padded 6-digit code.
     */
    public function codeAt(string $secret, ?int $timestamp = null): string
    {
        $counter = (int) floor((($timestamp ?? time()) / self::PERIOD));

        return $this->generateCode($secret, $counter);
    }

    /**
     * Generate the 6-digit code for a given time-step counter.
     *
     * @param  string  $secret  The base32 shared secret.
     * @param  int  $counter  The 8-byte time-step counter.
     * @return string The zero-padded 6-digit code.
     */
    private function generateCode(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);

        // HOTP dynamic truncation per RFC 4226.
        $hash = hash_hmac('sha1', pack('N*', 0, $counter), $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = (
            ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF)
        );

        return str_pad((string) ($binary % 10 ** self::DIGITS), self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Encode bytes as RFC 4648 base32 without padding.
     *
     * @param  string  $bytes  The raw bytes.
     * @return string The uppercase base32 string.
     */
    private function base32Encode(string $bytes): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $result = '';
        $buffer = 0;
        $bits = 0;

        for ($i = 0; $i < strlen($bytes); $i++) {
            $buffer = ($buffer << 8) | ord($bytes[$i]);
            $bits += 8;

            while ($bits >= 5) {
                $result .= $alphabet[($buffer >> ($bits - 5)) & 0x1F];
                $bits -= 5;
            }
        }

        return $result;
    }

    /**
     * Decode a base32 string back into raw bytes.
     *
     * @param  string  $base32  The base32 string (padding optional).
     * @return string The raw bytes.
     *
     * @throws RuntimeException When the string is not valid base32.
     */
    private function base32Decode(string $base32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32 = strtoupper(str_replace('=', '', $base32));
        $result = '';
        $buffer = 0;
        $bits = 0;

        for ($i = 0; $i < strlen($base32); $i++) {
            $index = strpos($alphabet, $base32[$i]);

            if ($index === false) {
                throw new RuntimeException('Invalid base32 character in TOTP secret.');
            }

            $buffer = ($buffer << 5) | $index;
            $bits += 5;

            if ($bits >= 8) {
                $result .= chr(($buffer >> ($bits - 8)) & 0xFF);
                $bits -= 8;
            }
        }

        return $result;
    }
}
