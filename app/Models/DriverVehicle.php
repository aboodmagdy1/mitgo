<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriverVehicle extends Model
{
    protected $fillable = [
        'driver_id',
        'seats',
        'color',
        'license_number',
        'plate_number',
        'vehicle_type_id',
        'vehicle_brand_model_id',
    ];

    protected $casts = [
        'seats' => 'integer',
    ];

    /**
     * Get the driver that owns the vehicle.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the vehicle type.
     */
    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    /**
     * Get the vehicle brand model.
     */
    public function vehicleBrandModel(): BelongsTo
    {
        return $this->belongsTo(VehicleBrandModel::class);
    }
}
