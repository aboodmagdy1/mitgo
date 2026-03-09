<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashbackUsage extends Model
{
    protected $fillable = [
        'cashback_campaign_id',
        'user_id',
        'trip_id',
        'cashback_amount',
        'wallet_transaction_id',
        'awarded_at',
    ];

    protected $casts = [
        'cashback_amount' => 'decimal:2',
        'awarded_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(CashbackCampaign::class, 'cashback_campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }
}

