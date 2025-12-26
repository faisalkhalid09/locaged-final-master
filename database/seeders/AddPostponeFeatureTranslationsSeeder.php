<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddPostponeFeatureTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            // Postpone Button & Delete Button
            ['key' => 'pages.destructions.postpone_button', 'en_text' => 'Postpone', 'fr_text' => 'Reporter', 'ar_text' => 'تأجيل'],
            ['key' => 'pages.destructions.delete_button', 'en_text' => 'Delete', 'fr_text' => 'Supprimer', 'ar_text' => 'حذف'],
            
            // Postpone Modal
            ['key' => 'pages.destructions.postpone.title', 'en_text' => 'Postpone Expiration', 'fr_text' => 'Reporter l\'expiration', 'ar_text' => 'تأجيل انتهاء الصلاحية'],
            ['key' => 'pages.destructions.postpone.description', 'en_text' => 'Select the time period to extend the expiration date. The document will be restored to active status.', 'fr_text' => 'Sélectionnez la période pour prolonger la date d\'expiration. Le document sera restauré au statut actif.', 'ar_text' => 'حدد الفترة الزمنية لتمديد تاريخ انتهاء الصلاحية. سيتم استعادة المستند إلى الحالة النشطة.'],
            ['key' => 'pages.destructions.postpone.confirm_button', 'en_text' => 'Confirm', 'fr_text' => 'Confirmer', 'ar_text' => 'تأكيد'],
            
            // Time Unit Fields
            ['key' => 'pages.destructions.postpone.time_unit', 'en_text' => 'Time Unit', 'fr_text' => 'Unité de temps', 'ar_text' => 'وحدة الوقت'],
            ['key' => 'pages.destructions.postpone.days', 'en_text' => 'Days', 'fr_text' => 'Jours', 'ar_text' => 'أيام'],
            ['key' => 'pages.destructions.postpone.weeks', 'en_text' => 'Weeks', 'fr_text' => 'Semaines', 'ar_text' => 'أسابيع'],
            ['key' => 'pages.destructions.postpone.months', 'en_text' => 'Months', 'fr_text' => 'Mois', 'ar_text' => 'أشهر'],
            ['key' => 'pages.destructions.postpone.years', 'en_text' => 'Years', 'fr_text' => 'Années', 'ar_text' => 'سنوات'],
            
            // Amount Field
            ['key' => 'pages.destructions.postpone.amount_label', 'en_text' => 'Amount', 'fr_text' => 'Quantité', 'ar_text' => 'الكمية'],
            ['key' => 'pages.destructions.postpone.amount_placeholder', 'en_text' => 'Amount (1-1000)', 'fr_text' => 'Quantité (1-1000)', 'ar_text' => 'الكمية (1-1000)'],
            
            // No Documents Message
            ['key' => 'pages.destructions.no_expired_documents', 'en_text' => 'No expired documents found.', 'fr_text' => 'Aucun document expiré trouvé.', 'ar_text' => 'لم يتم العثور على وثائق منتهية الصلاحية.'],
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

        $this->command->info('Postpone feature translations added successfully.');
    }
}
