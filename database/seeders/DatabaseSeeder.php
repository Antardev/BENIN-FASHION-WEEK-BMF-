<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'contact@benin-fashion-week.com';
        $password = config('Bfw@1er2026()');
        $name = 'Admin BFW';

        if (blank($password)) {
            throw new RuntimeException('Configurez ADMIN_PASSWORD avant de lancer le seeder.');
        }

        $admin = User::query()->where('email', $email)->first()
            ?? User::query()->where('email', 'admin@beninfashionweek.com')->first()
            ?? new User;

        $admin->fill([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ])->save();
    }
}
