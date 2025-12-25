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
            // English
            [
                'locale' => 'en',
                'key' => 'pages.users_page.user_modal.set_password_now',
                'value' => 'Set password for this user now',
            ],
            [
                'locale' => 'en',
                'key' => 'pages.users_page.user_modal.set_password_now_hint',
                'value' => 'If unchecked, the user will receive an email with a link to set their own password.',
            ],
            // French
            [
                'locale' => 'fr',
                'key' => 'pages.users_page.user_modal.set_password_now',
                'value' => 'Définir le mot de passe pour cet utilisateur maintenant',
            ],
            [
                'locale' => 'fr',
                'key' => 'pages.users_page.user_modal.set_password_now_hint',
                'value' => 'Si non coché, l\'utilisateur recevra un email avec un lien pour définir son propre mot de passe.',
            ],
            // Arabic
            [
                'locale' => 'ar',
                'key' => 'pages.users_page.user_modal.set_password_now',
                'value' => 'تعيين كلمة المرور لهذا المستخدم الآن',
            ],
            [
                'locale' => 'ar',
                'key' => 'pages.users_page.user_modal.set_password_now_hint',
                'value' => 'إذا لم يتم التحديد، سيتلقى المستخدم بريدًا إلكترونيًا يحتوي على رابط لتعيين كلمة المرور الخاصة به.',
            ],
        ];

        foreach ($translations as $translation) {
            UiTranslation::updateOrCreate(
                [
                    'locale' => $translation['locale'],
                    'key' => $translation['key'],
                ],
                [
                    'value' => $translation['value'],
                ]
            );
        }

        $this->command->info('User password checkbox translations added successfully.');
    }
}
