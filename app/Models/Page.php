<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Page extends Model
{
    use HasTranslations;
    protected $table = "pages";
    protected $fillable = ['title', 'slug', 'page_layout_ar', 'page_layout_en'];
    public $translatable = ['title'];

    /**
     * Get the English title for slug generation
     */
    public function getEnglishTitleAttribute()
    {
        return $this->getTranslation('title', 'en');
    }
}
