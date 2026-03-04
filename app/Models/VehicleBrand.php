<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class VehicleBrand extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia;

    protected $table = 'vehicle_brands';
    public $timestamps = true;
    
    protected $fillable = [
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
     * Get all models for this brand.
     */
    public function models(): HasMany
    {
        return $this->hasMany(VehicleBrandModel::class);
    }

    /**
     * Get active models for this brand.
     */
    public function activeModels(): HasMany
    {
        return $this->hasMany(VehicleBrandModel::class)->where('active', true);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('icon')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/svg+xml', 'image/webp']);
    }

}
