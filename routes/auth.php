<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\RecoveryController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SaltLookupController;
use App\Http\Controllers\Auth\TwoFactorAuthenticatedSessionController;
use App\Http\Controllers\Auth\TwoFactorSecretController;
use App\Http\Controllers\Auth\WebauthnAuthenticationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Breeze-sourced session scaffolding customized for the zero-knowledge
| architecture: the client derives the auth hash (never a password), and
| TOTP is the MANDATORY second factor (temporary override of the
| WebAuthn-first requirement). Password-reset, email-verification, and
| password-confirmation routes are removed because the users table has
| no `password` or `email_verified_at` columns.
|
*/

// Guest-only routes: registration, factor 1 login, mandatory TOTP
// challenge, KDF salt lookup, and recovery-key account recovery.
Route::middleware('guest')->group(function (): void {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])
        ->name('register.submit')
        ->middleware('throttle:auth.register');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.submit')
        ->middleware('throttle:auth.login');

    Route::get('totp/challenge', [TwoFactorAuthenticatedSessionController::class, 'create'])->name('totp.challenge');
    Route::post('totp/verify', [TwoFactorAuthenticatedSessionController::class, 'store'])
        ->name('totp.verify')
        ->middleware('throttle:auth.totp');

    Route::get('recovery', [RecoveryController::class, 'create'])->name('recovery');
    Route::post('recovery/request-otp', [RecoveryController::class, 'requestOtp'])
        ->name('recovery.request-otp')
        ->middleware('throttle:auth.recovery');
    Route::post('recovery/verify', [RecoveryController::class, 'verify'])
        ->name('recovery.verify')
        ->middleware('throttle:auth.recovery');
    Route::post('recovery/reset-password', [RecoveryController::class, 'resetPassword'])
        ->name('recovery.reset-password');
});

// KDF salt lookup is intentionally middleware-free (neither guest nor auth):
// the vault UNLOCK flow runs while the user is authenticated (guest-only
// routes redirect authenticated sessions to the dashboard HTML, which the
// client would fail to parse as JSON), while login uses it as a guest.
// It only ever returns a per-account salt or a deterministic decoy, never
// any account data, so it is safe for both states.
Route::get('kdf-salt', [SaltLookupController::class, 'show'])
    ->name('kdf-salt')
    ->middleware('throttle:auth.salt');

// Authenticated routes: mandatory TOTP enrollment, email verification, and logout.
Route::middleware('auth')->group(function (): void {
    // Mandatory email verification (unverified users are blocked from vault
    // routes via the `verified` middleware until they confirm ownership).
    Route::get('email/verify', EmailVerificationPromptController::class)
        ->name('verification.notice');
    Route::get('email/verify/{id}/{hash}', EmailVerificationController::class)
        ->name('verification.verify');
    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:auth.verification')->name('verification.send');

    Route::get('totp/setup', [TwoFactorSecretController::class, 'create'])->name('totp.setup');
    Route::post('totp/setup/options', [TwoFactorSecretController::class, 'store'])->name('totp.setup.options');
    Route::post('totp/setup/verify', [TwoFactorSecretController::class, 'update'])->name('totp.setup.verify');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// WebAuthn assertions (dormant while TOTP is the mandatory factor):
// kept for the biometric vault-unlock path. No auth/guest middleware —
// the controller resolves a pending login OR an authenticated user.
Route::post('2fa/webauthn/options', [WebauthnAuthenticationController::class, 'options'])->name('2fa.webauthn.options');
Route::post('2fa/webauthn/verify', [WebauthnAuthenticationController::class, 'verify'])->name('2fa.webauthn.verify');
