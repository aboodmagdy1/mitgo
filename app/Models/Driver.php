<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;

class Driver extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'is_approved',
        'date_of_birth',
        'national_id',
        'license_number',
        'absher_phone',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'date_of_birth' => 'date',
        'status' => 'integer',
    ];

    /**
     * Get the user that owns the driver profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the vehicle for the driver.
     */
    public function vehicle(): HasOne
    {
        return $this->hasOne(DriverVehicle::class);
    }

    /**
     * Get all trips for the driver.
     */
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    /**
     * Get all ratings for the driver.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(TripRating::class);
    }

    /**
     * Get average rating for the driver.
     */
    public function averageRating()
    {
        return $this->ratings()->avg('rating');
    }

    /**
     * Check if driver is online.
     */
    public function isOnline(): bool
    {
        return $this->status === 1;
    }

    /**
     * Check if driver is offline.
     */
    public function isOffline(): bool
    {
        return $this->status === 0;
    }

    /**
     * Check if driver is approved.
     */
    public function isApproved(): bool
    {
        return $this->is_approved === true;
    }

    /**
     * Check if driver is pending approval.
     */
    public function isPendingApproval(): bool
    {
        return $this->is_approved === false;
    }

    /**
     * Approve the driver (one-time action).
     */
    public function approve(): bool
    {
        if ($this->isApproved()) {
            return false; // Already approved, cannot approve again
        }
        
        return $this->update(['is_approved' => true]);
    }

    /**
     * Check if driver can receive trip requests.
     * Driver must be approved, active, and online.
     */
    public function canReceiveTrips(): bool
    {
        return $this->isApproved() && 
               $this->user->is_active && 
               $this->isOnline();
    }

    /**
     * Get all withdraw requests for the driver.
     */
    public function withdrawRequests(): HasMany
    {
        return $this->hasMany(DriverWithdrawRequest::class);
    }

    /**
     * Get pending withdraw requests for the driver.
     */
    public function pendingWithdrawRequests(): HasMany
    {
        return $this->hasMany(DriverWithdrawRequest::class)->where('is_approved', false);
    }

    /**
     * Get approved withdraw requests for the driver.
     */
    public function approvedWithdrawRequests(): HasMany
    {
        return $this->hasMany(DriverWithdrawRequest::class)->where('is_approved', true);
    }

    /**
     * Get the wallet balance through user relationship.
     */
    public function getWalletBalance(): float
    {
        return $this->user->balance / 100 ?? 0;
    }

    /**
     * Get the wallet balance formatted.
     */
    public function getFormattedBalanceAttribute(): string
    {
        return $this->getWalletBalance() . ' ' . config('app.currency', 'SAR');
    }

    /**
     * Deposit money to driver's wallet.
     */
    public function deposit(float $amount, array $meta = []): \Bavix\Wallet\Models\Transaction
    {
        return $this->user->deposit($amount, $meta);
    }

    /**
     * Withdraw money from driver's wallet.
     */
    public function withdraw(float $amount, array $meta = []): \Bavix\Wallet\Models\Transaction
    {
        return $this->user->withdraw($amount, $meta);
    }

    /**
     * Check if driver has sufficient balance.
     */
    public function canWithdraw(float $amount): bool
    {
        return $this->user->canWithdraw($amount);
    }

    /**
     * Get all wallet transactions for this driver.
     */
    public function getWalletTransactions()
    {
        return $this->user->transactions();
    }

    /*
     |--------------------------------------------------------------------------
     | Query Scopes (for driver search)
     |--------------------------------------------------------------------------
     */

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 1);
    }

    public function scopeUserActive($query)
    {
        // Ensure related user is active
        return $query->whereHas('user', function ($q) {
            $q->where('is_active', true);
        });
    }

    public function scopeWithUserLocation($query)
    {
        // Join users to get latest lat/long for distance filtering
        return $query->join('users', 'drivers.user_id', '=', 'users.id')
            ->whereNotNull('users.latest_lat')
            ->whereNotNull('users.latest_long')
            ->addSelect('drivers.*', 'users.latest_lat', 'users.latest_long');
    }

    public function scopeHasVehicleType($query, int $vehicleTypeId)
    {
        return $query->whereHas('vehicle', function ($q) use ($vehicleTypeId) {
            $q->whereIn('vehicle_type_id', [$vehicleTypeId,1]);
        });
    }

    public function scopeWithoutActiveTrips($query)
    {
        return $query->whereDoesntHave('trips', function ($q) {
            $q->whereIn('status', [
                \App\Enums\TripStatus::IN_ROUTE_TO_PICKUP,
                \App\Enums\TripStatus::PICKUP_ARRIVED,
                \App\Enums\TripStatus::IN_PROGRESS,
            ]);
        });
    }

    public function scopeWithinBoundingBox($query, float $lat, float $lng, float $radiusKm)
    {
        // Quick pre-filter using a bounding box to reduce rows before Haversine
        $latDelta = $radiusKm / 111.045; // ~111.045 km per degree latitude
        $lngDelta = $radiusKm / (111.045 * max(cos(deg2rad(max(min($lat, 89.9999), -89.9999))), 0.00001));

        $minLat = $lat - $latDelta;
        $maxLat = $lat + $latDelta;
        $minLng = $lng - $lngDelta;
        $maxLng = $lng + $lngDelta;

        return $query->whereBetween('users.latest_lat', [$minLat, $maxLat])
            ->whereBetween('users.latest_long', [$minLng, $maxLng]);
    }

    public function scopeWithinDistance($query, float $lat, float $lng, float $radiusKm)
    {
        // Accurate filter using Haversine formula (Earth radius ~6371 km)
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(users.latest_lat)) * cos(radians(users.latest_long) - radians(?)) + sin(radians(?)) * sin(radians(users.latest_lat))))";

        return $query->selectRaw(
                "drivers.*, users.latest_lat, users.latest_long, {$haversine} AS distance",
                [$lat, $lng, $lat]
            )
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance', 'asc');
    }
}
