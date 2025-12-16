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
        // Update existing users with 'en' or null locale to 'fr'
        DB::table('users')
            ->where(function($query) {
                $query->whereNull('locale')
                      ->orWhere('locale', 'en');
            })
            ->update(['locale' => 'fr']);

        // Change the default locale in the database schema
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale')->default('fr')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Update existing users with 'fr' locale back to 'en'
        DB::table('users')
            ->where('locale', 'fr')
            ->update(['locale' => 'en']);

        // Change the default locale back to 'en'
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale')->default('en')->change();
        });
    }
};
