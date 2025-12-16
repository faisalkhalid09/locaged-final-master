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
        Schema::table('documents', function (Blueprint $table) {
            // Only add the column if it does not already exist (safety for partially-migrated DBs)
            if (! Schema::hasColumn('documents', 'service_id')) {
                $table->foreignId('service_id')
                    ->nullable()
                    ->after('department_id')
                    ->constrained('services')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'service_id')) {
                try {
                    $table->dropForeign(['service_id']);
                } catch (\Throwable $e) {
                    // If foreign key name differs, ignore
                }

                $table->dropColumn('service_id');
            }
        });
    }
};
