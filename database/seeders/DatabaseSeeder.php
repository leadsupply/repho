<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Repository;
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

        $defaultRepo = Repository::firstOrCreate(
            ['slug' => 'default'],
            ['name' => 'Default', 'auth_type' => 'none'],
        );

        $defaultRepo->packages()->syncWithoutDetaching(Package::pluck('id'));
    }
}
