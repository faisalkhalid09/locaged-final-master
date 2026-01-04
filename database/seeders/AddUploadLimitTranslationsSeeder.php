<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddUploadLimitTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            [
                'key' => 'pages.upload.max_file_size',
                'en_text' => 'Maximum file size: :size MB per file',
                'fr_text' => 'Taille maximale du fichier : :size Mo par fichier',
                'ar_text' => 'الحد الأقصى لحجم الملف: :size ميجابايت لكل ملف',
            ],
            [
                'key' => 'pages.upload.max_batch_info',
                'en_text' => 'Maximum :count files per upload (Total: :size MB)',
                'fr_text' => 'Maximum :count fichiers par téléchargement (Total : :size Mo)',
                'ar_text' => 'الحد الأقصى :count ملفات لكل تحميل (الإجمالي: :size ميجابايت)',
            ],
            [
                'key' => 'pages.upload.upload_blocked',
                'en_text' => 'Upload Blocked',
                'fr_text' => 'Téléchargement Bloqué',
                'ar_text' => 'التحميل محظور',
            ],
            [
                'key' => 'actions.ok',
                'en_text' => 'OK',
                'fr_text' => 'OK',
                'ar_text' => 'حسناً',
            ],
            [
                'key' => 'pages.upload.file_too_large',
                'en_text' => 'File ":filename" exceeds the maximum size of :max MB',
                'fr_text' => 'Le fichier ":filename" dépasse la taille maximale de :max Mo',
                'ar_text' => 'الملف ":filename" يتجاوز الحد الأقصى للحجم :max ميجابايت',
            ],
            [
                'key' => 'pages.upload.batch_too_large',
                'en_text' => 'Total batch size exceeds :max MB. Please upload fewer files.',
                'fr_text' => 'La taille totale du lot dépasse :max Mo. Veuillez télécharger moins de fichiers.',
                'ar_text' => 'حجم الدفعة الإجمالي يتجاوز :max ميجابايت. يرجى تحميل ملفات أقل.',
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
            
            $this->command->info("✓ Added: {$translation['key']}");
        }

        $this->command->info('Upload limit translations seeding completed');
    }
}

