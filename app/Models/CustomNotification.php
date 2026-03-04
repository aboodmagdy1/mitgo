<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class CustomNotification extends Model 
{
    use HasTranslations;

    public $translatable = ['title', 'body'];

    protected $table = 'custom_notifications';
    public $timestamps = true;
    protected $fillable = array('title', 'body', 'notifiable', 'metadata', 'is_read');

    protected $casts = [
        'metadata' => 'array',
    ];

}