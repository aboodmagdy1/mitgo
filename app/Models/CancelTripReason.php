<?php

namespace App\Models;

use App\Enums\CancelTripReasonType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class CancelTripReason extends Model
{
    use HasFactory, HasTranslations;

    protected  $translatable = ['reason'];
    protected $fillable = [
        'reason',
        'type',
        'is_active',
    ];

    protected $casts = [
        'type' => CancelTripReasonType::class,
        'is_active' => 'boolean',
    ];

    public function scopeByType($query, CancelTripReasonType $type)
    {
        return $query->where('type', $type->value);
    }

    public function scopeForRiders($query)
    {
        return $query->where('type', CancelTripReasonType::Rider->value);
    }

    public function scopeForDrivers($query)
    {
        return $query->where('type', CancelTripReasonType::Driver->value);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
