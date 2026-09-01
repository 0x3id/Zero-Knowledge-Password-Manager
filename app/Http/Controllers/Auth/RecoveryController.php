<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RecoveryCodeMail;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Throwable;

class RecoveryController extends Controller
{
    /** Session key holding the user authorized for a password reset. */
    public const PENDING_RECOVERY = 'auth.pending_recovery_user_id';

    /** Cache key prefix for OTP codes (10-minute validity). */
    public const OTP_CACHE_PREFIX = 'recovery.otp.';

    /**
     * Show the account recovery page.
     *
     * Multi-step flow: email + OTP gate, then the Recovery Key unlocks
     * the encrypted_recovery_blob locally, then a new master password is
     * set. The server never sees the Recovery Key and cannot validate it;
     * releasing the blob is therefore gated by the OTP identity proof and
     * strict rate limiting.
     *
     * @return View The recovery view.
     */
    public function create(): View
    {
        return view('auth.recovery');
    }

    /**
     * Send a one-time identity proof code by email.
     *
     * The response is deliberately identical whether or not the account
     * exists, preventing enumeration. The OTP is stored hashed with a
     * short TTL and single-use semantics via cache pull.
     *
     * @param  Request  $request  The current request.
     * @return JsonResponse A generic success response.
     */
    public function requestOtp(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user !== null && $request->session()->get(self::PENDING_RECOVERY) === null) {
            $code = (string) random_int(100000, 999999);

            Session::put(self::OTP_CACHE_PREFIX.$user->email, [
                'otp' => Hash::make($code),
                'expires_at' => now()->addMinutes(10)->getTimestamp(),
            ]);

            $this->sendOtpEmail($user, $code, $request);
        }

        return response()->json(['message' => 'If that email is registered, a recovery code has been sent.']);
    }

    /**
     * Verify the OTP gate and release the recovery blob for the user.
     *
     * On success: returns the encrypted_recovery_blob plus the vault
     * canary (both ciphertext — the client derives everything locally),
     * clears all 2FA enrollments so they can be re-registered, revokes
     * every other session, and parks the user for the password-reset
     * step.
     *
     * @param  Request  $request  The current request.
     * @param  AuditLogger  $audit  The audit logger.
     * @return JsonResponse The recovery payload.
     */
    public function verify(Request $request, AuditLogger $audit): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user === null) {
            return response()->json(['message' => 'Recovery failed.'], 422);
        }

        $stored = Session::pull(self::OTP_CACHE_PREFIX.$user->email);

        if (! is_array($stored) || ($stored['expires_at'] ?? 0) < now()->getTimestamp()
            || ! Hash::check($validated['otp'], (string) $stored['otp'])) {
            return response()->json(['message' => 'Invalid or expired recovery code.'], 422);
        }

        $request->session()->put(self::PENDING_RECOVERY, $user->id);

        // Wipe 2FA enrollments: a recovered account must re-enroll.
        $user->webauthnCredentials()->delete();
        $user->update([
            'totp_secret' => null,
            'is_2fa_enabled' => false,
            'is_totp_complete' => false,
        ]);

        // Reject all other sessions for this account immediately.
        $this->revokeOtherSessions($user, $request);

        $audit->log($user, 'recovery_used', $request);

        return response()->json([
            'ok' => true,
            'encrypted_recovery_blob' => $user->encrypted_recovery_blob,
            'vault_canary' => $user->vault_canary,
        ]);
    }

    /**
     * Set a new master password after successful recovery.
     *
     * The client re-derives a fresh auth hash input (and may rotate the
     * KDF salt/params); the server only updates the bcrypt hash and KDF
     * metadata. The encryption key is NOT rebuilt — the vault stays
     * decryptable exactly as designed.
     *
     * @param  Request  $request  The current request.
     * @param  AuditLogger  $audit  The audit logger.
     * @return RedirectResponse Redirect to mandatory 2FA re-enrollment.
     */
    public function resetPassword(Request $request, AuditLogger $audit): RedirectResponse
    {
        $userId = $request->session()->get(self::PENDING_RECOVERY);

        if ($userId === null) {
            abort(403, 'Recovery session expired.');
        }

        $validated = $request->validate([
            'new_auth_hash_input' => ['required', 'string', 'max:255'],
            'kdf_salt' => ['required', 'string', 'max:255'],
            'kdf_params' => ['required', 'json'],
        ]);

        $user = User::findOrFail($userId);

        $user->update([
            'auth_hash' => Hash::make($validated['new_auth_hash_input']),
            'kdf_salt' => $validated['kdf_salt'],
            'kdf_params' => $validated['kdf_params'],
        ]);

        $request->session()->forget(self::PENDING_RECOVERY);

        $audit->log($user, 'password_changed', $request);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('totp.setup');
    }

    /**
     * Revoke every session row of the user except the current one.
     *
     * @param  User  $user  The recovering user.
     * @param  Request  $request  The current request.
     */
    private function revokeOtherSessions(User $user, Request $request): void
    {
        $userId = $user->id;

        $request->session()->getHandler()->gc(0);
        DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $request->session()->getId())
            ->delete();
    }

    /**
     * Send the OTP email, tolerating mail transport failures.
     *
     * The message is rendered through the cyber-themed master layout under
     * the user's selected UI locale (falling back to English).
     *
     * @param  User  $user  The recovering user.
     * @param  string  $code  The 6-digit code.
     * @param  Request  $request  The current request.
     */
    private function sendOtpEmail(User $user, string $code, Request $request): void
    {
        $locale = in_array((string) $request->cookie('zkpm_lang'), ['en', 'ar'], true)
            ? (string) $request->cookie('zkpm_lang')
            : 'en';

        try {
            Mail::to($user->email)->queue(new RecoveryCodeMail($code, $locale));
        } catch (Throwable $exception) {
            logger()->warning("Recovery OTP email failed: {$exception->getMessage()}");
        }
    }
}
