<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $testUserEmail = 'test@example.com';
        if (!User::where('email', $testUserEmail)->exists()) {
            User::factory()->create([
                'full_name' => 'Test User',
                'username' => $testUserEmail,
                'email' => $testUserEmail,
            ]);
        }

        $this->call([
            \Database\Seeders\RolesAndPermissionsSeeder::class,
            \Database\Seeders\AddActivityTabsTranslationsSeeder::class,
            \Database\Seeders\AddApprovalReminderTranslationsSeeder::class,
            \Database\Seeders\AddDayNamesTranslationsSeeder::class,
            \Database\Seeders\AddDeletedChartTranslationSeeder::class,
            \Database\Seeders\AddDeletionLogPageTranslationsSeeder::class,
            \Database\Seeders\AddDeletionLogPdfTranslationsSeeder::class,
            \Database\Seeders\AddDonutChartTranslationsSeeder::class,
            \Database\Seeders\AddExpiredChartLabelTranslationSeeder::class,
            \Database\Seeders\AddExpiredStatusTranslationSeeder::class,
            \Database\Seeders\AddPendingDocumentsHeadingTranslationSeeder::class,
            \Database\Seeders\AddPendingDocumentsPageHeadingSeeder::class,
            \Database\Seeders\AddPhysicalLocationBoxErrorTranslationsSeeder::class,
            \Database\Seeders\AddPostponeFeatureTranslationsSeeder::class,
            \Database\Seeders\AddServiceSelectionTranslationsSeeder::class,
            \Database\Seeders\AddUploadLimitTranslationsSeeder::class,
            \Database\Seeders\AddUploadSubmittingTranslationSeeder::class,
            \Database\Seeders\AddUserPasswordCheckboxTranslationsSeeder::class,
        ]);
    }
}
