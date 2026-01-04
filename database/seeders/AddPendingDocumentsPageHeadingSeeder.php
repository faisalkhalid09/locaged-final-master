<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddPendingDocumentsPageHeadingSeeder extends Seeder
{
    public function run(): void
    {
        $translations = [
            [
                'key' => 'pages.headings.pending_documents',
                'fr' => 'Documents en attente',
                'en' => 'Pending Documents',
                'ar' => 'الوثائق قيد الانتظار',
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

        $this->command->info('✓ Pending Documents page heading translation added successfully');
    }
}
