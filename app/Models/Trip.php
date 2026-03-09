<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOne as HasOneRelation;
use App\Enums\TripStatus;
use App\Enums\TripType;
use App\Enums\TripPaymentType;
use Illuminate\Contracts\Database\Query\Builder;

class Trip extends Model
{
    protected $fillable = [
        'user_id',
        'zone_id',
        'vehicle_type_id',
        'driver_id',
        'coupon_id',
        'type',
        'number',
        'status',
        'scheduled_date',
        'scheduled_time',
        'is_scheduled',
        'scheduled_at',
        'payment_method_id',
        'pickup_lat',
        'pickup_long',
        'pickup_address',
        'dropoff_lat',
        'dropoff_long',
        'dropoff_address',
        'distance',
        'estimated_duration',
        'actual_duration',
        'cancel_reason_id',
        'cancellation_fee',
        'waiting_fee',
        'estimated_fare',
        'actual_fare',
        'started_at',
        'ended_at',
        'arrived_at',
    ];

    protected $casts = [
        'type' => TripType::class,
        'status' => TripStatus::class,
        'scheduled_date' => 'date',
        'scheduled_time' => 'datetime',
        'is_scheduled' => 'boolean',
        'scheduled_at' => 'datetime',
        'pickup_lat' => 'decimal:8',
        'pickup_long' => 'decimal:8',
        'dropoff_lat' => 'decimal:8',
        'dropoff_long' => 'decimal:8',
        'distance' => 'decimal:2',
        'estimated_duration' => 'integer',
        'actual_duration' => 'integer',
        'cancellation_fee' => 'decimal:2',
        'waiting_fee' => 'decimal:2',
        'estimated_fare' => 'decimal:2',
        'actual_fare' => 'decimal:2',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'arrived_at' => 'datetime',
    ];

    /**
     * Get the user that requested the trip.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    /**
     * Get the payment method for the trip.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the driver assigned to the trip.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the zone for the trip.
     */
    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Get the vehicle type for the trip.
     */
    public function vehicleType(): BelongsTo
    {
        return $this->belongsTo(VehicleType::class);
    }

    /**
     * Get the cancel reason for the trip.
     */
    public function cancelReason(): BelongsTo
    {
        return $this->belongsTo(CancelTripReason::class);
    }

    /**
     * Get all ratings for the trip.
     */
    public function rate(): HasOne
    {
        return $this->hasOne(TripRating::class);
    }

    /**
     * Get the coupon used for this trip.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the coupon usage records for this trip.
     */
    public function couponUsages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function cashbackUsage(): HasOneRelation
    {
        return $this->hasOne(CashbackUsage::class);
    }

    public function scopeActive(Builder $query)
    {
        return $query->whereIn('status', [
            TripStatus::SEARCHING,  
            TripStatus::NO_DRIVER_FOUND,
            TripStatus::IN_PROGRESS,
            TripStatus::PICKUP_ARRIVED,
            TripStatus::IN_ROUTE_TO_PICKUP,
            TripStatus::COMPLETED_PENDING_PAYMENT,
        ]);
    }

    /**
     * Get the payment record for this trip.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(TripPayment::class);
    }

    /**
     * Get the trip status text.
     */
    public function getStatusTextAttribute(): string
    {
        return $this->status?->label() ?? __('Unknown');
    }


    /**
     * Check if trip is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === TripStatus::COMPLETED;
    }
    public function completed(): bool
    {
        return $this->status === TripStatus::COMPLETED;
    }

    /**
     * Check if trip is cancelled.
     */
    public function isCancelled(): bool
    {
        return in_array($this->status, [
            TripStatus::CANCELLED_BY_DRIVER,
            TripStatus::CANCELLED_BY_RIDER,
            TripStatus::CANCELLED_BY_SYSTEM,
        ]);
    }

    /**
     * Check if trip is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === TripStatus::IN_PROGRESS;
    }

    /**
     * Check if the free waiting time has expired since driver arrived at pickup.
     */
    public function hasWaitingTimeExpired(): bool
    {
        if (!$this->arrived_at) {
            \Illuminate\Support\Facades\Log::info('hasWaitingTimeExpired: No arrived_at timestamp', ['trip_id' => $this->id]);
            return false;
        }

        // Get free waiting time directly from settings to avoid helper function issues
        $generalSettings = app(\App\Settings\GeneralSettings::class);
        $freeWaitingTime = $generalSettings->free_waiting_time ?? 5; // in minutes, default 5
        
        // Use copy() to avoid mutating the original timestamp
        $waitingTimeLimit = $this->arrived_at->copy()->addMinutes($freeWaitingTime);
        $hasExpired = now()->greaterThan($waitingTimeLimit);
        
        \Illuminate\Support\Facades\Log::info('hasWaitingTimeExpired result', [
            'trip_id' => $this->id,
            'arrived_at' => $this->arrived_at->toDateTimeString(),
            'free_waiting_time' => $freeWaitingTime,
            'waiting_time_limit' => $waitingTimeLimit->toDateTimeString(),
            'current_time' => now()->toDateTimeString(),
            'has_expired' => $hasExpired
        ]);
        
        return $hasExpired;
    }
}
