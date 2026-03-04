<?php

namespace App\Helpers;

use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Http;
use App\Settings\ContentSettings;
use Illuminate\Support\Facades\Cache;
use App\Models\Zone;

if(!function_exists('settings')){
    function settings()
    {
        return Cache::rememberForever('all_settings', function () {
            return [
                app(ContentSettings::class),
            ];
        });
    }
}


if(!function_exists('setting')){
    function setting($group, $name)
    {
        return Cache::rememberForever("setting_{$group}_{$name}", function () use ($group, $name) {
            switch ($group) {
                case 'content':
                        $settings = app(ContentSettings::class);
                    break;
                case 'general':
                    $settings = app(GeneralSettings::class);
                    break;
                default:
                    return null;
            }
            
            return $settings->{$name} ?? null;
        });
    }
}

if(!function_exists('clear_settings_cache')){
    function clear_settings_cache()
    {
        Cache::forget('all_settings');
        Cache::forget('setting_content_*');
        Cache::forget('setting_general_*');
    }
}

// ===== Zones helpers =====
if (!function_exists('in_any_zone')) {
    /**
     * Return the Zone the point belongs to, or null if none.
     */
    function in_any_zone(float $lat, float $lng): ?Zone
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Zone> $zones */
        $zones = Zone::query()->where('status', true)->get();
        foreach ($zones as $zone) {
            if (point_in_polygon_from_zone($zone, $lat, $lng)) {
                return $zone;
            }
        }
        return null;
    }
}

if (!function_exists('point_in_polygon_from_zone')) {
    /**
     * Minimal check against the first polygon ring in Zone->points.
     */
    function point_in_polygon_from_zone(Zone $zone, float $lat, float $lng): bool
    {
        $data = $zone->points;
        $geo = is_array($data) && array_key_exists('geojson', $data) ? $data['geojson'] : $data;
        if (!is_array($geo)) {
            $decoded = json_decode((string) $geo, true);
            $geo = is_array($decoded) ? $decoded : null;
        }
        if (!is_array($geo)) return false;

        $ring = null;
        $type = $geo['type'] ?? null;
        if ($type === 'FeatureCollection') {
            $first = $geo['features'][0] ?? null;
            $geo = is_array($first) ? ($first['geometry'] ?? null) : null;
            $type = $geo['type'] ?? null;
        }
        if ($type === 'Feature') {
            $geo = $geo['geometry'] ?? null;
            $type = $geo['type'] ?? null;
        }
        if ($type === 'Polygon') {
            $ring = $geo['coordinates'][0] ?? null;
        } elseif ($type === 'MultiPolygon') {
            $ring = $geo['coordinates'][0][0] ?? null;
        } elseif (is_array($geo) && isset($geo[0][0], $geo[0][1])) {
            $ring = $geo; // already a ring
        }
        if (!is_array($ring) || count($ring) < 3) return false;

        return point_in_polygon_ring($lat, $lng, $ring);
    }
}

if (!function_exists('point_in_polygon_ring')) {
    /**
     * Ray-casting point-in-polygon where ring points are [lng, lat].
     * Returns true if inside.
     * @param array<int, array{0: float|int, 1: float|int}> $ring
     */
    function point_in_polygon_ring(float $lat, float $lng, array $ring): bool
    {
        $inside = false;
        $n = count($ring);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xiLng = (float) ($ring[$i][0] ?? 0); $xiLat = (float) ($ring[$i][1] ?? 0);
            $xjLng = (float) ($ring[$j][0] ?? 0); $xjLat = (float) ($ring[$j][1] ?? 0);
            $intersect = (($xiLat > $lat) !== ($xjLat > $lat))
                && ($lng < ($xjLng - $xiLng) * ($lat - $xiLat) / (max($xjLat - $xiLat, 1e-12)) + $xiLng);
            if ($intersect) $inside = !$inside;
        }
        return $inside;
    }
}
if(!function_exists('get_distance_and_duration')){
    function get_distance_and_duration($origin, $destination){
        $apiKey = env('GOOGLE_MAPS_API_KEY');
        
        // Check if API key is available
        if (!$apiKey) {
            throw new \Exception('Google Maps API key not configured');
        }
        
        // Format coordinates properly for Google API
        // Convert array [lat, lng] to string "lat,lng"
        $originString = is_array($origin) ? implode(',', $origin) : $origin;
        $destinationString = is_array($destination) ? implode(',', $destination) : $destination;
        
        $response = Http::get('https://maps.googleapis.com/maps/api/distancematrix/json', [
            'origins' => $originString,
            'destinations' => $destinationString,
            'key' => $apiKey,
        ]);
        
        // Check if the HTTP request was successful
        if (!$response->successful()) {
            throw new \Exception('Failed to get response from Google Distance Matrix API: ' . $response->status());
        }
        
        $data = $response->json();
        
        // Check if the API returned an error
        if (isset($data['status']) && $data['status'] !== 'OK') {
            throw new \Exception('Google Distance Matrix API error: ' . ($data['status'] ?? 'Unknown error'));
        }
        
        // Validate response structure
        if (!isset($data['rows'][0]['elements'][0])) {
            throw new \Exception('Invalid response structure from Google Distance Matrix API');
        }
        
        $element = $data['rows'][0]['elements'][0];
        
        // Check if the element status is OK
        if (isset($element['status']) && $element['status'] !== 'OK') {
            throw new \Exception('Google Distance Matrix API element error: ' . $element['status']);
        }
        
        // Extract distance and duration safely
        if (!isset($element['distance']['text']) || !isset($element['duration']['text'])) {
            throw new \Exception('Distance or duration data not available in API response');
        }
        
        return [
            'distance' => $element['distance']['value'] / 1000 ?? null, // Distance in kilo meters
            'duration' => $element['duration']['value'] / 60 ?? null, // Duration in minutes
        ];
    }
}