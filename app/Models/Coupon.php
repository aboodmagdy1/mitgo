<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'amount',
        'max_discount_amount',
        'start_date',
        'end_date',
        'total_usage_limit',
        'usage_limit_per_user',
        'used_count',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'amount' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Constants for coupon types
    const TYPE_PERCENTAGE = 1;
    const TYPE_FIXED_AMOUNT = 2;

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function getTypeNameAttribute(): string
    {
        return $this->type === self::TYPE_PERCENTAGE ? 'نسبة مئوية' : 'مبلغ ثابت';
    }

    public function getRemainingUsageAttribute(): ?int
    {
        if (!$this->total_usage_limit) {
            return null; // unlimited
        }
        return max(0, $this->total_usage_limit - $this->used_count);
    }

    public function isExpired(): bool
    {
        if (!$this->end_date) {
            return false;
        }
        return now()->isAfter($this->end_date);
    }

    public function isNotStarted(): bool
    {
        if (!$this->start_date) {
            return false;
        }
        return now()->isBefore($this->start_date);
    }

    public function isAvailable(): bool
    {
        return $this->is_active && 
               !$this->isExpired() && 
               !$this->isNotStarted() && 
               ($this->total_usage_limit === null || $this->used_count < $this->total_usage_limit);
    }
    public function availableForUser(User $user): bool
    {
        return $this->usage_limit_per_user === null || $this->usages()->where('user_id', $user->id)->count() < $this->usage_limit_per_user;
    }
    // store that this coupon is used by this user
}
