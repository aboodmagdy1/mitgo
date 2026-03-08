<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
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
        'approval_status',
        'date_of_birth',
        'national_id',
        'absher_phone',
    ];

    protected $casts = [
        'approval_status' => ApprovalStatus::class,
        'date_of_birth'   => 'date',
        'status'          => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): HasOne
    {
        return $this->hasOne(DriverVehicle::class);
    }

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(TripRating::class);
    }

    public function averageRating()
    {
        return $this->ratings()->avg('rating');
    }

    /*
    |--------------------------------------------------------------------------
    | Status helpers
    |--------------------------------------------------------------------------
    */

    public function isOnline(): bool
    {
        return $this->status === 1;
    }

    public function isOffline(): bool
    {
        return $this->status === 0;
    }

    /*
    |--------------------------------------------------------------------------
    | Approval helpers
    |--------------------------------------------------------------------------
    */

    public function isApproved(): bool
    {
        return $this->approval_status === ApprovalStatus::APPROVED;
    }

    public function isPendingApproval(): bool
    {
        return $this->approval_status === ApprovalStatus::PENDING;
    }

    public function isInProgress(): bool
    {
        return $this->approval_status === ApprovalStatus::IN_PROGRESS;
    }

    public function isRejected(): bool
    {
        return $this->approval_status === ApprovalStatus::REJECTED;
    }

    /**
     * Approve the driver (one-time action).
     */
    public function approve(): bool
    {
        if ($this->isApproved()) {
            return false;
        }

        return $this->update([
            'approval_status' => ApprovalStatus::APPROVED,
        ]);
    }

    /**
     * Move driver to in-progress inspection stage.
     */
    public function moveToInProgress(): bool
    {
        if (! $this->isPendingApproval()) {
            return false;
        }

        return $this->update([
            'approval_status' => ApprovalStatus::IN_PROGRESS,
        ]);
    }

    /**
     * Reject the driver application.
     */
    public function reject(): bool
    {
        if ($this->isApproved()) {
            return false;
        }

        return $this->update([
            'approval_status' => ApprovalStatus::REJECTED,
        ]);
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

    /*
    |--------------------------------------------------------------------------
    | Wallet helpers
    |--------------------------------------------------------------------------
    */

    public function withdrawRequests(): HasMany
    {
        return $this->hasMany(DriverWithdrawRequest::class);
    }

    public function pendingWithdrawRequests(): HasMany
    {
        return $this->hasMany(DriverWithdrawRequest::class)->where('is_approved', false);
    }

    public function approvedWithdrawRequests(): HasMany
    {
        return $this->hasMany(DriverWithdrawRequest::class)->where('is_approved', true);
    }

    public function getWalletBalance(): float
    {
        return $this->user->balance / 100 ?? 0;
    }

    public function getFormattedBalanceAttribute(): string
    {
        return $this->getWalletBalance() . ' ' . config('app.currency', 'SAR');
    }

    public function deposit(float $amount, array $meta = []): \Bavix\Wallet\Models\Transaction
    {
        return $this->user->deposit($amount, $meta);
    }

    public function withdraw(float $amount, array $meta = []): \Bavix\Wallet\Models\Transaction
    {
        return $this->user->withdraw($amount, $meta);
    }

    public function canWithdraw(float $amount): bool
    {
        return $this->user->canWithdraw($amount);
    }

    public function getWalletTransactions()
    {
        return $this->user->transactions();
    }

    /*
    |--------------------------------------------------------------------------
    | Query Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeApproved($query)
    {
        return $query->where('approval_status', ApprovalStatus::APPROVED->value);
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', ApprovalStatus::PENDING->value);
    }

    public function scopeInProgress($query)
    {
        return $query->where('approval_status', ApprovalStatus::IN_PROGRESS->value);
    }

    public function scopeRejected($query)
    {
        return $query->where('approval_status', ApprovalStatus::REJECTED->value);
    }

    public function scopeOnline($query)
    {
        return $query->where('status', 1);
    }

    public function scopeUserActive($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('is_active', true);
        });
    }

    public function scopeWithUserLocation($query)
    {
        return $query->join('users', 'drivers.user_id', '=', 'users.id')
            ->whereNotNull('users.latest_lat')
            ->whereNotNull('users.latest_long')
            ->addSelect('drivers.*', 'users.latest_lat', 'users.latest_long');
    }

    public function scopeHasVehicleType($query, int $vehicleTypeId)
    {
        return $query->whereHas('vehicle', function ($q) use ($vehicleTypeId) {
            $q->whereIn('vehicle_type_id', [$vehicleTypeId, 1]);
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
        $latDelta = $radiusKm / 111.045;
        $lngDelta = $radiusKm / (111.045 * max(cos(deg2rad(max(min($lat, 89.9999), -89.9999))), 0.00001));

        return $query->whereBetween('users.latest_lat', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('users.latest_long', [$lng - $lngDelta, $lng + $lngDelta]);
    }

    public function scopeWithinDistance($query, float $lat, float $lng, float $radiusKm)
    {
        $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(users.latest_lat)) * cos(radians(users.latest_long) - radians(?)) + sin(radians(?)) * sin(radians(users.latest_lat))))";

        return $query->selectRaw(
                "drivers.*, users.latest_lat, users.latest_long, {$haversine} AS distance",
                [$lat, $lng, $lat]
            )
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance', 'asc');
    }
}
