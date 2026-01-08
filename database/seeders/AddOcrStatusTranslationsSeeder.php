<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddOcrStatusTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Add OCR status translation keys
        UiTranslation::updateOrCreate(
            ['key' => 'pages.ocr.status.completed'],
            [
                'en_text' => 'Completed',
                'fr_text' => 'Terminé',
                'ar_text' => 'مكتمل',
            ]
        );

        UiTranslation::updateOrCreate(
            ['key' => 'pages.ocr.status.pending'],
            [
                'en_text' => 'Pending',
                'fr_text' => 'En attente',
                'ar_text' => 'قيد الانتظار',
            ]
        );

        $this->command->info('OCR status translations added successfully.');
    }
}
