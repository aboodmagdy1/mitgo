<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripPayment extends Model
{
    protected $fillable = [
        'trip_id',
        'payment_method_id',
        'commission_rate',
        'commission_amount',
        'total_amount',
        'final_amount',
        'driver_earning',
        'status',
        'coupon_discount',
        'coupon_id',
        'additional_fees',
        'transaction_id',
    ];

    protected $casts = [
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'final_amount' => 'decimal:2',
        'driver_earning' => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'additional_fees' => 'decimal:2',
        'status' => 'integer',
        'transaction_id' => 'string',
    ];

    /**
     * Get the trip that this payment belongs to.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the payment method used.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the coupon used for discount.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Check if payment is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 1;
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 0;
    }

    /**
     * Check if payment failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 2;
    }

    /**
     * Get the base fare (total before additional fees and discounts).
     */
    public function getBaseFareAttribute(): float
    {
        return $this->total_amount - $this->additional_fees + ($this->coupon_discount ?? 0);
    }
}

