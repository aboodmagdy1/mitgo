<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Zone extends Model
{
    protected $fillable = [
        'points',
        'name',
        'status',
    ];

    protected $casts = [
        'points' => 'json',
        'status' => 'boolean',
    ];

    /**
     * Get the pricing modifiers for the zone.
     */
    public function pricingModifiers(): HasMany
    {
        return $this->hasMany(ZonePricingModifier::class);
    }

    /**
     * Get the vehicle type pricing for the zone.
     */
    public function vehicleTypePricing(): HasMany
    {
        return $this->hasMany(ZoneVehicleTypePricing::class);
    }
}
