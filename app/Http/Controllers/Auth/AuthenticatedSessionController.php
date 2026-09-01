<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\SessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /** Session key holding the user pending the 2FA challenge. */
    public const PENDING_USER = TwoFactorAuthenticatedSessionController::PENDING_USER;

    /** Session key holding the pending "remember me" preference. */
    public const PENDING_REMEMBER = TwoFactorAuthenticatedSessionController::PENDING_REMEMBER;

    /** A cached bcrypt sentinel for timing-equalized decoy checks. */
    private static ?string $decoyAuthHash = null;

    /**
     * Show the login form (factor 1).
     *
     * @return View The login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Validate factor 1 (email + client-derived auth hash input).
     *
     * The auth hash input is checked with bcrypt against the stored
     * auth_hash. Unknown emails are verified against a decoy bcrypt value
     * so the request time and the error message are identical, defeating
     * user enumeration.
     *
     * TOTP is the MANDATORY second factor (temporary override of the
     * WebAuthn-first design): factor-1 success only parks the user in a
     * pending session and redirects to the TOTP challenge. The Laravel
     * session is NOT authorized until the TOTP code verifies. Users who
     * never completed TOTP enrollment are sent to the enrollment page.
     *
     * @param  Request  $request  The login request.
     * @return RedirectResponse The next step in the login flow.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'auth_hash_input' => ['required', 'string', 'max:255'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $user = User::where('email', $validated['email'])->first();
        $remember = (bool) ($validated['remember'] ?? false);

        if ($user !== null && Hash::check($validated['auth_hash_input'], $user->auth_hash)) {
            $request->session()->regenerate();

            if (! $user->hasVerifiedEmail()) {
                return redirect()->route('verification.notice');
            }

            if (! $user->is_totp_complete) {
                Auth::login($user);
                app(SessionManager::class)->establish($user, $remember, $request);

                return redirect()->route('totp.setup');
            }

            $request->session()->put(TwoFactorAuthenticatedSessionController::PENDING_USER, $user->id);
            $request->session()->put(TwoFactorAuthenticatedSessionController::PENDING_REMEMBER, $remember);

            return redirect()->route('totp.challenge');
        }

        // Timing equality for unknown emails: always run a bcrypt check.
        Hash::check($validated['auth_hash_input'], $this->decoyAuthHash());

        return back()
            ->withErrors(['auth_hash_input' => 'Invalid email or password.'])
            ->onlyInput('email', 'remember');
    }

    /**
     * Complete the two-factor authentication login sequence.
     *
     * @param  User  $user  The authenticated user.
     * @param  Request  $request  The login request.
     * @return RedirectResponse Redirect to the dashboard.
     */
    public static function completeTwoFactorLogin(User $user, Request $request): RedirectResponse
    {
        $remember = (bool) $request->session()->pull(TwoFactorAuthenticatedSessionController::PENDING_REMEMBER, false);
        $request->session()->forget(TwoFactorAuthenticatedSessionController::PENDING_USER);

        Auth::login($user);
        $request->session()->regenerate();

        app(SessionManager::class)->establish($user, $remember, $request);
        app(AuditLogger::class)->log($user, 'login', $request);

        return redirect()->intended(route('dashboard'));
    }



    /**
     * Log the user out and record the audit entry; session is destroyed.
     *
     * @param  Request  $request  The current request.
     * @return RedirectResponse Redirect to the login page.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        app(AuditLogger::class)->log($user, 'logout', $request);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Return the cached bcrypt decoy used for enumeration-safe checks.
     *
     * @return string The bcrypt hash of a fixed sentinel value.
     */
    private function decoyAuthHash(): string
    {
        if (self::$decoyAuthHash === null) {
            self::$decoyAuthHash = Hash::make('invalid-auth-hash-input-sentinel');
        }

        return self::$decoyAuthHash;
    }
}
