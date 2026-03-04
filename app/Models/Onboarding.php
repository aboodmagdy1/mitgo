<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Model;

class Onboarding extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'onboardings';
    public $timestamps = true;

    protected $fillable = [
        'title',
        'description',
        'order'
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    public function scopeActive($query)
    {
        return $query->where('order', '>', 0);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('onboarding')
            ->singleFile();
    }
}
