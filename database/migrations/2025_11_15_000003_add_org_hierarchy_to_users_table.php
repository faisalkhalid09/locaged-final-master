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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('sub_department_id')
                ->nullable()
                ->after('active')
                ->constrained('sub_departments')
                ->nullOnDelete();

            $table->foreignId('service_id')
                ->nullable()
                ->after('sub_department_id')
                ->constrained('services')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['sub_department_id']);
            $table->dropColumn('sub_department_id');

            $table->dropForeign(['service_id']);
            $table->dropColumn('service_id');
        });
    }
};
