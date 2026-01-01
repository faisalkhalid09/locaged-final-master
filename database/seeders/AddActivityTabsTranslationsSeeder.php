<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddActivityTabsTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $translations = [
            [
                'key' => 'pages.activity.tabs.document_activity',
                'en_text' => 'Document Activity',
                'fr_text' => 'Activité des documents',
                'ar_text' => 'نشاط الوثائق',
            ],
            [
                'key' => 'pages.activity.tabs.authentication_activity',
                'en_text' => 'Authentication Activity',
                'fr_text' => 'Activité d\'authentification',
                'ar_text' => 'نشاط المصادقة',
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

        $this->command->info('Activity tabs translations added/updated successfully.');
    }
}
