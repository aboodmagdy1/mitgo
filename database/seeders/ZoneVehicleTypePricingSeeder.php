<?php

namespace Database\Seeders;

use App\Models\Zone;
use App\Models\VehicleType;
use App\Models\ZoneVehicleTypePricing;
use Illuminate\Database\Seeder;

class ZoneVehicleTypePricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = Zone::all();
        $vehicleTypes = VehicleType::all();

        if ($zones->isEmpty()) {
            $this->command->warn('No zones found. Please create zones first.');
            return;
        }

        if ($vehicleTypes->isEmpty()) {
            $this->command->warn('No vehicle types found. Please create vehicle types first.');
            return;
        }

        // Base pricing structure for different vehicle types
        $basePricing = [
            'Economy' => [
                'base_fare' => 8.00,
                'fare_per_km' => 1.20,
                'fare_per_minute' => 0.30,
                'cancellation_fee' => 15.00,
                'waiting_fee' => 0.50,
                'extra_fare' => 5.00,
            ],
            'Comfort' => [
                'base_fare' => 12.00,
                'fare_per_km' => 1.80,
                'fare_per_minute' => 0.45,
                'cancellation_fee' => 20.00,
                'waiting_fee' => 0.75,
                'extra_fare' => 8.00,
            ],
            'Premium' => [
                'base_fare' => 18.00,
                'fare_per_km' => 2.50,
                'fare_per_minute' => 0.60,
                'cancellation_fee' => 30.00,
                'waiting_fee' => 1.00,
                'extra_fare' => 12.00,
            ],
            'SUV' => [
                'base_fare' => 22.00,
                'fare_per_km' => 2.80,
                'fare_per_minute' => 0.70,
                'cancellation_fee' => 35.00,
                'waiting_fee' => 1.20,
                'extra_fare' => 15.00,
            ],
            'Van' => [
                'base_fare' => 30.00,
                'fare_per_km' => 3.50,
                'fare_per_minute' => 0.90,
                'cancellation_fee' => 45.00,
                'waiting_fee' => 1.50,
                'extra_fare' => 20.00,
            ],
            'Motorcycle' => [
                'base_fare' => 5.00,
                'fare_per_km' => 0.80,
                'fare_per_minute' => 0.20,
                'cancellation_fee' => 10.00,
                'waiting_fee' => 0.30,
                'extra_fare' => 3.00,
            ],
        ];

        foreach ($zones as $zone) {
            $zoneMultiplier = $this->getZoneMultiplier($zone);
            
            foreach ($vehicleTypes as $vehicleType) {
                $vehicleTypeName = $vehicleType->name['en'] ?? 'Economy';
                $pricing = $basePricing[$vehicleTypeName] ?? $basePricing['Economy'];
                
                // Apply zone-specific multipliers
                $finalPricing = [
                    'zone_id' => $zone->id,
                    'vehicle_type_id' => $vehicleType->id,
                    'base_fare' => round($pricing['base_fare'] * $zoneMultiplier, 2),
                    'fare_per_km' => round($pricing['fare_per_km'] * $zoneMultiplier, 2),
                    'fare_per_minute' => round($pricing['fare_per_minute'] * $zoneMultiplier, 2),
                    'cancellation_fee' => round($pricing['cancellation_fee'] * $zoneMultiplier, 2),
                    'waiting_fee' => round($pricing['waiting_fee'] * $zoneMultiplier, 2),
                    'extra_fare' => round($pricing['extra_fare'] * $zoneMultiplier, 2),
                ];

                ZoneVehicleTypePricing::create($finalPricing);
            }
        }

        $this->command->info('Zone Vehicle Type Pricing seeded successfully!');
    }

    /**
     * Get zone-specific pricing multiplier based on zone characteristics
     */
    private function getZoneMultiplier(Zone $zone): float
    {
        $zoneName = strtolower($zone->name);

        // Airport areas - highest rates due to distance and convenience
        if (str_contains($zoneName, 'airport')) {
            return 1.40; // 40% higher
        }

        // Holy areas (Mecca) - premium rates due to high demand
        if (str_contains($zoneName, 'mecca') || str_contains($zoneName, 'holy')) {
            return 1.35; // 35% higher
        }

        // Central business districts - higher rates due to traffic and demand
        if (str_contains($zoneName, 'central')) {
            return 1.25; // 25% higher
        }

        // Major cities (Riyadh, Jeddah, Dammam) - standard premium rates
        if (str_contains($zoneName, 'riyadh') || str_contains($zoneName, 'jeddah') || str_contains($zoneName, 'dammam')) {
            return 1.15; // 15% higher
        }

        // North areas - slightly higher due to distance from city centers
        if (str_contains($zoneName, 'north')) {
            return 1.10; // 10% higher
        }

        // Eastern areas - oil region premium
        if (str_contains($zoneName, 'khobar') || str_contains($zoneName, 'east')) {
            return 1.12; // 12% higher
        }

        // South and West areas - standard rates
        if (str_contains($zoneName, 'south') || str_contains($zoneName, 'west')) {
            return 1.05; // 5% higher
        }

        // Default multiplier for other areas
        return 1.00;
    }

    /**
     * Get vehicle type specific adjustments for certain zones
     */
    private function getVehicleZoneAdjustment(VehicleType $vehicleType, Zone $zone): float
    {
        $zoneName = strtolower($zone->name);
        $vehicleTypeName = $vehicleType->name['en'] ?? 'Economy';

        // Motorcycles not allowed in airport areas
        if ($vehicleTypeName === 'Motorcycle' && str_contains($zoneName, 'airport')) {
            return 0; // Disabled
        }

        // Premium vehicles more expensive in holy areas
        if (($vehicleTypeName === 'Premium' || $vehicleTypeName === 'SUV') && 
            (str_contains($zoneName, 'mecca') || str_contains($zoneName, 'holy'))) {
            return 1.20; // Additional 20% for luxury in holy areas
        }

        // Vans more expensive in central areas due to size restrictions
        if ($vehicleTypeName === 'Van' && str_contains($zoneName, 'central')) {
            return 1.15; // Additional 15% for size premium
        }

        return 1.00; // No adjustment
    }
}
