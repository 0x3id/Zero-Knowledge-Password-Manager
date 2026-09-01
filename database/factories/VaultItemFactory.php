<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VaultItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VaultItem>
 */
class VaultItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * The sensitive fields hold opaque ciphertext placeholders; plaintext
     * is never part of the server-side model.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => null,
            'title' => fake()->words(2, true),
            'username' => fake()->userName(),
            'encrypted_password' => fake()->sha256(),
            'encrypted_notes' => null,
            'iv' => fake()->sha1(),
            'url' => null,
        ];
    }
}
