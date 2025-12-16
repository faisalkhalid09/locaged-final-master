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
        Schema::create('boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shelf_id')->constrained('shelves')->onDelete('cascade');
            $table->string('name'); // e.g., "Box 1", "Box A"
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['shelf_id', 'name']);
            $table->unique(['shelf_id', 'name']); // Prevent duplicate box names within same shelf
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boxes');
    }
};
