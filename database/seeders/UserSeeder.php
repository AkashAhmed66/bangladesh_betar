<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * M01 — one staff account per role plus sample public listeners.
 * Every seeded account uses password: 123456
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('123456');

        $staff = [
            ['Super Admin', 'admin@betar.gov.bd', 'Super Administrator'],
            ['Rafiq Ahmed', 'archive.admin@betar.gov.bd', 'Archive Administrator'],
            ['Salma Khatun', 'archivist@betar.gov.bd', 'Archivist'],
            ['Kamal Hossain', 'editor@betar.gov.bd', 'Audio Editor'],
            ['Nasrin Sultana', 'producer@betar.gov.bd', 'Programme Producer'],
            ['Tanvir Islam', 'podcast@betar.gov.bd', 'Podcast Manager'],
            ['Farida Yasmin', 'music@betar.gov.bd', 'Music Library Manager'],
            ['Imran Chowdhury', 'curator@betar.gov.bd', 'Content Curator'],
            ['Shirin Akter', 'moderator@betar.gov.bd', 'Moderator'],
            ['Habibur Rahman', 'ads@betar.gov.bd', 'Advertisement Manager'],
            ['Rehana Parvin', 'copyright@betar.gov.bd', 'Copyright Officer'],
            ['Director General', 'approver@betar.gov.bd', 'Approver'],
            ['Dr. Anisur Rahman', 'researcher@betar.gov.bd', 'Researcher'],
            ['Nabila Chowdhury', 'ai-reviewer@betar.gov.bd', 'AI Reviewer'],
        ];

        foreach ($staff as [$name, $email, $role]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'user_type' => 'staff',
                    'password' => $password,
                    'status' => 'active',
                    'locale' => 'en',
                    'email_verified_at' => now(),
                    'tos_accepted_at' => now(),
                    'tos_version' => '1.0',
                ],
            );
            $user->syncRoles([$role]);
        }

        $listeners = [
            ['Arif Hasan', 'listener1@example.com'],
            ['Mitu Rani Das', 'listener2@example.com'],
            ['Jewel Mia', 'listener3@example.com'],
            ['Sadia Afrin', 'listener4@example.com'],
            ['Rasel Ahmed', 'listener5@example.com'],
        ];

        foreach ($listeners as [$name, $email]) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'user_type' => 'listener',
                    'password' => $password,
                    'status' => 'active',
                    'locale' => 'bn',
                    'email_verified_at' => now(),
                    'tos_accepted_at' => now(),
                    'tos_version' => '1.0',
                ],
            );
            $user->syncRoles(['Listener']);
        }

        $this->command?->info('Users: '.count($staff).' staff + '.count($listeners).' listeners (password: 123456)');
    }
}
