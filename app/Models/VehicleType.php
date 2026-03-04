<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class VehicleType extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia;

    protected $table = 'vehicle_types';
    public $timestamps = true;
    
    protected $fillable = [
        'name',
        'active',
        'seats'
    ];

    public $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'seats' => 'integer',
        ];
    }

    /**
     * Get driver vehicles of this type.
     */
    public function driverVehicles()
    {
        return $this->hasMany(DriverVehicle::class);
    }

    /**
     * Get the zone pricing for this vehicle type.
     */
    public function zonePricing()
    {
        return $this->hasMany(ZoneVehicleTypePricing::class);
    }

    /**
     * Get the default pricing for this vehicle type.
     */
    public function defaultPricing()
    {
        return $this->hasOne(VehicleTypeDefaultPricing::class);
    }

    /**
     * Get the pricing for this type in a specific zone.
     */
    public function pricing($zone)
    {
        return $this->zonePricing()->where('zone_id', $zone->id)->first();
    }
}
