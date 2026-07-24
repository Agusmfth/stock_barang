<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Warehouse::firstOrCreate(['name' => 'Gudang Pusat'], ['address' => 'Jakarta']);
        User::updateOrCreate(
            ['email' => 'admin@stockflow.test'],
            ['name' => 'Super Admin', 'password' => Hash::make('password'), 'role' => 'Super Admin', 'is_active' => true]
        );
    }
}
