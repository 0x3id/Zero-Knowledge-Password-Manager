<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the TOTP completion flag to the `users` table.
     *
     * `is_totp_complete` gates vault access: TOTP is the MANDATORY second
     * factor (temporary override of the WebAuthn-first requirement), so a
     * freshly registered account must finish TOTP enrollment before the
     * dashboard is usable. The `totp_secret` column already exists and
     * remains encrypted at rest via the model's `encrypted` cast.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_totp_complete')->default(false)->after('totp_secret');
        });
    }

    /**
     * Remove the TOTP completion flag from the `users` table.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_totp_complete');
        });
    }
};
