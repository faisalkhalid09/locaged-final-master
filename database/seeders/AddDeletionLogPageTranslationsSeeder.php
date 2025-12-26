<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddDeletionLogPageTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            // Stats Cards
            ['key' => 'pages.deletion_log.stats.total_deleted', 'en_text' => 'Total Deleted', 'fr_text' => 'Total supprimé', 'ar_text' => 'إجمالي المحذوفة'],
            ['key' => 'pages.deletion_log.stats.this_week', 'en_text' => 'This Week', 'fr_text' => 'Cette semaine', 'ar_text' => 'هذا الأسبوع'],
            ['key' => 'pages.deletion_log.stats.today', 'en_text' => 'Today', 'fr_text' => 'Aujourd\'hui', 'ar_text' => 'اليوم'],
            ['key' => 'pages.deletion_log.stats.current_page', 'en_text' => 'Current Page', 'fr_text' => 'Page actuelle', 'ar_text' => 'الصفحة الحالية'],
            
            // Filters
            ['key' => 'pages.deletion_log.filters.document', 'en_text' => 'Document', 'fr_text' => 'Document', 'ar_text' => 'وثيقة'],
            ['key' => 'pages.deletion_log.filters.search_placeholder', 'en_text' => 'Search...', 'fr_text' => 'Rechercher...', 'ar_text' => 'بحث...'],
            ['key' => 'pages.deletion_log.filters.creation_date', 'en_text' => 'Creation Date', 'fr_text' => 'Date de création', 'ar_text' => 'تاريخ الإنشاء'],
            ['key' => 'pages.deletion_log.filters.expiration', 'en_text' => 'Expiration', 'fr_text' => 'Expiration', 'ar_text' => 'انتهاء الصلاحية'],
            ['key' => 'pages.deletion_log.filters.deleted_at', 'en_text' => 'Deleted At', 'fr_text' => 'Supprimé le', 'ar_text' => 'تاريخ الحذف'],
            ['key' => 'pages.deletion_log.filters.deleted_by', 'en_text' => 'Deleted By', 'fr_text' => 'Supprimé par', 'ar_text' => 'حذف بواسطة'],
            ['key' => 'pages.deletion_log.filters.all_users', 'en_text' => 'All Users', 'fr_text' => 'Tous les utilisateurs', 'ar_text' => 'جميع المستخدمين'],
            ['key' => 'pages.deletion_log.filters.structure', 'en_text' => 'Structure', 'fr_text' => 'Structure', 'ar_text' => 'الهيكل'],
            ['key' => 'pages.deletion_log.filters.all', 'en_text' => 'All', 'fr_text' => 'Tous', 'ar_text' => 'الكل'],
            ['key' => 'pages.deletion_log.filters.reset', 'en_text' => 'Reset Filters', 'fr_text' => 'Réinitialiser les filtres', 'ar_text' => 'إعادة تعيين الفلاتر'],
            ['key' => 'pages.deletion_log.filters.per_page', 'en_text' => 'Per page', 'fr_text' => 'Par page', 'ar_text' => 'لكل صفحة'],
            ['key' => 'pages.deletion_log.filters.export', 'en_text' => 'Export', 'fr_text' => 'Exporter', 'ar_text' => 'تصدير'],
            
            // Table Headers
            ['key' => 'pages.deletion_log.table.document', 'en_text' => 'Document', 'fr_text' => 'Document', 'ar_text' => 'وثيقة'],
            ['key' => 'pages.deletion_log.table.creation_date', 'en_text' => 'Creation Date', 'fr_text' => 'Date de création', 'ar_text' => 'تاريخ الإنشاء'],
            ['key' => 'pages.deletion_log.table.expiration', 'en_text' => 'Expiration', 'fr_text' => 'Expiration', 'ar_text' => 'انتهاء الصلاحية'],
            ['key' => 'pages.deletion_log.table.deleted_at', 'en_text' => 'Deleted At', 'fr_text' => 'Supprimé le', 'ar_text' => 'تاريخ الحذف'],
            ['key' => 'pages.deletion_log.table.deleted_by', 'en_text' => 'Deleted By', 'fr_text' => 'Supprimé par', 'ar_text' => 'حذف بواسطة'],
            ['key' => 'pages.deletion_log.table.structure', 'en_text' => 'Structure', 'fr_text' => 'Structure', 'ar_text' => 'الهيكل'],
            ['key' => 'pages.deletion_log.table.pdf', 'en_text' => 'PDF', 'fr_text' => 'PDF', 'ar_text' => 'PDF'],
            
            // Other
            ['key' => 'pages.deletion_log.document_deleted', 'en_text' => '(Document deleted)', 'fr_text' => '(Document supprimé)', 'ar_text' => '(تم حذف الوثيقة)'],
            ['key' => 'pages.deletion_log.export_pdf', 'en_text' => 'Export to PDF', 'fr_text' => 'Exporter en PDF', 'ar_text' => 'تصدير إلى PDF'],
            ['key' => 'pages.deletion_log.no_documents', 'en_text' => 'No permanently deleted documents found.', 'fr_text' => 'Aucun document définitivement supprimé trouvé.', 'ar_text' => 'لم يتم العثور على وثائق محذوفة نهائيا.'],
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

        $this->command->info('Deletion log page translations added successfully.');
    }
}
