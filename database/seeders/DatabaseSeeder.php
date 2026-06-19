<?php

namespace Database\Seeders;

use App\Models\Invoice;
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
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Invoice::factory(30)->pending()->create();
        Invoice::factory(8)->approved()->create();
        Invoice::factory(7)->rejected()->create();
    }
}
