<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ZonePricingModifier;
use App\Models\Zone;

class ZonePricingModifierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all zones to create modifiers for each
        $zones = Zone::all();
        
        if ($zones->isEmpty()) {
            $this->command->warn('No zones found. Please create zones first.');
            return;
        }

        // Define modifier patterns based on common busy times
        $modifierPatterns = [
            [
                'name' => 'Morning Rush Hour',
                'name_ar' => 'ساعات الذروة الصباحية',
                'multiplier' => 25, // 25% increase
                'start_date' => '2025-01-01',   
                'end_date' => '2025-12-31',
                'start_time' => '07:00',
                'end_time' => '09:30',
            ],
            [
                'name' => 'Lunch Time',
                'name_ar' => 'وقت الغداء',
                'multiplier' => 10, // 10% increase
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'start_time' => '12:00',
                'end_time' => '14:00',
            ],
            [
                'name' => 'Evening Rush Hour',
                'name_ar' => 'ساعات الذروة المسائية',
                'multiplier' => 30, // 30% increase
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'start_time' => '17:00',
                'end_time' => '20:00',
            ],
            [
                'name' => 'Late Night Premium',
                'name_ar' => 'رسوم وقت متأخر من الليل',
                'multiplier' => 20, // 20% increase
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'start_time' => '23:00',
                'end_time' => '05:00',
            ],
            [
                'name' => 'Weekend Peak',
                'name_ar' => 'ذروة نهاية الأسبوع',
                'multiplier' => 15, // 15% increase
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'start_time' => '19:00',
                'end_time' => '23:00',
            ],
            [
                'name' => 'Early Morning Discount',
                'name_ar' => 'خصم الصباح الباكر',
                'multiplier' => -10, // 10% discount
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'start_time' => '05:00',
                'end_time' => '07:00',
            ],
            [
                'name' => 'Mid-Day Discount',
                'name_ar' => 'خصم منتصف النهار',
                'multiplier' => 5, // 5% discount
                'start_date' => '2025-01-01',
                'end_date' => '2025-12-31',
                'start_time' => '10:00',
                'end_time' => '12:00',
            ],
        ];

        // Create modifiers for each zone
        foreach ($zones as $zone) {
            // Some zones (like airports, holy areas) might have different patterns
            $patterns = $this->getModifierPatternsForZone($zone, $modifierPatterns);
            
            foreach ($patterns as $pattern) {
                ZonePricingModifier::create([
                    'zone_id' => $zone->id,
                    'name' => $pattern['name'],
                    'multiplier' => $pattern['multiplier'],
                    'is_active' => true,
                    'start_date' => $pattern['start_date'],
                    'end_date' => $pattern['end_date'],
                    'start_time' => $pattern['start_time'],
                    'end_time' => $pattern['end_time'],
                ]);
            }
        }

        $this->command->info('Zone Pricing Modifiers seeded successfully for all zones!');
    }

    /**
     * Get modifier patterns specific to zone type
     */
    private function getModifierPatternsForZone($zone, $defaultPatterns)
    {
        // Holy areas (Mecca) have special patterns
        if (str_contains(strtolower($zone->name), 'mecca') || str_contains(strtolower($zone->name), 'holy')) {
            return [
                [
                    'name' => 'Hajj/Umrah Peak',
                    'multiplier' => 50, // 50% increase during religious times
                    'start_date' => '2025-01-01',
                    'end_date' => '2025-12-31',
                    'start_time' => '18:00',
                    'end_time' => '02:00',
                ],
                [
                    'name' => 'Prayer Time Premium',
                    'multiplier' => 20,
                    'start_date' => '2025-01-01',
                    'end_date' => '2025-12-31',
                    'start_time' => '05:00',
                    'end_time' => '07:00',
                ],
                [
                    'name' => 'Night Premium',
                    'multiplier' => 30,
                    'start_date' => '2025-01-01',
                    'end_date' => '2025-12-31',
                    'start_time' => '22:00',
                    'end_time' => '04:00',
                ],
            ];
        }

        // Airport areas have different patterns
        if (str_contains(strtolower($zone->name), 'airport')) {
            return [
                [
                    'name' => 'Flight Rush Morning',
                    'multiplier' => 35,
                    'start_date' => '2025-01-01',
                    'end_date' => '2025-12-31',
                    'start_time' => '05:00',
                    'end_time' => '09:00',
                ],
                [
                    'name' => 'Flight Rush Evening',
                    'multiplier' => 35,
                    'start_date' => '2025-01-01',
                    'end_date' => '2025-12-31',
                    'start_time' => '17:00',
                    'end_time' => '22:00',
                ],
                [
                    'name' => 'Late Night Flights',
                    'multiplier' => 40,
                    'start_date' => '2025-01-01',
                    'end_date' => '2025-12-31',
                    'start_time' => '22:00',
                    'end_time' => '05:00',
                ],
                [
                    'name' => 'Mid-day Low Demand',
                    'multiplier' => -10,
                    'start_date' => '2025-01-01',
                    'end_date' => '2025-12-31',
                    'start_time' => '10:00',
                    'end_time' => '15:00',
                ],
            ];
        }

        // Central business districts have higher multipliers
        if (str_contains(strtolower($zone->name), 'central')) {
            return array_map(function($pattern) {
                if ($pattern['multiplier'] > 0) {
                    $pattern['multiplier'] += 5; // Add 5% more for central areas
                }
                return $pattern;
            }, $defaultPatterns);
        }

        return $defaultPatterns;
    }
}
