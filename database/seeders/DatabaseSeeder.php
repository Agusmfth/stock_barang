<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Warehouse;
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
        Warehouse::firstOrCreate(['name' => 'Gudang Pusat'], ['address' => 'Jakarta']);
        User::firstOrCreate(['email' => 'admin@stockflow.test'], ['name' => 'Super Admin', 'password' => 'password', 'role' => 'Super Admin']);
    }
}
