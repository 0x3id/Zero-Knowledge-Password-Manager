<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Extend the audit_logs action_type enum with the `email_verified` action.
     *
     * Recording email-confirmation events gives users a complete, private
     * audit trail of account lifecycle changes. The existing enum values
     * are preserved verbatim; only the new action is appended. Logging
     * contains metadata only (no vault content or email body).
     *
     * The raw `ALTER TABLE` is MySQL-specific; SQLite implements `enum` as
     * a plain VARCHAR without a check constraint, so no change is needed
     * there (and the statement would be invalid SQL for that driver). The
     * driver guard keeps the migration portable across the test and
     * production databases.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE audit_logs MODIFY action_type ENUM(
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
            'email_verified'
        ) NOT NULL");
    }

    /**
     * Restore the original action_type enum (dropping the new action).
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE audit_logs MODIFY action_type ENUM(
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
            'recovery_used'
        ) NOT NULL");
    }
};
