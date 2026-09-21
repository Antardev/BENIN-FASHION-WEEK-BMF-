<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@beninfashionweek.com')],
            [
                'name' => env('ADMIN_NAME', 'Admin BFW'),
                'password' => Hash::make(env('ADMIN_PASSWORD', 'changez-moi')),
            ],
        );
    }
}
