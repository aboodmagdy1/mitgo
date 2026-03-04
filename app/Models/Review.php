<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider_id',
        'rating',
        'comment'
    ];

    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Get the user that wrote the review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the provider being reviewed
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Boot method to handle cache clearing when reviews are updated
     */
    protected static function booted()
    {
        static::created(function ($review) {
            static::clearProviderRatingCache($review->provider_id);
        });

        static::updated(function ($review) {
            static::clearProviderRatingCache($review->provider_id);
        });

        static::deleted(function ($review) {
            static::clearProviderRatingCache($review->provider_id);
        });
    }

    /**
     * Clear the cached rating for a specific provider
     */
    public static function clearProviderRatingCache(int $providerId): void
    {
        Cache::forget("provider_rating_{$providerId}");
    }

    /**
     * Calculate and cache the average rating for a provider (last 3 months)
     */
    public static function calculateProviderRating(int $providerId): float
    {
        $cacheKey = "provider_rating_{$providerId}";
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($providerId) {
            $threeMonthsAgo = now()->subMonths(3);
            
            $reviews = static::where('provider_id', $providerId)
                ->where('created_at', '>=', $threeMonthsAgo)
                ->get();
            
            if ($reviews->isEmpty()) {
                return 0.0;
            }
            
            $totalRating = $reviews->sum('rating');
            $averageRating = $totalRating / $reviews->count();
            
            return round($averageRating, 2);
        });
    }

    /**
     * Get the total number of reviews for a provider (last 3 months)
     */
    public static function getProviderReviewCount(int $providerId): int
    {
        $threeMonthsAgo = now()->subMonths(3);
        
        return static::where('provider_id', $providerId)
            ->where('created_at', '>=', $threeMonthsAgo)
            ->count();
    }
}
