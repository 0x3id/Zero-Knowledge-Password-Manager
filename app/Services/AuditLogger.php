<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Writes account-action records to the `audit_logs` table.
 *
 * Per the architecture, only action + metadata (IP, device) are logged —
 * never vault content, item titles, or credentials. All writes are
 * wrapped in a try/catch so a storage hiccup can never break the
 * user-facing action that triggered the audit entry.
 */
class AuditLogger
{
    /** Whitelist of action types matching the audit_logs table enum. */
    public const ACTIONS = [
        'login',
        'logout',
        'vault_item_created',
        'vault_item_updated',
        'vault_item_deleted',
        'password_changed',
        'password_generated',
        'webauthn_registered',
        'webauthn_removed',
        '2fa_totp_enabled',
        'session_revoked',
        'recovery_used',
        'email_verified',
    ];

    /**
     * Persist an audit entry.
     *
     * @param  User|null  $user  The acting user (null for unauthenticated events).
     * @param  string  $action  One of the ACTIONS whitelist.
     * @param  Request  $request  The current request for IP/device metadata.
     */
    public function log(?User $user, string $action, Request $request): void
    {
        if (! in_array($action, self::ACTIONS, true)) {
            Log::warning("Audit action not in whitelist: {$action}");

            return;
        }

        try {
            AuditLog::create([
                'user_id' => $user?->id,
                'action_type' => $action,
                'ip_address' => $request->ip(),
                'device_info' => self::parseUserAgent((string) $request->userAgent()),
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::warning("Failed to write audit log entry: {$exception->getMessage()}");
        }
    }

    /**
     * Parse a User-Agent string into a compact "Browser on OS" label.
     *
     * @param  string  $userAgent  The raw User-Agent header value.
     * @return string The human-readable device label.
     */
    public static function parseUserAgent(string $userAgent): string
    {
        $browser = 'Unknown browser';
        $os = 'Unknown OS';

        if (preg_match('/Edg\/(\d+)/', $userAgent, $matches)) {
            $browser = 'Edge';
        } elseif (preg_match('/Firefox\/(\d+)/', $userAgent, $matches)) {
            $browser = 'Firefox';
        } elseif (preg_match('/OPR\/(\d+)/', $userAgent, $matches) || str_contains($userAgent, 'Opera')) {
            $browser = 'Opera';
        } elseif (preg_match('/Chrome\/(\d+)/', $userAgent, $matches)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Safari\/(\d+)/', $userAgent, $matches)) {
            $browser = 'Safari';
        }

        if (str_contains($userAgent, 'Windows NT 10.0')) {
            $os = 'Windows 10/11';
        } elseif (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'iPhone')) {
            $os = 'iOS';
        } elseif (str_contains($userAgent, 'iPad')) {
            $os = 'iPadOS';
        } elseif (str_contains($userAgent, 'Mac OS X')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        }

        return $browser.' on '.$os;
    }
}
