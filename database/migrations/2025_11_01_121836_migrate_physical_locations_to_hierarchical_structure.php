<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\PhysicalLocation;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Migrates flat physical_locations table to hierarchical structure
     */
    public function up(): void
    {
        // Create mapping table to store old_id -> new_box_id relationship
        Schema::create('physical_location_migration_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('old_physical_location_id');
            $table->unsignedBigInteger('new_box_id');
            $table->timestamps();
            
            $table->index('old_physical_location_id');
            $table->index('new_box_id');
        });

        // Get all existing physical locations
        $oldLocations = PhysicalLocation::all();

        if ($oldLocations->isEmpty()) {
            return; // Nothing to migrate
        }

        // Group by room, row, shelf, box to build hierarchy
        $hierarchy = [];
        foreach ($oldLocations as $location) {
            $roomName = $location->room ?? 'Default Room';
            $rowName = $location->row ?? 'Default Row';
            $shelfName = $location->shelf ?? 'Default Shelf';
            $boxName = $location->box ?? 'Default Box';

            if (!isset($hierarchy[$roomName])) {
                $hierarchy[$roomName] = [];
            }
            if (!isset($hierarchy[$roomName][$rowName])) {
                $hierarchy[$roomName][$rowName] = [];
            }
            if (!isset($hierarchy[$roomName][$rowName][$shelfName])) {
                $hierarchy[$roomName][$rowName][$shelfName] = [];
            }
            if (!isset($hierarchy[$roomName][$rowName][$shelfName][$boxName])) {
                $hierarchy[$roomName][$rowName][$shelfName][$boxName] = [
                    'description' => $location->description,
                    'old_id' => $location->id,
                ];
            }
        }

        // Build hierarchical structure and create mapping
        foreach ($hierarchy as $roomName => $rows) {
            // Create or get room
            $roomId = DB::table('rooms')->where('name', $roomName)->value('id');
            if (!$roomId) {
                $roomId = DB::table('rooms')->insertGetId([
                    'name' => $roomName,
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($rows as $rowName => $shelves) {
                // Create or get row
                $rowId = DB::table('rows')->where('room_id', $roomId)->where('name', $rowName)->value('id');
                if (!$rowId) {
                    $rowId = DB::table('rows')->insertGetId([
                        'room_id' => $roomId,
                        'name' => $rowName,
                        'description' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                foreach ($shelves as $shelfName => $boxes) {
                    // Create or get shelf
                    $shelfId = DB::table('shelves')->where('row_id', $rowId)->where('name', $shelfName)->value('id');
                    if (!$shelfId) {
                        $shelfId = DB::table('shelves')->insertGetId([
                            'row_id' => $rowId,
                            'name' => $shelfName,
                            'description' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    foreach ($boxes as $boxName => $boxData) {
                        // Create or get box
                        $boxId = DB::table('boxes')->where('shelf_id', $shelfId)->where('name', $boxName)->value('id');
                        if (!$boxId) {
                            $boxId = DB::table('boxes')->insertGetId([
                                'shelf_id' => $shelfId,
                                'name' => $boxName,
                                'description' => $boxData['description'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        // Store mapping
                        DB::table('physical_location_migration_map')->insert([
                            'old_physical_location_id' => $boxData['old_id'],
                            'new_box_id' => $boxId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     * Note: This doesn't restore the old flat structure, just cleans up new structure
     */
    public function down(): void
    {
        // Drop mapping table
        Schema::dropIfExists('physical_location_migration_map');

        // Clear hierarchical data
        DB::table('boxes')->truncate();
        DB::table('shelves')->truncate();
        DB::table('rows')->truncate();
        DB::table('rooms')->truncate();
    }
};
