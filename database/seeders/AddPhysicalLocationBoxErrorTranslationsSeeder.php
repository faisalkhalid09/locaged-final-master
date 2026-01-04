<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddPhysicalLocationBoxErrorTranslationsSeeder extends Seeder
{
    public function run(): void
    {
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
            UiTranslation::updateOrCreate(
                ['key' => $translation['key']],
                [
                    'fr' => $translation['fr'],
                    'en' => $translation['en'],
                    'ar' => $translation['ar'],
                ]
            );
        }

        $this->command->info('✓ Physical location box error translations added successfully');
    }
}
