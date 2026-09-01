<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Zero-knowledge user factory.
 *
 * There is no `name`, `password`, or `email_verified_at` column: the
 * account is identified only by email, an `auth_hash` (bcrypt of the
 * client-derived auth hash input), KDF parameters, and the encrypted
 * recovery material. The default `auth_hash_input` value is
 * `'zkpm-test-auth-hash-input'`.
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current auth hash input used by the factory.
     */
    protected static ?string $authHashInput;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'username' => fake()->unique()->userName(),
            'email_verified_at' => now(),
            'auth_hash' => static::$authHashInput ??= Hash::make('zkpm-test-auth-hash-input'),
            'kdf_salt' => base64_encode(random_bytes(16)),
            'kdf_params' => json_encode([
                'algorithm' => 'PBKDF2-SHA256',
                'iterations' => 600000,
                'hash' => 'SHA-256',
            ]),
            'encrypted_recovery_blob' => Str::random(128),
            'vault_canary' => Str::random(64),
            'is_2fa_enabled' => true,
            'is_totp_complete' => true,
            'totp_secret' => 'JBSWY3DPEHPK3PXP',
        ];
    }

    /**
     * Indicate that the account has not completed TOTP enrollment.
     */
    public function totpIncomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_2fa_enabled' => false,
            'is_totp_complete' => false,
            'totp_secret' => null,
        ]);
    }

    /**
     * Indicate that the account has not yet verified its email address.
     *
     * Vault/sensitive access is blocked by the `verified` middleware until
     * `email_verified_at` is set, mirroring a freshly registered account.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
