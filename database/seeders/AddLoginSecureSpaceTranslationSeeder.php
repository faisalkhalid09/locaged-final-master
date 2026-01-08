<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddLoginSecureSpaceTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update the login page tagline
        UiTranslation::updateOrCreate(
            ['key' => 'auth.ui.secure_space'],
            [
                'en_text' => 'Gestion éléctronique documentaire.',
                'fr_text' => 'Gestion éléctronique documentaire.',
                'ar_text' => 'إدارة إلكترونية للوثائق.',
            ]
        );

        $this->command->info('Login page secure space translation updated successfully.');
    }
}
