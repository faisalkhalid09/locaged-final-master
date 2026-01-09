<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddRoomDeletionTranslationsSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure UTF-8 encoding
        DB::statement('SET NAMES utf8mb4');
        
        $translations = [
            [
                'key' => 'messages.physical_location.room_deleted',
                'fr' => 'Salle et tout son contenu supprimés avec succès.',
                'en' => 'Room and all its contents deleted successfully.',
                'ar' => 'تم حذف الغرفة وجميع محتوياتها بنجاح.',
            ],
            [
                'key' => 'messages.physical_location.room_contains_documents',
                'fr' => 'Impossible de supprimer la salle car elle contient des documents. Veuillez les déplacer ou les supprimer d\'abord.',
                'en' => 'Cannot delete room because it contains documents. Please move or delete them first.',
                'ar' => 'لا يمكن حذف الغرفة لأنها تحتوي على مستندات. يرجى نقلها أو حذفها أولاً.',
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

        $this->command->info('Room deletion translations seeding completed');
    }
}
