<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddExpiredStatusTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translation = [
            'key' => 'pages.documents.status.expired',
            'en_text' => 'Expired',
            'fr_text' => 'Expiré',
            'ar_text' => 'منتهية الصلاحية',
        ];

        UiTranslation::updateOrCreate(
            ['key' => $translation['key']],
            [
                'en_text' => $translation['en_text'],
                'fr_text' => $translation['fr_text'],
                'ar_text' => $translation['ar_text'],
            ]
        );

        $this->command->info('Expired status translation added successfully.');
    }
}
