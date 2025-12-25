<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UiTranslation;

class AddDeletedChartTranslationSeeder extends Seeder
{
    public function run()
    {
        $translations = [
            [
                'key' => 'pages.chart.deleted',
                'locale' => 'en',
                'value' => 'Deleted',
            ],
            [
                'key' => 'pages.chart.deleted',
                'locale' => 'fr',
                'value' => 'Supprimé',
            ],
            [
                'key' => 'pages.chart.deleted',
                'locale' => 'ar',
                'value' => 'محذوف',
            ],
        ];

        foreach ($translations as $translation) {
            UiTranslation::updateOrCreate(
                ['key' => $translation['key'], 'locale' => $translation['locale']],
                ['value' => $translation['value']]
            );
        }
    }
}
