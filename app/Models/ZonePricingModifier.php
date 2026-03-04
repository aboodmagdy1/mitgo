<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class ZonePricingModifier extends Model
{
    use HasFactory;

    protected $fillable = [
        'zone_id',
        'multiplier',
        'name',
        'is_active',
        'start_date',
        'start_time',
        'end_date',
        'end_time'
    ];


    protected $casts = [
        'multiplier' => 'decimal:2',
        'is_active' => 'boolean',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'start_date' => 'date',
        'end_date' => 'date'
    ];

    /**
     * Get the zone that owns the pricing modifier.
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Scope a query to only include active modifiers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include modifiers for a specific zone.
     */
    public function scopeForZone($query, $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    /**
     * Scope a query to only include modifiers active at a specific time.
     */
    public function scopeActiveAtTime($query, $date, $time)
    {
        return $query->where('start_date', '<=', $date)
                    ->where('end_date', '>=', $date)
                    ->where('start_time', '<=', $time)
                    ->where('end_time', '>=', $time);
    }

    /**
     * Get the formatted multiplier as percentage.
     */
    public function getMultiplierPercentageAttribute(): string
    {
        return number_format($this->multiplier * 100, 0) . '%';
    }
}
