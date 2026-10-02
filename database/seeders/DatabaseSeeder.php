<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        $name = env('ADMIN_NAME', 'Admin BFW');

        if (app()->environment('production')) {
            if (blank($email) || blank($password)) {
                throw new RuntimeException('Les variables ADMIN_EMAIL et ADMIN_PASSWORD doivent être définies avant le seed en production.');
            }

            if ($email === 'admin@beninfashionweek.com' && $password === 'changez-moi') {
                throw new RuntimeException('Les identifiants admin par défaut ne sont pas autorisés en production.');
            }
        }

        User::firstOrCreate(
            ['email' => $email ?: 'admin@beninfashionweek.com'],
            [
                'name' => $name,
                'password' => Hash::make($password ?: 'changez-moi'),
            ],
        );
    }
}
