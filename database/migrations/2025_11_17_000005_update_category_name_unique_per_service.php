<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop the global unique constraint on name so the same
            // category name can be reused across different services.
            try {
                $table->dropUnique(['name']);
            } catch (\Throwable $e) {
                // Fallback for databases where the index name must be used explicitly
                try {
                    $table->dropUnique('categories_name_unique');
                } catch (\Throwable $e2) {
                    // If the index does not exist, continue silently
                }
            }

            // Enforce uniqueness per service: within a single service,
            // category names must be unique; across services they may repeat.
            $table->unique(['service_id', 'name'], 'categories_service_id_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Drop the composite unique index
            try {
                $table->dropUnique('categories_service_id_name_unique');
            } catch (\Throwable $e) {
                // Ignore if the index is missing
            }

            // Restore the original global unique constraint on name
            $table->unique('name');
        });
    }
};
