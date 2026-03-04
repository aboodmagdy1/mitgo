<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PaymentMethod extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia;
    protected $table = 'payment_methods';
    public $timestamps = true;
    protected $fillable = ['name'];
    public $translatable = ['name'];



}
