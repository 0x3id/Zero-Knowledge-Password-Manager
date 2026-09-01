<?php

namespace App\Models;

use App\Jobs\SendVerificationEmail;
use App\Mail\VerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * `id` is never mass assignable. `auth_hash` and `totp_secret` are
     * assignable only at signup/enrollment time and are hidden from all
     * serialization (see $hidden). No plaintext password or name is ever
     * stored on this model.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'email',
        'email_verified_at',
        'auth_hash',
        'kdf_salt',
        'kdf_params',
        'encrypted_recovery_blob',
        'vault_canary',
        'is_2fa_enabled',
        'is_totp_complete',
        'totp_secret',
    ];

    /**
     * The attributes that should be hidden from serialization.
     *
     * `auth_hash` (bcrypt of the client-derived auth hash input) and
     * `totp_secret` (encrypted TOTP seed) must never leak through JSON/API
     * responses, logs, or any other serialization path.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'auth_hash',
        'totp_secret',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kdf_params' => 'array',
            'email_verified_at' => 'datetime',
            'is_2fa_enabled' => 'boolean',
            'is_totp_complete' => 'boolean',
            'totp_secret' => 'encrypted',
        ];
    }

    /**
     * Return the credential Laravel's authentication guard compares against.
     *
     * Overrides the default `password` lookup: in this zero-knowledge
     * architecture the server never stores a master password, only the
     * bcrypt of the client-derived auth hash input. This allows
     * `Auth::attempt()`-style flows to verify against `auth_hash` instead
     * of a `password` column.
     *
     * @return string The bcrypt-verifiable `auth_hash` value.
     */
    public function getAuthPassword(): string
    {
        return (string) $this->auth_hash;
    }

    /**
     * Send the account ownership verification email.
     *
     * Mandatory email verification is enforced app-wide (ARCHITECTURE /
     * SRS). The notification is sent through the cyber-themed `VerifyEmail`
     * Mailable under the recipient's selected UI locale so the message
     * renders in the correct language and text direction. Confirming an
     * email never touches vault key material — it only proves address
     * ownership.
     *
     * @return void
     */
    public function sendEmailVerificationNotification(): void
    {
        SendVerificationEmail::dispatch($this, $this->preferredLocale());
    }

    /**
     * Resolve this user's preferred UI locale for outgoing email.
     *
     * Falls back to the locale currently configured on the request so
     * emails remain localized even before the account has persisted a
     * preference.
     *
     * @return string The two-letter locale code (en | ar).
     */
    public function preferredLocale(): string
    {
        return in_array(app()->getLocale(), ['en', 'ar'], true)
            ? app()->getLocale()
            : 'en';
    }

    /**
     * Get all WebAuthn credentials registered by this user.
     */
    public function webauthnCredentials(): HasMany
    {
        return $this->hasMany(WebauthnCredential::class);
    }

    /**
     * Get all vault categories owned by this user.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get all vault items owned by this user.
     */
    public function vaultItems(): HasMany
    {
        return $this->hasMany(VaultItem::class);
    }

    /**
     * Get all audit log records for this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
