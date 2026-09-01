<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the `categories` table.
     *
     * Categories are user-scoped organizational labels (e.g. "Banking",
     * "Work") for vault items. Category names are stored as plaintext,
     * matching the architecture: titles/labels are not part of the
     * zero-knowledge guarantee, only secret payloads are encrypted.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Drop the `categories` table.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
