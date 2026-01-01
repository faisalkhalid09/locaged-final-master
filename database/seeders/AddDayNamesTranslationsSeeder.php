<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddDayNamesTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $translations = [
            [
                'key' => 'pages.chart.days.sun',
                'en_text' => 'Sun',
                'fr_text' => 'Dim',
                'ar_text' => 'الأحد',
            ],
            [
                'key' => 'pages.chart.days.mon',
                'en_text' => 'Mon',
                'fr_text' => 'Lun',
                'ar_text' => 'الاثنين',
            ],
            [
                'key' => 'pages.chart.days.tue',
                'en_text' => 'Tue',
                'fr_text' => 'Mar',
                'ar_text' => 'الثلاثاء',
            ],
            [
                'key' => 'pages.chart.days.wed',
                'en_text' => 'Wed',
                'fr_text' => 'Mer',
                'ar_text' => 'الأربعاء',
            ],
            [
                'key' => 'pages.chart.days.thu',
                'en_text' => 'Thu',
                'fr_text' => 'Jeu',
                'ar_text' => 'الخميس',
            ],
            [
                'key' => 'pages.chart.days.fri',
                'en_text' => 'Fri',
                'fr_text' => 'Ven',
                'ar_text' => 'الجمعة',
            ],
            [
                'key' => 'pages.chart.days.sat',
                'en_text' => 'Sat',
                'fr_text' => 'Sam',
                'ar_text' => 'السبت',
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

        $this->command->info('Day names translations added/updated successfully.');
    }
}
