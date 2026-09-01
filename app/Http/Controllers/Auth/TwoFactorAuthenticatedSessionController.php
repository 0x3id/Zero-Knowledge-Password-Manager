<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SessionManager;
use App\Services\TOTPService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TwoFactorAuthenticatedSessionController extends Controller
{
    /** Session key holding the user pending the TOTP challenge. */
    public const PENDING_USER = 'auth.pending_totp_user_id';

    /** Session key holding the pending "remember me" preference. */
    public const PENDING_REMEMBER = 'auth.pending_totp_remember';

    /**
     * Show the mandatory TOTP challenge page.
     *
     * @param  Request  $request  The current request.
     * @return View|RedirectResponse The challenge view or redirect.
     */
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->session()->get(self::PENDING_USER) === null) {
            return redirect()->route('login');
        }

        return view('auth.totp-challenge');
    }

    /**
     * Verify the TOTP code for login and complete authentication.
     *
     * The session is only authorized (Auth::login) after this code
     * verifies against the stored, at-rest-encrypted secret.
     * Writes the extended session row (device/IP/location), raises a new-device alert,
     * and records the audit entry. The client then persists the already-derived
     * Encryption Key from sessionStorage into memory.
     *
     * @param  Request  $request  The current request.
     * @param  TOTPService  $totp  The TOTP service.
     * @return JsonResponse The outcome including the redirect target.
     */
    public function store(Request $request, TOTPService $totp): JsonResponse
    {
        $userId = $request->session()->get(self::PENDING_USER);
        $user = $userId === null ? null : User::find($userId);

        if ($user === null) {
            return response()->json(['message' => 'No pending login.'], 403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $secret = $user->totp_secret;

        if ($secret === null || ! $totp->verify($secret, $validated['code'])) {
            return response()->json(['message' => 'Invalid code.'], 422);
        }

        $remember = (bool) $request->session()->pull(self::PENDING_REMEMBER, false);
        $request->session()->forget(self::PENDING_USER);

        Auth::login($user);
        $request->session()->regenerate();

        app(SessionManager::class)->establish($user, $remember, $request);
        app(AuditLogger::class)->log($user, 'login', $request);

        return response()->json(['ok' => true, 'redirect' => route('dashboard')]);
    }
}
