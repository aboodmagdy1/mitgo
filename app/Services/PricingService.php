<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Zone;
use App\Models\VehicleType;
use App\Models\ZonePricingModifier;
use App\Models\ZoneVehicleTypePricing;
use Carbon\Carbon;

class PricingService
{
    /**
     * Calculate estimated trip cost for a vehicle type in a zone
     */
    public function calculateEstimatedCost(VehicleType $vehicleType, ?Zone $zone, float $distance, float $duration): ?float
    {
        $pricing = $this->getPricingForVehicle($vehicleType, $zone);
        
        if (!$pricing) {
            return null;
        }

        return $pricing->calculateBaseCost($distance, $duration);
    }

    /**
     * Get pricing configuration for a vehicle type in a specific zone
     * Falls back to default pricing if zone is null or zone-specific pricing is not available
     */
    public function getPricingForVehicle(VehicleType $vehicleType, ?Zone $zone)
    {
        // Try to get zone-specific pricing if zone is provided
        if ($zone) {
            $zonePricing = $vehicleType->zonePricing()
                ->where('zone_id', $zone->id)
                ->first();
            
            if ($zonePricing) {
                return $zonePricing;
            }
        }
        
        // Fall back to default pricing
        return $vehicleType->defaultPricing;
    }

    /**
     * Get pricing configuration for a vehicle type in a specific zone (legacy method)
     */
    public function getPricingForVehicleInZone(VehicleType $vehicleType, Zone $zone): ?ZoneVehicleTypePricing
    {
        return $vehicleType->zonePricing()
            ->where('zone_id', $zone->id)
            ->first();
    }

    /**
     * Get active pricing modifiers for a zone at a specific date and time
     */
    public function getActivePricingModifiers(Zone $zone, ?Carbon $dateTime = null): float
    {
        $dateTime = $dateTime ?? now();
        $date = $dateTime->format('Y-m-d');
        $time = $dateTime->format('H:i:s');

        $modifiers = ZonePricingModifier::active()
            ->forZone($zone->id)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time)
            ->get();

        // Calculate total multiplier (sum of all active modifiers)
        // Modifiers are stored as percentages (10, 20), so sum them directly
        $totalMultiplier = $modifiers->sum('multiplier');

        return $totalMultiplier;
    }

    /**
     * Apply pricing modifiers to a cost
     * @param float $baseCost The base cost
     * @param float $multiplierPercentage The percentage (e.g., 10, 20, not 0.10, 0.20)
     */
    public function applyModifiers(float $baseCost, float $multiplierPercentage): float
    {
        return $baseCost * (1 + $multiplierPercentage / 100);
    }

    /**
     * Get available vehicle types with pricing for a zone
     */
    public function getVehicleTypesWithPricing(?Zone $zone, float $distance, float $duration, ?Carbon $dateTime = null): array
    {
        $dateTime = $dateTime ?? now();
        $vehicleTypes = VehicleType::where('active', true)->get();
        
        // Get active modifiers for this zone at this time (if zone exists)
        $modifierPercentage = $zone ? $this->getActivePricingModifiers($zone, $dateTime) : 0;
        
        $result = [];

        foreach ($vehicleTypes as $vehicleType) {
            $pricing = $this->getPricingForVehicle($vehicleType, $zone);
            
            if ($pricing) {
                $baseCost = $pricing->calculateBaseCost($distance, $duration);
                
                // Apply modifiers if any
                $estimatedCost = $modifierPercentage > 0 
                    ? $this->applyModifiers($baseCost, $modifierPercentage)
                    : $baseCost;
                
                $result[] = [
                    'id' => $vehicleType->id,
                    'name' => $vehicleType->name,
                    'seats' => $vehicleType->seats,
                    'icon' => $vehicleType->getFirstMediaUrl('icon'),
                    'cost' => round($estimatedCost, 2),
                    'duration' => round($duration, 0),
                ];
            }
        }

        return $result;
    }

    /**
     * Get active modifiers details for a zone
     */
    public function getActiveModifiersDetails(Zone $zone, ?Carbon $dateTime = null): array
    {
        $dateTime = $dateTime ?? now();
        $date = $dateTime->format('Y-m-d');
        $time = $dateTime->format('H:i:s');

        $modifiers = ZonePricingModifier::active()
            ->forZone($zone->id)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->where('start_time', '<=', $time)
            ->where('end_time', '>=', $time)
            ->get();

        return $modifiers->map(function ($modifier) {
            return [
                'id' => $modifier->id,
                'name' => $modifier->name,
                'multiplier' => $modifier->multiplier,
                'percentage' => round((float)$modifier->multiplier, 0) . '%',
            ];
        })->toArray();
    }
    /**
     * Get available vehicle types without pricing (when dropoff coordinates are not provided)
     */
    public function getVehicleTypesWithoutPricing(): array
    {
        $vehicleTypes = VehicleType::where('active', true)->get();
        
        $result = [];

        foreach ($vehicleTypes as $vehicleType) {
            $result[] = [
                'id' => $vehicleType->id,
                'name' => $vehicleType->name,
                'seats' => $vehicleType->seats,
                'icon' => $vehicleType->getFirstMediaUrl('icon'),
                'cost' => null,
                'duration' => null,
            ];
        }

        return $result;
    }

    /**
     * Apply coupon discount to fare
     * Delegates to CouponService for discount calculation logic
     */
    public function applyCoupon(float $baseCost, Coupon $coupon): float
    {
        $couponService = app(\App\Services\CouponService::class);
        $discountAmount = $couponService->calculateDiscountAmount($coupon, $baseCost);
        
        return $baseCost - $discountAmount;
    }
}
