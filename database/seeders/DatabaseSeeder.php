<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => 'SUPER_ADMIN'],
            [
                'display_name' => 'Super Admin',
                'hierarchy_level' => 0,
                'is_system' => true
            ]
        );

        $hqDept = Department::firstOrCreate(
            ['code' => 'HQ'],
            [
                'name' => 'Headquarters',
                'organization' => 'Hexagon Kingdom'
            ]
        );

        User::updateOrCreate(
            ['email' => 'superadmin@neuragent.local'],
            [
                'name' => 'Superhero',
                'full_name' => 'System Super Admin',
                'username' => 'superhero',
                'password' => Hash::make('superadmin'),
                'role' => 'SUPER_ADMIN',
                'role_id' => $adminRole->id,
                'department_id' => $hqDept->id,
                'status' => 'ACTIVE'
            ]
        );
    }
}
