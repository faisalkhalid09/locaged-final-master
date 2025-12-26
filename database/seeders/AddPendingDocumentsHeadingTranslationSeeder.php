<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddPendingDocumentsHeadingTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translation = [
            'key' => 'pages.dashboard.pending_documents',
            'en_text' => 'Pending Documents',
            'fr_text' => 'Documents en attente',
            'ar_text' => 'المستندات المعلقة',
        ];

        UiTranslation::updateOrCreate(
            ['key' => $translation['key']],
            [
                'en_text' => $translation['en_text'],
                'fr_text' => $translation['fr_text'],
                'ar_text' => $translation['ar_text'],
            ]
        );

        $this->command->info('Pending Documents heading translation added successfully.');
    }
}
