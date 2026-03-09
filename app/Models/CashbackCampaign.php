<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\CashbackUsage;
use App\Models\User;

class CashbackCampaign extends Model
{
    const TYPE_FIXED_AMOUNT = 1;
    const TYPE_PERCENTAGE = 2;

    protected $fillable = [
        'name',
        'description',
        'type',
        'amount',
        'max_cashback_amount',
        'can_stack_with_coupon',
        'start_date',
        'end_date',
        'is_active',
        'max_trips_per_user',
        'max_trips_global',
        'used_trips_global',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'max_cashback_amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function cashbackUsages(): HasMany
    {
        return $this->hasMany(CashbackUsage::class);
    }

    public function getTypeNameAttribute(): string
    {
        return $this->type === self::TYPE_PERCENTAGE ? 'نسبة مئوية' : 'مبلغ ثابت';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailableForNow(Builder $query): Builder
    {
        $now = now();

        return $query->active()
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function (Builder $q) use ($now) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $now);
            });
    }

    public function isWithinDateWindow(): bool
    {
        $now = now();

        if ($this->start_date && $now->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $now->gt($this->end_date)) {
            return false;
        }

        return true;
    }

    public function isAvailableForUser(User $user): bool
    {
        if (!$this->is_active || !$this->isWithinDateWindow()) {
            return false;
        }

        if ($this->max_trips_global !== null && $this->used_trips_global >= $this->max_trips_global) {
            return false;
        }

        if ($this->max_trips_per_user === null) {
            return true;
        }

        $userUsageCount = $this->cashbackUsages()
            ->where('user_id', $user->id)
            ->count();

        return $userUsageCount < $this->max_trips_per_user;
    }
}

