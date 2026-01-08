<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddLoginSecureSpaceTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update the login page tagline
        DB::table('ui_translations')
            ->updateOrInsert(
                ['key' => 'auth.ui.secure_space'],
                [
                    'en' => 'Gestion éléctronique documentaire.',
                    'fr' => 'Gestion éléctronique documentaire.',
                    'ar' => 'إدارة إلكترونية للوثائق.',
                    'updated_at' => now(),
                ]
            );

        $this->command->info('Login page secure space translation updated successfully.');
    }
}
