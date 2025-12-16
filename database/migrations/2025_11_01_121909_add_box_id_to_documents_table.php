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
        // Add box_id column (nullable during migration period)
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('box_id')->nullable()->after('physical_location_id')->constrained('boxes')->nullOnDelete();
            $table->index('box_id');
        });

        // Migrate existing physical_location_id to box_id using migration map
        if (Schema::hasTable('physical_location_migration_map')) {
            $mappings = DB::table('physical_location_migration_map')->get();
            
            foreach ($mappings as $mapping) {
                DB::table('documents')
                    ->where('physical_location_id', $mapping->old_physical_location_id)
                    ->update(['box_id' => $mapping->new_box_id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert box_id back to physical_location_id if mapping exists
        if (Schema::hasTable('physical_location_migration_map')) {
            $mappings = DB::table('physical_location_migration_map')->get();
            
            foreach ($mappings as $mapping) {
                DB::table('documents')
                    ->where('box_id', $mapping->new_box_id)
                    ->update(['physical_location_id' => $mapping->old_physical_location_id]);
            }
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['box_id']);
            $table->dropIndex(['box_id']);
            $table->dropColumn('box_id');
        });
    }
};
