<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddPhysicalLocationBoxErrorTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure UTF-8 encoding
        DB::statement('SET NAMES utf8mb4');
        
        $translations = [
            [
                'key' => 'errors.physical_location.box_name_duplicate',
                'fr' => 'Une boîte avec ce nom existe déjà sur cette étagère. Veuillez choisir un nom différent.',
                'en' => 'A box with this name already exists on this shelf. Please choose a different name.',
                'ar' => 'صندوق بهذا الاسم موجود بالفعل على هذا الرف. يرجى اختيار اسم مختلف.',
            ],
            [
                'key' => 'errors.physical_location.box_update_failed',
                'fr' => 'Échec de la mise à jour de la boîte. Veuillez réessayer.',
                'en' => 'Failed to update box. Please try again.',
                'ar' => 'فشل تحديث الصندوق. يرجى المحاولة مرة أخرى.',
            ],
        ];

        foreach ($translations as $translation) {
            try {
                UiTranslation::updateOrCreate(
                    ['key' => $translation['key']],
                    [
                        'fr' => $translation['fr'],
                        'en' => $translation['en'],
                        'ar' => $translation['ar'],
                    ]
                );
                $this->command->info("✓ Added: {$translation['key']}");
            } catch (\Exception $e) {
                $this->command->error("✗ Failed to add: {$translation['key']} - " . $e->getMessage());
            }
        }

        $this->command->info('Physical location box error translations seeding completed');
    }
}
