<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddApprovalReminderTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            [
                'key' => 'Approval Reminder',
                'en_text' => 'Approval Reminder',
                'fr_text' => "Rappel d'approbation",
                'ar_text' => 'تذكير بالموافقة',
            ],
            [
                'key' => 'This document has been pending approval for a week.',
                'en_text' => 'The document ":title" has been pending approval for a week.',
                'fr_text' => 'Le document ":title" est en attente d\'approbation depuis une semaine.',
                'ar_text' => 'المستند ":title" في انتظار الموافقة منذ أسبوع.',
            ],
            [
                'key' => 'This document has been pending approval for a month.',
                'en_text' => 'The document ":title" has been pending approval for a month.',
                'fr_text' => 'Le document ":title" est en attente d\'approbation depuis un mois.',
                'ar_text' => 'المستند ":title" في انتظار الموافقة منذ شهر.',
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

        $this->command->info('Approval reminder translations seeding completed');
    }
}
