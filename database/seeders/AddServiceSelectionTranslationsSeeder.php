<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddServiceSelectionTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            [
                'key' => 'pages.physical.fields.service',
                'en_text' => 'Service',
                'fr_text' => 'Service',
                'ar_text' => 'الخدمة',
            ],
            [
                'key' => 'pages.physical.selects.select_service',
                'en_text' => 'Select Service',
                'fr_text' => 'Sélectionner un service',
                'ar_text' => 'اختر الخدمة',
            ],
            [
                'key' => 'pages.physical.service_link_help',
                'en_text' => 'This box will be linked to the selected service',
                'fr_text' => 'Cette boîte sera liée au service sélectionné',
                'ar_text' => 'سيتم ربط هذا الصندوق بالخدمة المحددة',
            ],
            [
                'key' => 'pages.categories_form.select_department',
                'en_text' => 'Select Department',
                'fr_text' => 'Sélectionner un département',
                'ar_text' => 'اختر القسم',
            ],
            [
                'key' => 'pages.categories_form.select_service',
                'en_text' => 'Select Service',
                'fr_text' => 'Sélectionner un service',
                'ar_text' => 'اختر الخدمة',
            ],
        ];

        foreach ($translations as $translation) {
            UiTranslation::updateOrCreate(
                ['key' => $translation['key']],
                [
                    'en_text' => $translation['en_text'],
                    'fr_text' => $translation['fr_text'],
                    'ar_text' => $translation['ar_text'],
                ]
            );
        }

        $this->command->info('Service selection translations added successfully.');
    }
}
