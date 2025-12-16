<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Use raw SQL to avoid requiring doctrine/dbal for column changes
        try {
            DB::statement("ALTER TABLE ui_translations MODIFY ar_text VARCHAR(255) NULL");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE ui_translations MODIFY fr_text VARCHAR(255) NULL");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE ui_translations MODIFY en_text VARCHAR(255) NULL");
        } catch (\Throwable $e) {}
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE ui_translations MODIFY ar_text VARCHAR(255) NOT NULL");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE ui_translations MODIFY fr_text VARCHAR(255) NOT NULL");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE ui_translations MODIFY en_text VARCHAR(255) NOT NULL");
        } catch (\Throwable $e) {}
    }
};


