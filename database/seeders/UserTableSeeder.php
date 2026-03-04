<?php

namespace Database\Seeders;

use App\Enums\UserTypeEnum;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create users and store their instances to get actual IDs
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('admin123456'),
            'phone' => '01010101010',
            'is_active' => true,
            'city_id' => 1,
            'latest_lat' => 0,
            'latest_long' => 0,
            'active_code' => 0,
            'remember_token' => null,
        ]);
        // Run shield command to generate all policies for users 
        try {
            Artisan::call('shield:generate', ['--all' => true, '--panel' => 'admin']);
            
            // Make users super admins using their actual IDs
            Artisan::call('shield:super-admin', ['--user' => $admin->id]);
            
            $this->command->info('Shield commands executed successfully.');
        } catch (\Exception $e) {
            $this->command->error('Error running Shield commands: ' . $e->getMessage());
        }
    }
}
