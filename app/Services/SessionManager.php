<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\NewDeviceLogin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Finalizes an authenticated session per the architecture's "Session Init"
 * step: writes device/IP/location metadata to the extended `sessions` row,
 * stamps remember-me and expiry fields, and raises a new-device alert.
 *
 * Location is intentionally null until a GeoIP provider is configured
 * (open item in the architecture). The new-device email is only sent for
 * a device name/IP combination never seen for the account before, to
 * avoid alert fatigue.
 */
class SessionManager
{
    /** Default session lifetime in minutes (24 hours). */
    public const DEFAULT_TTL_MINUTES = 1440;

    /** Remember-me session lifetime in minutes (7 days). */
    public const REMEMBER_TTL_MINUTES = 10080;

    /**
     * Establish the session row for a freshly authenticated user.
     *
     * The framework only persists the session row AFTER the controller
     * returns, so this uses an upsert carrying the exact payload format
     * the database handler writes — the row exists by the time the
     * framework's own `write()` runs, which then simply updates the
     * standard columns and preserves our metadata.
     *
     * @param  User  $user  The authenticated user.
     * @param  bool  $remember  Whether the user opted into "remember me".
     * @param  Request  $request  The current request.
     */
    public function establish(User $user, bool $remember, Request $request): void
    {
        $deviceName = AuditLogger::parseUserAgent((string) $request->userAgent());
        $isNewDevice = $this->isNewDevice($user, $deviceName, (string) $request->ip());
        $ttlMinutes = $remember ? self::REMEMBER_TTL_MINUTES : self::DEFAULT_TTL_MINUTES;

        DB::table('sessions')->updateOrInsert(
            ['id' => $request->session()->getId()],
            [
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'user_agent' => (string) $request->userAgent(),
                'payload' => base64_encode(serialize($request->session()->all())),
                'last_activity' => time(),
                'device_name' => $deviceName,
                'location' => null,
                'is_remember_me' => $remember,
                'last_active_at' => now(),
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]
        );

        if ($isNewDevice) {
            $this->sendNewDeviceAlert($user, $deviceName, $request);
        }
    }

    /**
     * Determine whether this device/IP combination is new for the user.
     *
     * @param  User  $user  The authenticated user.
     * @param  string  $deviceName  The parsed device label.
     * @param  string  $ip  The client IP address.
     * @return bool True when no other session row matches both fields.
     */
    private function isNewDevice(User $user, string $deviceName, string $ip): bool
    {
        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('device_name', $deviceName)
            ->where('ip_address', $ip)
            ->where('id', '!=', session()->getId())
            ->exists() === false;
    }

    /**
     * Send the "new device signed in" alert email.
     *
     * Emails are skipped when the mailer is the no-op "array" transport,
     * and any sending failure is logged rather than breaking login. The
     * message is delivered through the cyber-themed `NewDeviceLogin`
     * notification under the user's selected UI locale.
     *
     * @param  User  $user  The authenticated user.
     * @param  string  $deviceName  The parsed device label.
     * @param  Request  $request  The current request.
     */
    private function sendNewDeviceAlert(User $user, string $deviceName, Request $request): void
    {
        if (config('mail.default') === 'array') {
            return;
        }

        $locale = in_array((string) $request->cookie('zkpm_lang'), ['en', 'ar'], true)
            ? (string) $request->cookie('zkpm_lang')
            : 'en';

        try {
            $user->notify(new NewDeviceLogin($deviceName, (string) $request->ip(), $locale));
        } catch (Throwable $exception) {
            logger()->warning("New-device alert email failed: {$exception->getMessage()}");
        }
    }
}
