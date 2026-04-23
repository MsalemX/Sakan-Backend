<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'Admin']);

        User::updateOrCreate(
            ['email' => 'a@a.a'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('123456789'),
                'role_id' => $role->id,
            ]
        );
    }
}
