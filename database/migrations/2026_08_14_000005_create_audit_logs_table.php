<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the `audit_logs` table.
     *
     * Stores immutable account-action records (login, logout, vault
     * changes, 2FA changes, session revocations, recovery use) with
     * device/IP metadata only. Per the architecture, NO vault content,
     * item titles, or credentials ever appear in log entries.
     *
     * The `action_type` enum matches the exactly specified set in
     * ARCHITECTURE.md section 3.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->enum('action_type', [
                'login',
                'logout',
                'vault_item_created',
                'vault_item_updated',
                'vault_item_deleted',
                'password_changed',
                'password_generated',
                'webauthn_registered',
                'webauthn_removed',
                '2fa_totp_enabled',
                'session_revoked',
                'recovery_used',
            ]);
            $table->string('ip_address')->nullable();
            $table->string('device_info')->nullable();
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Drop the `audit_logs` table.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
