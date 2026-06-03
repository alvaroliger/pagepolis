<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::create([
            'name'     => 'Admin Pagepolis',
            'email'    => 'admin@pagepolis.com',
            'password' => Hash::make('admin123'),
            'role'     => 'admin',
        ]);

        User::create([
            'name'     => 'Usuario Demo',
            'email'    => 'demo@pagepolis.com',
            'password' => Hash::make('demo123'),
            'role'     => 'user',
        ]);

        $this->call([
            TemplateSeeder::class,
        ]);
    }
}
