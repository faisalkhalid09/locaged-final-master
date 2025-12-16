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
        // Some databases already have the folder_id column from a previous migration.
        // Only add it if it does not exist to avoid duplicate-column errors.
        Schema::table('documents', function (Blueprint $table) {
            if (! Schema::hasColumn('documents', 'folder_id')) {
                $table->foreignId('folder_id')->nullable()->after('uid')->constrained('folders')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['folder_id']);
            $table->dropColumn('folder_id');
        });
    }
};
