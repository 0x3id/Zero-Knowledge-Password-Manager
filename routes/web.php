<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LockController;
use App\Http\Controllers\Auth\WebauthnRegistrationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PasswordGeneratorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\VaultItemController;
use App\Http\Controllers\VaultPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Application web routes adhering to the zero-knowledge architecture:
| - Vault items & categories (client-side encrypted payloads).
| - Multi-mode password generator with breach detection.
| - Active sessions & device revocation.
| - Privacy-preserving activity audit logs.
| - WebAuthn passkey management.
|
*/

// Public landing page; authenticated visitors are sent straight to the vault.
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : app(LandingController::class)->index();
    // return app(LandingController::class)->index();
})->name('landing');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function (): void {
    // Profile management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Client-side lock context (vault canary and WebAuthn enrollment flag)
    Route::get('/lock-data', [LockController::class, 'show'])->name('lock-data');

    // Vault item CRUD (ciphertext payloads only).
    // HTML page: /vault — JSON list API: /vault/items.
    Route::get('/vault', [VaultPageController::class, 'index'])->name('vault');
    Route::get('/vault/items', [VaultItemController::class, 'index'])->name('vault.items');
    Route::post('/vault', [VaultItemController::class, 'store'])->name('vault.store');
    Route::put('/vault/{vaultItem}', [VaultItemController::class, 'update'])->name('vault.update');
    Route::delete('/vault/{vaultItem}', [VaultItemController::class, 'destroy'])->name('vault.destroy');

    // Category CRUD
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    // Multi-mode Password Generator
    Route::get('/generator', [PasswordGeneratorController::class, 'index'])->name('generator');
    Route::post('/generator/audit', [PasswordGeneratorController::class, 'logGeneration'])->name('generator.audit');

    // Active Sessions & Multi-Device Management (FR-8)
    Route::get('/sessions', [SessionController::class, 'index'])->name('sessions.index');
    Route::delete('/sessions/{sessionId}', [SessionController::class, 'destroy'])->name('sessions.destroy');
    Route::post('/sessions/revoke-others', [SessionController::class, 'destroyOthers'])->name('sessions.destroy-others');

    // Privacy-Preserving Audit Logs (FR-9)
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

    // Security Center (settings hub)
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

    // WebAuthn Passkey Registration & Management (FR-3)
    Route::get('/webauthn/credentials', [WebauthnRegistrationController::class, 'index'])->name('webauthn.index');
    Route::post('/webauthn/register/options', [WebauthnRegistrationController::class, 'options'])->name('webauthn.register.options');
    Route::post('/webauthn/register/verify', [WebauthnRegistrationController::class, 'verify'])->name('webauthn.register.verify');
    Route::delete('/webauthn/credentials/{credential}', [WebauthnRegistrationController::class, 'destroy'])->name('webauthn.destroy');
});

require __DIR__.'/auth.php';
