<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ZoneVehicleTypePricing extends Model
{
    use HasFactory;

    protected $table = 'zone_vehicle_type_pricing';

    protected $fillable = [
        'zone_id',
        'vehicle_type_id',
        'base_fare',
        'fare_per_km',
        'fare_per_minute',
        'cancellation_fee',
        'waiting_fee',
        'extra_fare',
    ];

    protected $casts = [
        'base_fare' => 'decimal:2',
        'fare_per_km' => 'decimal:2',
        'fare_per_minute' => 'decimal:2',
        'cancellation_fee' => 'decimal:2',
        'waiting_fee' => 'decimal:2',
        'extra_fare' => 'decimal:2',
    ];

    /**
     * Get the zone that owns the pricing.
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Get the vehicle type that owns the pricing.
     */
    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    /**
     * Scope a query to only include pricing for a specific zone.
     */
    public function scopeForZone($query, $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    /**
     * Scope a query to only include pricing for a specific vehicle type.
     */
    public function scopeForVehicleType($query, $vehicleTypeId)
    {
        return $query->where('vehicle_type_id', $vehicleTypeId);
    }

    /**
     * Get the total base cost for a trip.
     */
    public function calculateBaseCost($distance = 0, $duration = 0): float
    {
        $cost = $this->base_fare;
        $cost += $this->fare_per_km * $distance;
        $cost += $this->fare_per_minute * $duration;
        
        return round($cost, 2);
    }

    /**
     * Get the waiting fee for a specific duration.
     */
    public function calculateWaitingFee($waitingMinutes = 0): float
    {
        return round($this->waiting_fee * $waitingMinutes, 2);
    }

    /**
     * Get the total trip cost including all fees.
     */
    public function calculateTotalCost($distance = 0, $duration = 0, $waitingMinutes = 0, $includeExtraFare = false): float
    {
        $cost = $this->calculateBaseCost($distance, $duration);
        $cost += $this->calculateWaitingFee($waitingMinutes);
        
        if ($includeExtraFare) {
            $cost += $this->extra_fare;
        }
        
        return round($cost, 2);
    }
}
