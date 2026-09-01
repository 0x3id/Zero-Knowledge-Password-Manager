<?php

namespace App\Session;

use Illuminate\Session\Store;
use Illuminate\Support\Str;

/**
 * Session store that issues UUID v4 session identifiers instead of the
 * framework's default 40-character alphanumeric IDs.
 */
class UuidSessionStore extends Store
{
    /**
     * Determine whether the given session ID is a valid UUID v4.
     *
     * @param  string|null  $id  The session ID to validate.
     * @return bool True when the ID is a UUID v4.
     */
    public function isValidId($id): bool
    {
        return is_string($id)
            && preg_match(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
                $id
            ) === 1;
    }

    /**
     * Generate a fresh UUID v4 session identifier.
     *
     * @return string The new session ID.
     */
    protected function generateSessionId(): string
    {
        return Str::uuid()->toString();
    }
}