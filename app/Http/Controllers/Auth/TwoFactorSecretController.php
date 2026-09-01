<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TOTPService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TwoFactorSecretController extends Controller
{
    /** Session key holding a TOTP secret pending enrollment verification. */
    public const PENDING_SECRET = 'auth.pending_totp_secret';

    /**
     * Show the mandatory TOTP enrollment page.
     *
     * TOTP is enforced as the second factor for ALL users: access to the
     * dashboard is gated by `is_totp_complete`. Users who already
     * completed enrollment are bounced to the dashboard.
     *
     * @param  Request  $request  The current request.
     * @return View|RedirectResponse The setup view or redirect.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()->is_totp_complete) {
            return redirect()->route('dashboard');
        }

        return view('auth.totp-setup');
    }

    /**
     * Generate a fresh TOTP secret for enrollment and return its URI.
     *
     * The secret is held only in the session until the user proves
     * possession of an authenticator app by submitting a valid code.
     *
     * @param  Request  $request  The current request.
     * @param  TOTPService  $totp  The TOTP service.
     * @return JsonResponse The secret and provisioning URI.
     */
    public function store(Request $request, TOTPService $totp): JsonResponse
    {
        $secret = $totp->generateSecret();
        $request->session()->put(self::PENDING_SECRET, $secret);

        return response()->json([
            'secret' => $secret,
            'uri' => $totp->otpauthUri($secret, (string) $request->user()->email),
        ]);
    }

    /**
     * Verify the enrollment code and activate TOTP for the user.
     *
     * On success the secret is stored encrypted at rest and the account
     * is flagged `is_totp_complete = true`, unlocking the dashboard.
     * Repeated failed codes clear the pending secret and force a fresh
     * enrollment attempt.
     *
     * @param  Request  $request  The current request.
     * @param  TOTPService  $totp  The TOTP service.
     * @return JsonResponse The outcome payload.
     */
    public function update(Request $request, TOTPService $totp): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $secret = $request->session()->pull(self::PENDING_SECRET);

        if ($secret === null || ! $totp->verify($secret, $validated['code'])) {
            $request->session()->forget(self::PENDING_SECRET);

            return response()->json(['message' => 'Invalid code; please rescan and retry.'], 422);
        }

        $user->update([
            'totp_secret' => $secret,
            'is_2fa_enabled' => true,
            'is_totp_complete' => true,
        ]);

        app(AuditLogger::class)->log($user, '2fa_totp_enabled', $request);

        return response()->json(['ok' => true, 'redirect' => route('dashboard')]);
    }
}
