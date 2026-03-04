<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripRating extends Model
{
    protected $fillable = [
        'trip_id',
        'user_id',
        'driver_id',
        'rating',
        'rating_comment_id',
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Get the trip that was rated.
     */
    public function trip(): BelongsTo
    {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Get the user who gave the rating.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the driver who received the rating.
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Get the rating comment.
     */
    public function ratingComment(): BelongsTo
    {
        return $this->belongsTo(RatingComment::class);
    }

    /**
     * Scope a query to only include ratings above a certain value.
     */
    public function scopeMinRating($query, int $rating)
    {
        return $query->where('rating', '>=', $rating);
    }

    /**
     * Scope a query to only include ratings below a certain value.
     */
    public function scopeMaxRating($query, int $rating)
    {
        return $query->where('rating', '<=', $rating);
    }

    /**
     * Check if rating is positive (4-5 stars).
     */
    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    /**
     * Check if rating is negative (1-2 stars).
     */
    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }

    /**
     * Check if rating is neutral (3 stars).
     */
    public function isNeutral(): bool
    {
        return $this->rating === 3;
    }
}
