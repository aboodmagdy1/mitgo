<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            PaymentMethodTableSeeder::class,
            CitySeeder::class,
            // PageSeeder::class,
            RoleSeeder::class,
            UserTableSeeder::class,
            CancelTripReasonSeeder::class,
            VehicleTypeSeeder::class,
            VehicleBrandSeeder::class,
            VehicleBrandModelSeeder::class,
            DriverSeeder::class,
            // Zone-related seeders (order matters: zones first, then vehicle types, then pricing, then modifiers)
            ZoneSeeder::class,
            ZoneVehicleTypePricingSeeder::class,
            ZonePricingModifierSeeder::class,
            DemoTripsSeeder::class,
        ]);
    }
}
