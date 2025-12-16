<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Drop foreign key constraint using column name (Laravel will find the constraint name)
            $table->dropForeign(['category_id']);
        });
        
        Schema::table('documents', function (Blueprint $table) {
            // Rename the column
            $table->renameColumn('category_id', 'subcategory_id');
        });
        
        // Note: Foreign key constraint will be added after data migration
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['subcategory_id']);
            
            // Rename the column back
            $table->renameColumn('subcategory_id', 'category_id');
            
            // Add back the original foreign key constraint
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }
};