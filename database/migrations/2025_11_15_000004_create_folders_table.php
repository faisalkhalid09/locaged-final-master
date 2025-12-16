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
        // In some environments the `folders` table was created manually or by an earlier
        // migration. Guard against that so this migration can still run and be marked
        // as executed without throwing "table already exists" errors.
        if (Schema::hasTable('folders')) {
            return;
        }

        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('name');

            // Hierarchy
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('folders')
                ->nullOnDelete();

            // Ownership / organization
            $table->foreignId('department_id')
                ->nullable()
                ->constrained('departments')
                ->nullOnDelete();

            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->enum('status', ['pending', 'approved', 'declined', 'archived', 'locked', 'unlocked'])
                ->default('pending');

            $table->timestamps();
            $table->softDeletes();

            // Index to speed up lookups when recreating folder trees
            $table->index(['parent_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
