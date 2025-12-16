<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('categories')
            ->whereNull('expiry_value')
            ->orWhereNull('expiry_unit')
            ->update([
                'expiry_value' => 3,
                'expiry_unit' => 'days',
            ]);
    }

    public function down(): void
    {
        // No-op: we won't revert defaults automatically
    }
};


