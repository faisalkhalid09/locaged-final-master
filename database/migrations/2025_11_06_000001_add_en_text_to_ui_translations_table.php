<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ui_translations', function (Blueprint $table) {
            $table->string('en_text', 255)->nullable()->after('key');
        });
    }

    public function down(): void
    {
        Schema::table('ui_translations', function (Blueprint $table) {
            $table->dropColumn('en_text');
        });
    }
};


