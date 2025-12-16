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
        // Update documents to point to the first subcategory of their category
        // Note: category_id was already renamed to subcategory_id in previous migration
        // So subcategory_id currently contains category IDs, we need to convert them to subcategory IDs
        DB::statement('
            UPDATE documents d
            INNER JOIN subcategories s ON s.category_id = d.subcategory_id
            SET d.subcategory_id = s.id
            WHERE d.subcategory_id IS NOT NULL
            AND s.id = (
                SELECT id FROM subcategories 
                WHERE category_id = d.subcategory_id 
                ORDER BY id ASC 
                LIMIT 1
            )
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration cannot be easily reversed as we lose the original category_id
        // The original category_id is lost when we rename the column
        // This would require a more complex rollback strategy
        throw new Exception('This migration cannot be reversed automatically. Manual intervention required.');
    }
};