<?php

namespace App\Models;

use App\Enums\TripRequestOutcome;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripRequestLog extends Model
{
    protected $fillable = [
        'trip_id',
        'driver_id',
        'outcome',
        'sent_at',
        'resolved_at',
    ];

    protected $casts = [
        'outcome' => TripRequestOutcome::class,
        'sent_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function scopeAccepted($query)
    {
        return $query->where('outcome', TripRequestOutcome::ACCEPTED);
    }

    public function scopeRejected($query)
    {
        return $query->where('outcome', TripRequestOutcome::REJECTED);
    }

    public function scopeResponded($query)
    {
        return $query->whereIn('outcome', [
            TripRequestOutcome::ACCEPTED,
            TripRequestOutcome::REJECTED,
        ]);
    }

    public function scopeInDateRange($query, ?\Carbon\Carbon $from, ?\Carbon\Carbon $to)
    {
        if ($from) {
            $query->where('sent_at', '>=', $from);
        }
        if ($to) {
            $query->where('sent_at', '<=', $to);
        }
        return $query;
    }
}
