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
        // First, assign users without departments to a default department
        $usersWithoutDepartments = DB::table('users')
            ->whereNotIn('id', function ($query) {
                $query->select('user_id')
                    ->from('department_user');
            })
            ->get();

        if ($usersWithoutDepartments->count() > 0) {
            // Get the first available department or create a default one
            $defaultDepartment = DB::table('departments')->first();
            
            if (!$defaultDepartment) {
                // Create a default department if none exists
                $defaultDepartmentId = DB::table('departments')->insertGetId([
                    'name' => 'Default Structure',
                    'description' => 'Default structure for users without assigned structures',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $defaultDepartmentId = $defaultDepartment->id;
            }

            // Assign all users without departments to the default department
            foreach ($usersWithoutDepartments as $user) {
                DB::table('department_user')->insert([
                    'user_id' => $user->id,
                    'department_id' => $defaultDepartmentId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Now remove the old department_id column from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the department_id column
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
        });

        // Migrate data back from pivot table to department_id
        $userDepartments = DB::table('department_user')->get();
        foreach ($userDepartments as $userDept) {
            DB::table('users')
                ->where('id', $userDept->user_id)
                ->update(['department_id' => $userDept->department_id]);
        }
    }
};