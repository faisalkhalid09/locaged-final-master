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
        Schema::create('workflow_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained('departments')->onDelete('cascade');
            $table->enum('from_status', ['received', 'reviewed', 'approved']);
            $table->enum('to_status', ['received', 'reviewed', 'approved']);
           // $table->foreignId('role_id')->constrained()->onDelete('cascade');

           // $table->unique(['department_id', 'from_status', 'to_status'], 'uniq_rule');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_rules');
    }
};
