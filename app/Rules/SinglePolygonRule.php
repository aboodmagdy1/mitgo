<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SinglePolygonRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $data = $value;
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data = $decoded;
            }
        }

        if (!is_array($data)) {
            $fail(__('Please draw a polygon.'));
            return;
        }

        // The MapPicker may wrap the geometry under a 'geojson' key.
        $geo = array_key_exists('geojson', $data) ? $data['geojson'] : $data;
        $type = $geo['type'] ?? null;

        if ($type === 'FeatureCollection') {
            $features = $geo['features'] ?? [];
            if (count($features) !== 1) {
                $fail(__('Only one polygon is allowed.'));
                return;
            }
            $geo = $features[0]['geometry'] ?? null;
            $type = is_array($geo) ? ($geo['type'] ?? null) : null;
        }

        if ($type === 'Feature') {
            $geo = $geo['geometry'] ?? null;
            $type = is_array($geo) ? ($geo['type'] ?? null) : null;
        }

        if (!is_array($geo)) {
            $fail(__('Please draw a polygon.'));
            return;
        }

        // If geometry is a raw ring (array of [lng,lat] points), accept as a single polygon.
        if (isset($geo[0][0]) && isset($geo[0][1]) && is_numeric($geo[0][0]) && is_numeric($geo[0][1])) {
            if (count($geo) < 3) {
                $fail(__('Please draw a polygon.'));
            }
            return; // valid single ring interpreted as one polygon
        }

        if (($geo['type'] ?? null) === 'MultiPolygon') {
            $fail(__('Only one polygon is allowed.'));
            return;
        }

        if (($geo['type'] ?? null) !== 'Polygon') {
            $fail(__('Please draw a polygon.'));
            return;
        }
    }
}


