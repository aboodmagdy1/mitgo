<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleTypeDefaultPricing extends Model
{
    protected $table = 'vehicle_types_default_pricing';
    
    protected $fillable = [
        'vehicle_type_id',
        'base_fare',
        'fare_per_km',
        'fare_per_minute',
        'cancellation_fee',
        'waiting_fee',
    ];

    protected function casts(): array
    {
        return [
            'base_fare' => 'decimal:2',
            'fare_per_km' => 'decimal:2',
            'fare_per_minute' => 'decimal:2',
            'cancellation_fee' => 'decimal:2',
            'waiting_fee' => 'decimal:2',
        ];
    }

    /**
     * Get the vehicle type that owns this default pricing.
     */
    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    /**
     * Calculate base cost using default pricing
     */
    public function calculateBaseCost(float $distance, float $duration): float
    {
        return $this->base_fare + 
               ($this->fare_per_km * $distance) + 
               ($this->fare_per_minute * $duration);
    }
}

