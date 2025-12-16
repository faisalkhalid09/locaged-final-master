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
        Schema::create('shelves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('row_id')->constrained('rows')->onDelete('cascade');
            $table->string('name'); // e.g., "Shelf 1", "Shelf B"
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['row_id', 'name']);
            $table->unique(['row_id', 'name']); // Prevent duplicate shelf names within same row
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shelves');
    }
};
