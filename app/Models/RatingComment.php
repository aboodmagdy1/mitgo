<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class RatingComment extends Model
{
    use HasTranslations;
    protected $fillable = [
        'comment',
        'is_positive',
        'active',
    ];

    /**
     * Translatable attributes.
     */
    public $translatable = ['comment'];

    protected $casts = [
        'is_positive' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * Get all trip ratings that use this comment.
     */
    public function tripRatings(): HasMany
    {
        return $this->hasMany(TripRating::class);
    }

    /**
     * Scope a query to only include active comments.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include positive comments.
     */
    public function scopePositive($query)
    {
        return $query->where('is_positive', true);
    }

    /**
     * Scope a query to only include negative comments.
     */
    public function scopeNegative($query)
    {
        return $query->where('is_positive', false);
    }

    /**
     * Get the comment text for a specific locale.
     */
    public function getCommentText(string $locale = 'en'): string
    {
        return $this->getTranslation('comment', $locale) ?? $this->getTranslation('comment', 'en') ?? '';
    }
}
