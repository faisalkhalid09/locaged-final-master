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
        Schema::table('document_movements', function (Blueprint $table) {
            // Add new box_id columns (nullable during migration period)
            $table->foreignId('moved_from_box_id')->nullable()->after('moved_from')->constrained('boxes')->nullOnDelete();
            $table->foreignId('moved_to_box_id')->nullable()->after('moved_to')->constrained('boxes')->nullOnDelete();
            
            $table->index('moved_from_box_id');
            $table->index('moved_to_box_id');
        });

        // Migrate existing moved_from and moved_to to box_ids using migration map
        if (Schema::hasTable('physical_location_migration_map')) {
            $mappings = DB::table('physical_location_migration_map')->pluck('new_box_id', 'old_physical_location_id');
            
            // Update moved_from_box_id
            DB::table('document_movements')
                ->whereNotNull('moved_from')
                ->get()
                ->each(function ($movement) use ($mappings) {
                    if (isset($mappings[$movement->moved_from])) {
                        DB::table('document_movements')
                            ->where('id', $movement->id)
                            ->update(['moved_from_box_id' => $mappings[$movement->moved_from]]);
                    }
                });
            
            // Update moved_to_box_id
            DB::table('document_movements')
                ->whereNotNull('moved_to')
                ->get()
                ->each(function ($movement) use ($mappings) {
                    if (isset($mappings[$movement->moved_to])) {
                        DB::table('document_movements')
                            ->where('id', $movement->id)
                            ->update(['moved_to_box_id' => $mappings[$movement->moved_to]]);
                    }
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert box_ids back to physical_location_ids if mapping exists
        if (Schema::hasTable('physical_location_migration_map')) {
            $reverseMappings = DB::table('physical_location_migration_map')
                ->pluck('old_physical_location_id', 'new_box_id');
            
            // Revert moved_from_box_id
            DB::table('document_movements')
                ->whereNotNull('moved_from_box_id')
                ->get()
                ->each(function ($movement) use ($reverseMappings) {
                    if (isset($reverseMappings[$movement->moved_from_box_id])) {
                        DB::table('document_movements')
                            ->where('id', $movement->id)
                            ->update(['moved_from' => $reverseMappings[$movement->moved_from_box_id]]);
                    }
                });
            
            // Revert moved_to_box_id
            DB::table('document_movements')
                ->whereNotNull('moved_to_box_id')
                ->get()
                ->each(function ($movement) use ($reverseMappings) {
                    if (isset($reverseMappings[$movement->moved_to_box_id])) {
                        DB::table('document_movements')
                            ->where('id', $movement->id)
                            ->update(['moved_to' => $reverseMappings[$movement->moved_to_box_id]]);
                    }
                });
        }

        Schema::table('document_movements', function (Blueprint $table) {
            $table->dropForeign(['moved_from_box_id']);
            $table->dropForeign(['moved_to_box_id']);
            $table->dropIndex(['moved_from_box_id']);
            $table->dropIndex(['moved_to_box_id']);
            $table->dropColumn(['moved_from_box_id', 'moved_to_box_id']);
        });
    }
};
