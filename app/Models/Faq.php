<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Translatable\HasTranslations;

class Faq extends Model 
{
    use HasTranslations;
    protected $table = 'faqs';
    public $translatable = ['title', 'description'];
    protected $fillable = [
        'title',
        'description',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope to get only active FAQs
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope to order FAQs by sort_order
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get translated title
     */
    public function getTranslatedTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->getTranslation('title', $locale) ?? $this->getTranslation('title', 'en') ?? '';
    }

    /**
     * Get translated description
     */
    public function getTranslatedDescription(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->getTranslation('description', $locale) ?? $this->getTranslation('description', 'en') ?? '';
    }
}
