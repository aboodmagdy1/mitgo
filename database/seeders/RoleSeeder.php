<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Country;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
            $roles= [
                [
                    'name' => 'client',
                    'guard_name' => 'sanctum',
                ],
                [
                    'name' => 'driver',
                    'guard_name' => 'sanctum',
                ],
                [
                    'name' => 'admin',
                    'guard_name' => 'web',
                ]
            ];

            foreach ($roles as $role) {
                Role::create($role);
            }
    }
}
