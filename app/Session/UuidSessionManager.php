<?php

namespace App\Session;

use Illuminate\Session\SessionManager;

/**
 * Session manager that builds UUID-identifying session stores.
 */
class UuidSessionManager extends SessionManager
{
    /**
     * Build the session store instance for the given handler.
     *
     * Note: the encrypted-session path is intentionally not supported;
     * this project runs with `SESSION_ENCRYPT=false` (database driver).
     *
     * @param  \SessionHandlerInterface  $handler  The session driver handler.
     * @return UuidSessionStore The UUID-aware session store.
     */
    protected function buildSession($handler): UuidSessionStore
    {
        return new UuidSessionStore(
            $this->config->get('session.cookie'),
            $handler,
            $id = null,
            $this->config->get('session.serialization', 'php'),
        );
    }
}