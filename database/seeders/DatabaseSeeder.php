<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use App\Models\VaultItem;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Creates a standard demonstration user adhering to the zero-knowledge schema:
     * - Email: test@example.com
     * - Auth Hash Input: zkpm-test-auth-hash-input (Master Password: "Password123!")
     * - TOTP Secret: JBSWY3DPEHPK3PXP
     * - Preconfigured categories (Personal, Work, Finance, Social)
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'auth_hash' => Hash::make('zkpm-test-auth-hash-input'),
                'kdf_salt' => base64_encode(random_bytes(16)),
                'kdf_params' => [
                    'algorithm' => 'PBKDF2-SHA256',
                    'iterations' => 600000,
                    'hash' => 'SHA-256',
                ],
                'encrypted_recovery_blob' => json_encode([
                    'iv' => base64_encode(random_bytes(12)),
                    'ciphertext' => base64_encode(random_bytes(32)),
                ]),
                'vault_canary' => json_encode([
                    'iv' => base64_encode(random_bytes(12)),
                    'ciphertext' => base64_encode(random_bytes(32)),
                ]),
                'is_2fa_enabled' => true,
                'is_totp_complete' => true,
                'totp_secret' => 'JBSWY3DPEHPK3PXP',
            ]
        );

        // Seed default categories
        $categories = [
            'Personal',
            'Work',
            'Finance',
            'Social',
        ];

        foreach ($categories as $catName) {
            Category::firstOrCreate([
                'user_id' => $user->id,
                'name' => $catName,
            ]);
        }
    }
}
