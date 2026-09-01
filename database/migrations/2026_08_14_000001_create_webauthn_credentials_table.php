<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the `webauthn_credentials` table.
     *
     * Stores the per-device WebAuthn public keys used for the biometric
     * unlock convenience layer. Each device registered by a user produces
     * exactly one row. `credential_id` is the opaque, device-unique
     * identifier returned by the authenticator and is unique across all
     * users to prevent cross-account replay of a single device.
     *
     * Note: `credential_id` is a varchar rather than text because MySQL
     * cannot index TEXT/BLOB columns (a unique constraint is required).
     * A base64url-encoded credential ID never approaches 512 characters.
     *
     * Security note: `public_key` must NEVER be re-derived or written to
     * logs; it is public-key material for signature verification only.
     */
    public function up(): void
    {
        Schema::create('webauthn_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('credential_id', 512)->unique();
            $table->text('public_key');
            $table->string('device_label');
            $table->unsignedInteger('counter')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Drop the `webauthn_credentials` table.
     */
    public function down(): void
    {
        Schema::dropIfExists('webauthn_credentials');
    }
};
