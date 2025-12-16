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
            // Only add the column and foreign key if they don't already exist
            if (! Schema::hasColumn('documents', 'folder_id')) {
                $table->foreignId('folder_id')
                    ->nullable()
                    ->after('uid')
                    ->constrained('folders')
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
            if (Schema::hasColumn('documents', 'folder_id')) {
                // Drop FK first if it exists
                try {
                    $table->dropForeign(['folder_id']);
                } catch (\Throwable $e) {
                    // In case the foreign key name is different, ignore
                }

                $table->dropColumn('folder_id');
            }
        });
    }
};
