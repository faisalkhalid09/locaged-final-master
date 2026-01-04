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
        $translations = [
            [
                'key' => 'pages.dashboard.pending_documents',
                'en_text' => 'Pending Documents',
                'fr_text' => 'Documents en attente',
                'ar_text' => 'المستندات المعلقة',
            ],
            [
                'key' => 'pages.dashboard.approvals',
                'en_text' => 'Approvals',
                'fr_text' => 'Approbations',
                'ar_text' => 'الموافقات',
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

        $this->command->info('Pending Documents and Approvals heading translations added successfully.');
    }
}
