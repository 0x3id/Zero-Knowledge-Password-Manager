<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the `vault_items` table.
     *
     * Holds the user's vault entries. `encrypted_password` and
     * `encrypted_notes` are AES-256-GCM ciphertext produced entirely
     * client-side; the server never sees the plaintext or the encryption
     * key. `iv` is the base64-encoded 12-byte random initialization vector
     * used for that encryption and is required for client-side decryption.
     *
     * Security note: `encrypted_password`, `encrypted_notes`, and `iv`
     * should not be logged or cached beyond the authenticated response.
     */
    public function up(): void
    {
        Schema::create('vault_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('username');
            $table->text('encrypted_password');
            $table->text('encrypted_notes')->nullable();
            $table->string('iv');
            $table->string('url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Drop the `vault_items` table.
     */
    public function down(): void
    {
        Schema::dropIfExists('vault_items');
    }
};
