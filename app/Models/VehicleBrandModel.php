<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class VehicleBrandModel extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia;

    protected $table = 'vehicle_brand_models';
    public $timestamps = true;
    
    protected $fillable = [
        'vehicle_brand_id',
        'name',
        'active',
    ];

    public $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * Get the brand that owns this model.
     */
    public function vehicleBrand(): BelongsTo
    {
        return $this->belongsTo(VehicleBrand::class);
    }

    /**
     * Get driver vehicles using this model.
     */
    public function driverVehicles(): HasMany
    {
        return $this->hasMany(DriverVehicle::class);
    }
}
