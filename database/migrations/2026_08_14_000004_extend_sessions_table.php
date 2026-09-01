<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extend Breeze's default `sessions` table with device-management
     * columns as specified in the architecture (section 3).
     *
     * `device_name` is parsed from the User-Agent, `location` is filled
     * via GeoIP (provider TBD — nullable until configured), and
     * `last_active_at`/`expires_at` support the 24h default / 7-day
     * "remember me" session lifetimes.
     */
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->string('device_name')->nullable()->after('ip_address');
            $table->string('location')->nullable()->after('device_name');
            $table->boolean('is_remember_me')->default(false)->after('location');
            $table->timestamp('last_active_at')->nullable()->after('is_remember_me');
            $table->timestamp('expires_at')->nullable()->after('last_active_at');
        });
    }

    /**
     * Remove the added device-management columns from `sessions`.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->dropColumn([
                'device_name',
                'location',
                'is_remember_me',
                'last_active_at',
                'expires_at',
            ]);
        });
    }
};
