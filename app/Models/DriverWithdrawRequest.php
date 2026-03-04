<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class DriverWithdrawRequest extends Model
{
    protected $table = 'driver_withdraw_reqeusts';

    protected $fillable = [
        'driver_id',
        'amount',
        'is_approved',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_approved' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the driver that owns the withdraw request.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the formatted amount with currency.
     */
    protected function formattedAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => number_format((float) $this->amount, 2) . ' ' . config('app.currency', 'SAR'),
        );
    }

    /**
     * Get the status badge color.
     */
    public function getStatusColor(): string
    {
        return $this->is_approved ? 'success' : 'warning';
    }

    /**
     * Get the status text.
     */
    public function getStatusText(): string
    {
        return $this->is_approved ? __('Approved') : __('Pending');
    }

    /**
     * Scope for pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    /**
     * Scope for approved requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
}
