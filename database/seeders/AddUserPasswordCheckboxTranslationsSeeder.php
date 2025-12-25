<?php

namespace Database\Seeders;

use App\Models\UiTranslation;
use Illuminate\Database\Seeder;

class AddUserPasswordCheckboxTranslationsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $translations = [
            [
                'key' => 'pages.users_page.user_modal.set_password_now',
                'en_text' => 'Set password for this user now',
                'fr_text' => 'Définir le mot de passe pour cet utilisateur maintenant',
                'ar_text' => 'تعيين كلمة المرور لهذا المستخدم الآن',
            ],
            [
                'key' => 'pages.users_page.user_modal.set_password_now_hint',
                'en_text' => 'If unchecked, the user will receive an email with a link to set their own password.',
                'fr_text' => 'Si non coché, l\'utilisateur recevra un email avec un lien pour définir son propre mot de passe.',
                'ar_text' => 'إذا لم يتم التحديد، سيتلقى المستخدم بريدًا إلكترونيًا يحتوي على رابط لتعيين كلمة المرور الخاصة به.',
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

        $this->command->info('User password checkbox translations added successfully.');
    }
}
