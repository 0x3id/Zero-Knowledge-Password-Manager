<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration form.
     *
     * The client-side JS performs all key derivation (PBKDF2 + HKDF) and
     * encryption before submission, so this form only collects the email
     * and master password; derived values arrive as hidden fields.
     *
     * @return View The registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Store a newly registered user.
     *
     * Receives the zero-knowledge payload produced entirely client-side:
     * the bcrypt-of-auth-hash input, KDF salt and parameters, the
     * recovery blob (encryption key wrapped by the recovery key), and the
     * vault canary. The plaintext master password and the encryption key
     * NEVER reach this endpoint.
     *
     * The account is immediately logged in but vault access stays gated:
     * `is_totp_complete` is false, so the user is routed to the mandatory
     * TOTP enrollment page before the dashboard.
     *
     * @param  Request  $request  The registration request.
     * @return RedirectResponse Redirects to the mandatory TOTP setup.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'auth_hash_input' => ['required', 'string', 'max:255'],
            'kdf_salt' => ['required', 'string', 'max:255'],
            'kdf_params' => ['required', 'json'],
            'encrypted_recovery_blob' => ['required', 'string'],
            'vault_canary' => ['required', 'string'],
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'auth_hash' => Hash::make($validated['auth_hash_input']),
            'kdf_salt' => $validated['kdf_salt'],
            'kdf_params' => $validated['kdf_params'],
            'encrypted_recovery_blob' => $validated['encrypted_recovery_blob'],
            'vault_canary' => $validated['vault_canary'],
            'is_2fa_enabled' => false,
            'is_totp_complete' => false,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        app(SessionManager::class)->establish($user, false, $request);

        // Mandatory email verification: the freshly registered account is
        // unverified (email_verified_at is NULL), so vault access stays
        // blocked until ownership of the address is confirmed. The client
        // derived keys/recovery material in sessionStorage survive this
        // redirect — sessionStorage is scoped to the tab and none of the
        // verification pages clear it.
        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}
