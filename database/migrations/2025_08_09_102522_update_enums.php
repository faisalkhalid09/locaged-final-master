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
        Schema::table('document_status_history', function (Blueprint $table) {
            $table->enum('from_status', ['pending', 'declined', 'approved','locked','unlocked','moved','archived','destroyed'])->change();
            $table->enum('to_status', ['pending', 'declined', 'approved','locked','unlocked','moved','archived','destroyed'])->change();
        });

        Schema::table('workflow_rules', function (Blueprint $table) {

            $table->enum('from_status', ['pending', 'declined', 'approved','locked','unlocked','moved','archived','destroyed'])->change();
            $table->enum('to_status', ['pending', 'declined', 'approved','locked','unlocked','moved','archived','destroyed'])->change();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');

            $table->unique(['department_id', 'from_status', 'to_status'], 'uniq_rule');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_status_history', function (Blueprint $table) {
            $table->enum('from_status', ['received', 'reviewed', 'approved'])->change();
            $table->enum('to_status', ['received', 'reviewed', 'approved'])->change();
        });

        Schema::table('workflow_rules', function (Blueprint $table) {
            $table->enum('from_status', ['received', 'reviewed', 'approved'])->change();
            $table->enum('to_status', ['received', 'reviewed', 'approved'])->change();
            $table->dropUnique('uniq_rule');

            $table->dropForeign(['role_id']);

            $table->dropColumn('role_id');
        });

    }
};
