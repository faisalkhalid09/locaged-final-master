<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddUploadSubmittingTranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translation = [
            'key' => 'pages.upload.submitting',
            'en_text' => 'Submitting...',
            'fr_text' => 'Soumission en cours...',
            'ar_text' => 'جارٍ الإرسال...',
        ];

        UiTranslation::updateOrCreate(
            ['key' => $translation['key']],
            [
                'en_text' => $translation['en_text'],
                'fr_text' => $translation['fr_text'],
                'ar_text' => $translation['ar_text'],
            ]
        );

        $this->command->info('Upload submitting translation added successfully.');
    }
}
