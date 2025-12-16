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
        // Migrate existing user department assignments to the pivot table
        DB::table('users')
            ->whereNotNull('department_id')
            ->get()
            ->each(function ($user) {
                // Check if the record already exists to avoid duplicates
                $exists = DB::table('department_user')
                    ->where('user_id', $user->id)
                    ->where('department_id', $user->department_id)
                    ->exists();
                
                if (!$exists) {
                    DB::table('department_user')->insert([
                        'user_id' => $user->id,
                        'department_id' => $user->department_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear the pivot table on rollback
        DB::table('department_user')->truncate();
    }
};
