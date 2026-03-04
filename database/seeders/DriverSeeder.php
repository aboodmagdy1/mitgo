<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Driver;
use App\Models\DriverVehicle;
use App\Models\City;
use App\Models\VehicleType;
use App\Models\VehicleBrand;
use App\Models\VehicleBrandModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get required data
        $driverRole = Role::where('name', 'driver')->where('guard_name', 'sanctum')->first();
        $cities = City::all();
        $vehicleTypes = VehicleType::where('active', true)->get();
        $vehicleBrands = VehicleBrand::where('active', true)->get();

        if (!$driverRole || $cities->isEmpty() || $vehicleTypes->isEmpty() || $vehicleBrands->isEmpty()) {
            $this->command->error('Required data not found. Please ensure cities, vehicle types, brands, and driver role exist.');
            return;
        }

        $driversData = [
            [
                'name' => 'أحمد محمد',
                'email' => 'ahmed.mohammed@example.com',
                'phone' => '+966501234567',
                'national_id' => '1234567890',
                'license_number' => 'LIC001234',
                'absher_phone' => '+966501234567',
                'date_of_birth' => '1985-05-15',
                'is_approved' => true,
                'status' => 1, // Online
            ],
            [
                'name' => 'محمد علي',
                'email' => 'mohammed.ali@example.com',
                'phone' => '+966502345678',
                'national_id' => '2345678901',
                'license_number' => 'LIC002345',
                'absher_phone' => '+966502345678',
                'date_of_birth' => '1987-08-22',
                'is_approved' => true,
                'status' => 0, // Offline
            ],
            [
                'name' => 'عبدالله أحمد',
                'email' => 'abdullah.ahmed@example.com',
                'phone' => '+966503456789',
                'national_id' => '3456789012',
                'license_number' => 'LIC003456',
                'absher_phone' => '+966503456789',
                'date_of_birth' => '1990-12-10',
                'is_approved' => false,
                'status' => 0, // Offline
            ],
            [
                'name' => 'سعد عبدالرحمن',
                'email' => 'saad.abdulrahman@example.com',
                'phone' => '+966504567890',
                'national_id' => '4567890123',
                'license_number' => 'LIC004567',
                'absher_phone' => '+966504567890',
                'date_of_birth' => '1988-03-25',
                'is_approved' => true,
                'status' => 1, // Online
            ],
            [
                'name' => 'خالد حسن',
                'email' => 'khalid.hassan@example.com',
                'phone' => '+966505678901',
                'national_id' => '5678901234',
                'license_number' => 'LIC005678',
                'absher_phone' => '+966505678901',
                'date_of_birth' => '1983-11-18',
                'is_approved' => false,
                'status' => 0, // Offline
            ],
        ];

        $vehicleData = [
            [
                'color' => 'أبيض',
                'license_number' => 'VEH001234',
                'plate_number' => 'ب ج د 1234',
                'seats' => 4,
            ],
            [
                'color' => 'أسود',
                'license_number' => 'VEH002345',
                'plate_number' => 'ج د ه 2345',
                'seats' => 4,
            ],
            [
                'color' => 'رمادي',
                'license_number' => 'VEH003456',
                'plate_number' => 'د ه و 3456',
                'seats' => 7,
            ],
            [
                'color' => 'فضي',
                'license_number' => 'VEH004567',
                'plate_number' => 'ه و ز 4567',
                'seats' => 4,
            ],
            [
                'color' => 'أزرق',
                'license_number' => 'VEH005678',
                'plate_number' => 'و ز ح 5678',
                'seats' => 4,
            ],
        ];

        foreach ($driversData as $index => $driverData) {
            // Create user
            $user = User::create([
                'name' => $driverData['name'],
                'email' => $driverData['email'],
                'phone' => $driverData['phone'],
                'password' => Hash::make('password123'),
                'city_id' => $cities->random()->id,
                'is_active' => true,
                'latest_lat' => 24.7136 + (rand(-100, 100) / 1000), // Random coordinates around Riyadh
                'latest_long' => 46.6753 + (rand(-100, 100) / 1000),
                'active_code' => 0,
            ]);

			$user->addMedia(public_path('images/user.png'))
			->preservingOriginal()
			->toMediaCollection('avatar');

            // Assign driver role
            $user->assignRole($driverRole);

            // Create driver profile
            $driver = Driver::create([
                'user_id' => $user->id,
                'national_id' => $driverData['national_id'],
                'license_number' => $driverData['license_number'],
                'absher_phone' => $driverData['absher_phone'],
                'date_of_birth' => $driverData['date_of_birth'],
                'is_approved' => $driverData['is_approved'],
                'status' => $driverData['status'],
            ]);

            // Create vehicle for the driver
            $vehicleType = $vehicleTypes->random();
            $vehicleBrand = $vehicleBrands->random();
            
            // Get a random model for the selected brand
            $vehicleModel = VehicleBrandModel::where('vehicle_brand_id', $vehicleBrand->id)
                ->where('active', true)
                ->inRandomOrder()
                ->first();

            DriverVehicle::create([
                'driver_id' => $driver->id,
                'vehicle_type_id' => $vehicleType->id,
                'vehicle_brand_model_id' => $vehicleModel ? $vehicleModel->id : null,
                'color' => $vehicleData[$index]['color'],
                'license_number' => $vehicleData[$index]['license_number'],
                'plate_number' => $vehicleData[$index]['plate_number'],
                'seats' => $vehicleData[$index]['seats'],
            ]);

            $this->command->info("Created driver: {$driverData['name']} with vehicle");
        }

        $this->command->info('Driver seeder completed successfully!');
    }
}
