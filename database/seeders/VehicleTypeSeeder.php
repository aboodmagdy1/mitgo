<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use App\Models\VehicleTypeDefaultPricing;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicleTypes = [
            [
                'vehicle_type' => [
                    'name' => [
                        'en' => 'Economy',
                        'ar' => 'اقتصادي'
                    ],
                    'seats' => 4,
                    'active' => true,
                ],
                'default_pricing' => [
                    'base_fare' => 5.00,
                    'fare_per_km' => 1.50,
                    'fare_per_minute' => 0.50,
                    'cancellation_fee' => 10.00,
                    'waiting_fee' => 0.30,
                ],
            ],
            [
                'vehicle_type' => [
                    'name' => [
                        'en' => 'Comfort',
                        'ar' => 'مريح'
                    ],
                    'seats' => 4,
                    'active' => true,
                ],
                'default_pricing' => [
                    'base_fare' => 8.00,
                    'fare_per_km' => 2.00,
                    'fare_per_minute' => 0.70,
                    'cancellation_fee' => 15.00,
                    'waiting_fee' => 0.40,
                ],
            ],
            [
                'vehicle_type' => [
                    'name' => [
                        'en' => 'Premium',
                        'ar' => 'مميز'
                    ],
                    'seats' => 4,
                    'active' => true,
                ],
                'default_pricing' => [
                    'base_fare' => 12.00,
                    'fare_per_km' => 3.00,
                    'fare_per_minute' => 1.00,
                    'cancellation_fee' => 20.00,
                    'waiting_fee' => 0.60,
                ],
            ],
            [
                'vehicle_type' => [
                    'name' => [
                        'en' => 'SUV',
                        'ar' => 'دفع رباعي'
                    ],
                    'seats' => 7,
                    'active' => true,
                ],
                'default_pricing' => [
                    'base_fare' => 15.00,
                    'fare_per_km' => 3.50,
                    'fare_per_minute' => 1.20,
                    'cancellation_fee' => 25.00,
                    'waiting_fee' => 0.70,
                ],
            ],
            [
                'vehicle_type' => [
                    'name' => [
                        'en' => 'Van',
                        'ar' => 'حافلة صغيرة'
                    ],
                    'seats' => 12,
                    'active' => true,
                ],
                'default_pricing' => [
                    'base_fare' => 20.00,
                    'fare_per_km' => 4.00,
                    'fare_per_minute' => 1.50,
                    'cancellation_fee' => 30.00,
                    'waiting_fee' => 0.80,
                ],
            ],
            [
                'vehicle_type' => [
                    'name' => [
                        'en' => 'Motorcycle',
                        'ar' => 'دراجة نارية'
                    ],
                    'seats' => 2,
                    'active' => true,
                ],
                'default_pricing' => [
                    'base_fare' => 3.00,
                    'fare_per_km' => 1.00,
                    'fare_per_minute' => 0.30,
                    'cancellation_fee' => 5.00,
                    'waiting_fee' => 0.20,
                ],
            ],
        ];

        foreach ($vehicleTypes as $data) {
            $vehicleType = VehicleType::create($data['vehicle_type']);
            
            // TODO: Add vehicle type icons through the admin panel or use local images
            // External URLs from Bing are unreliable and may expire
            // $vehicleType->addMediaFromUrl(url('path/to/icon'))->toMediaCollection('icon');
            
            // Create default pricing for the vehicle type
            VehicleTypeDefaultPricing::create([
                'vehicle_type_id' => $vehicleType->id,
                ...$data['default_pricing']
            ]);
        }
    }
}
