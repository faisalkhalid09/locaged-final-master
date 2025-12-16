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
        Schema::create('sub_department_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sub_department_id')->constrained('sub_departments')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'sub_department_id'], 'sub_department_user_unique');
        });

        // No backfill from legacy users.sub_department_id; pivot is the single source of truth now.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_department_user');
    }
};
