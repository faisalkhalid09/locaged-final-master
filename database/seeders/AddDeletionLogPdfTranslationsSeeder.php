<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddDeletionLogPdfTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            // PDF Title and Headers
            [
                'key' => 'pdf.deletion_log.title',
                'en_text' => 'Deletion Log',
                'fr_text' => 'Journal de suppression',
                'ar_text' => 'سجل الحذف',
            ],
            [
                'key' => 'pdf.deletion_log.document',
                'en_text' => 'Document',
                'fr_text' => 'Document',
                'ar_text' => 'وثيقة',
            ],
            [
                'key' => 'pdf.deletion_log.heading',
                'en_text' => 'Deletion Log Record',
                'fr_text' => 'Enregistrement du journal de suppression',
                'ar_text' => 'سجل المستند المحذوف',
            ],
            [
                'key' => 'pdf.deletion_log.generated_on',
                'en_text' => 'Generated on',
                'fr_text' => 'Généré le',
                'ar_text' => 'تم الإنشاء في',
            ],
            
            // PDF Field Labels
            [
                'key' => 'pdf.deletion_log.document_title',
                'en_text' => 'Document Title',
                'fr_text' => 'Titre du document',
                'ar_text' => 'عنوان الوثيقة',
            ],
            [
                'key' => 'pdf.deletion_log.document_deleted',
                'en_text' => '(Document deleted)',
                'fr_text' => '(Document supprimé)',
                'ar_text' => '(تم حذف الوثيقة)',
            ],
            [
                'key' => 'pdf.deletion_log.document_id',
                'en_text' => 'Document ID',
                'fr_text' => 'ID du document',
                'ar_text' => 'معرف الوثيقة',
            ],
            [
                'key' => 'pdf.deletion_log.creation_date',
                'en_text' => 'Creation Date',
                'fr_text' => 'Date de création',
                'ar_text' => 'تاريخ الإنشاء',
            ],
            [
                'key' => 'pdf.deletion_log.expiration_date',
                'en_text' => 'Expiration Date',
                'fr_text' => 'Date d\'expiration',
                'ar_text' => 'تاريخ الانتهاء',
            ],
            [
                'key' => 'pdf.deletion_log.deleted_at',
                'en_text' => 'Deleted At',
                'fr_text' => 'Supprimé le',
                'ar_text' => 'تاريخ الحذف',
            ],
            [
                'key' => 'pdf.deletion_log.deleted_by',
                'en_text' => 'Deleted By',
                'fr_text' => 'Supprimé par',
                'ar_text' => 'تم الحذف بواسطة',
            ],
            [
                'key' => 'pdf.deletion_log.structure',
                'en_text' => 'Structure',
                'fr_text' => 'Structure',
                'ar_text' => 'الهيكل',
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

        $this->command->info('Deletion log PDF translations added successfully.');
    }
}
