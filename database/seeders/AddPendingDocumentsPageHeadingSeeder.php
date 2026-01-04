<?php


namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddPendingDocumentsPageHeadingSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure UTF-8 encoding
        DB::statement('SET NAMES utf8mb4');
        
        $translations = [
            [
                'key' => 'pages.headings.pending_documents',
                'fr' => 'Documents en attente',
                'en' => 'Pending Documents',
                'ar' => 'الوثائق قيد الانتظار',
            ],
        ];

        foreach ($translations as $translation) {
            try {
                UiTranslation::updateOrCreate(
                    ['key' => $translation['key']],
                    [
                        'fr_text' => $translation['fr'],
                        'en_text' => $translation['en'],
                        'ar_text' => $translation['ar'],
                    ]
                );
                $this->command->info("✓ Added: {$translation['key']}");
            } catch (\Exception $e) {
                $this->command->error("✗ Failed to add: {$translation['key']} - " . $e->getMessage());
            }
        }

        $this->command->info('Pending Documents page heading translation seeding completed');
    }
}
