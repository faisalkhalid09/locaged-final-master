<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UiTranslation;

class AddDeletedChartTranslationSeeder extends Seeder
{
    public function run()
    {
        UiTranslation::updateOrCreate(
            ['key' => 'pages.chart.deleted'],
            [
                'en_text' => 'Deleted',
                'fr_text' => 'Supprimé',
                'ar_text' => 'محذوف',
            ]
        );
    }
}
