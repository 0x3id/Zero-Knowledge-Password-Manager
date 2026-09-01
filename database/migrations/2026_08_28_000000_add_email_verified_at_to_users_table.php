<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the email verification timestamp to the `users` table.
     *
     * Mandatory email verification is enforced app-wide: `email_verified_at`
     * is NULL until the user confirms ownership of the registered address via
     * the link emailed by the application. Vault and sensitive routes are
     * gated behind Laravel's `verified` middleware, so this column is the
     * single source of truth that unlocks account access. The zero-knowledge
     * guarantee is unaffected — confirming an email never reveals or resets
     * any key material; it only proves address ownership.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });
    }

    /**
     * Drop the email verification timestamp column.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });
    }
};
