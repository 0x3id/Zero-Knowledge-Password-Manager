<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Make the `username` column non-unique so multiple accounts may
     * share a display handle. Usernames are not used for identity lookups
     * or login; the `email` column remains the unique login identifier.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
        });
    }

    /**
     * Re-add the unique constraint on `username`.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }
};
