<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@datamind.local'],
            [
                'name' => 'Administrador DataMind',
                'password' => 'datamind2026',
            ]
        );

        User::updateOrCreate(
            ['email' => 'analista@datamind.local'],
            [
                'name' => 'Analista BI',
                'password' => 'datamind2026',
            ]
        );
    }
}
