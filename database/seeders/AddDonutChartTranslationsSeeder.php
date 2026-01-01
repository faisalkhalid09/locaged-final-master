<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddDonutChartTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        $translations = [
            [
                'key' => 'pages.dashboard.donut.departments_title',
                'en_text' => 'Documents by Department',
                'fr_text' => 'Documents par structure',
                'ar_text' => 'المستندات حسب الهيكل',
            ],
            [
                'key' => 'pages.dashboard.donut.no_documents',
                'en_text' => 'No documents found',
                'fr_text' => 'Aucun document trouvé',
                'ar_text' => 'لا توجد مستندات',
            ],
            [
                'key' => 'pages.dashboard.donut.no_data',
                'en_text' => 'No data available',
                'fr_text' => 'Aucune donnée disponible',
                'ar_text' => 'لا توجد بيانات متاحة',
            ],
            [
                'key' => 'pages.dashboard.donut.departments_click',
                'en_text' => 'Click on a department to view details',
                'fr_text' => 'Cliquez sur une structure pour afficher les détails',
                'ar_text' => 'انقر على الهيكل لعرض التفاصيل',
            ],
            [
                'key' => 'pages.dashboard.donut.sub_departments_title',
                'en_text' => 'Documents by Sub-Department',
                'fr_text' => 'Documents par département',
                'ar_text' => 'المستندات حسب القسم',
            ],
            [
                'key' => 'pages.dashboard.donut.sub_departments_click',
                'en_text' => 'Click on a sub-department to view details',
                'fr_text' => 'Cliquez sur un département pour afficher les détails',
                'ar_text' => 'انقر على القسم لعرض التفاصيل',
            ],
            [
                'key' => 'pages.dashboard.donut.services_title',
                'en_text' => 'Documents by Service',
                'fr_text' => 'Documents par service',
                'ar_text' => 'المستندات حسب الخدمة',
            ],
            [
                'key' => 'pages.dashboard.donut.services_click',
                'en_text' => 'Click on a service to view details',
                'fr_text' => 'Cliquez sur un service pour afficher les détails',
                'ar_text' => 'انقر على الخدمة لعرض التفاصيل',
            ],
            [
                'key' => 'pages.dashboard.donut.services_empty',
                'en_text' => 'No services found',
                'fr_text' => 'Aucun service trouvé',
                'ar_text' => 'لا توجد خدمات',
            ],
            [
                'key' => 'pages.dashboard.donut.categories_title',
                'en_text' => 'Documents by Category',
                'fr_text' => 'Documents par catégorie',
                'ar_text' => 'المستندات حسب الفئة',
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

        $this->command->info('Donut chart translations added/updated successfully.');
    }
}
