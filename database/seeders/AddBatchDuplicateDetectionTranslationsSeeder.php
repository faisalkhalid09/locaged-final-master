<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddBatchDuplicateDetectionTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $translations = [
            // Batch duplicate detection intro
            [
                'key' => 'pages.upload.batch_duplicate_warning_intro',
                'en' => 'The following files match existing documents in the system:',
                'fr' => 'Les fichiers suivants correspondent à des documents existants dans le système :',
                'ar' => 'الملفات التالية تطابق مستندات موجودة في النظام:',
            ],
            // Review and modify button
            [
                'key' => 'pages.upload.review_and_modify',
                'en' => 'Review & Modify',
                'fr' => 'Réviser et Modifier',
                'ar' => 'مراجعة وتعديل',
            ],
            // Skip all duplicates button
            [
                'key' => 'pages.upload.skip_all_duplicates',
                'en' => 'Skip All with Duplicates',
                'fr' => 'Ignorer tous les doublons',
                'ar' => 'تخطي جميع التكرارات',
            ],
            // Upload all anyway button
            [
                'key' => 'pages.upload.upload_all_anyway',
                'en' => 'Upload All Anyway',
                'fr' => 'Téléverser tous quand même',
                'ar' => 'رفع الكل على أي حال',
            ],
        ];

        foreach ($translations as $translation) {
            // Check if translation already exists
            $existing = DB::table('ui_translations')->where('key', $translation['key'])->first();
            
            if (!$existing) {
                DB::table('ui_translations')->insert([
                    'key' => $translation['key'],
                    'en' => $translation['en'],
                    'fr' => $translation['fr'],
                    'ar' => $translation['ar'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                $this->command->info("Added translation: {$translation['key']}");
            } else {
                $this->command->info("Translation already exists: {$translation['key']}");
            }
        }
        
        $this->command->info('Batch duplicate detection translations seeded successfully!');
    }
}
