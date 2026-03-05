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

    /**
     * Get the trips assigned to this zone.
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Extract polygon coordinates from zone points (GeoJSON or raw format).
     *
     * @return array<int, array{0: float, 1: float}> Array of [lat, lng] for Leaflet
     */
    public function getPolygonCoordinates(): array
    {
        $data = $this->points;
        if (is_string($data)) {
            $data = json_decode($data, true) ?? [];
        }
        if (! is_array($data)) {
            return [];
        }

        $geo = $data['geojson'] ?? $data;
        $type = $geo['type'] ?? null;

        if ($type === 'FeatureCollection') {
            $features = $geo['features'] ?? [];
            $first = $features[0] ?? null;
            $geo = $first['geometry'] ?? $first ?? null;
        }
        if (($geo['type'] ?? null) === 'Feature') {
            $geo = $geo['geometry'] ?? null;
        }

        $coords = $geo['coordinates'] ?? $geo;
        if (! is_array($coords)) {
            return [];
        }

        // GeoJSON Polygon: [[[lng, lat], [lng, lat], ...]] - first ring is exterior
        if (isset($coords[0][0][0])) {
            $ring = $coords[0];
            return array_map(fn ($p) => [(float) ($p[1] ?? 0), (float) ($p[0] ?? 0)], $ring);
        }
        // Raw ring: [[lng, lat], ...] or [[lat, lng], ...]
        if (isset($coords[0][0]) && isset($coords[0][1])) {
            return array_map(fn ($p) => [(float) ($p[1] ?? $p[0] ?? 0), (float) ($p[0] ?? $p[1] ?? 0)], $coords);
        }

        return [];
    }
}
