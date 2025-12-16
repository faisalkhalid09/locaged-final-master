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
        Schema::create('document_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->enum('movement_type', ['storage', 'retrieval', 'transfer', 'destruction']);
            $table->foreignId('moved_from')->nullable()->constrained('physical_locations')->nullOnDelete();
            $table->foreignId('moved_to')->nullable()->constrained('physical_locations')->nullOnDelete();
            $table->foreignId('moved_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('moved_at', 6)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_movements');
    }
};
