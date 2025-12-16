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
        // Get the first department (or create a default one if none exists)
        $defaultDepartment = DB::table('departments')->first();
        
        if (!$defaultDepartment) {
            // Create a default department if none exists
            $defaultDepartmentId = DB::table('departments')->insertGetId([
                'name' => 'Default Department',
                'description' => 'Default department for existing categories',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $defaultDepartmentId = $defaultDepartment->id;
        }
        
        // Assign all existing categories to the default department
        DB::table('categories')
            ->whereNull('department_id')
            ->update(['department_id' => $defaultDepartmentId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove department assignments from categories
        DB::table('categories')->update(['department_id' => null]);
    }
};
