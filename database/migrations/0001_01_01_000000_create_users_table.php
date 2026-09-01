<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the `users` table for the zero-knowledge password manager.
     *
     * Security note: This table deliberately contains NO plaintext password
     * or `remember_token`. The server only ever stores the bcrypt of the
     * client-derived auth hash (`auth_hash`), the KDF salt and parameters,
     * and ciphertext blobs (`encrypted_recovery_blob`, `vault_canary`). All
     * encryption keys are derived client-side and never leave the browser.
     * The `totp_secret` is additionally encrypted at rest via Eloquent's
     * `encrypted` cast.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('auth_hash');
            $table->string('kdf_salt')->unique();
            $table->json('kdf_params');
            $table->text('encrypted_recovery_blob');
            $table->text('vault_canary');
            $table->boolean('is_2fa_enabled')->default(false);
            $table->text('totp_secret')->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Drop all tables created by this migration, in reverse dependency order.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
