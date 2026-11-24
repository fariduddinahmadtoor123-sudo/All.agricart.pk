<?php

namespace Database\Seeders;

use App\Models\Role;
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
        $role = Role::query()->firstOrCreate(
            ['name' => 'Super admin'],
            ['description' => 'Approves people and assigns their role.'],
        );

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => 'admin',
            'role_id' => $role->id,
            'status' => User::STATUS_ACTIVE,
        ]);
    }
}
