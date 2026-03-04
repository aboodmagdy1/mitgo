<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class City extends Model 
{
    use HasTranslations;

    protected $table = 'cities';
    public $timestamps = true;
    protected $fillable = array('name');
    public $translatable = ['name'];

    public function users()
    {
        return $this->hasMany('App\Models\User');
    }

    public function requests()
    {
        return $this->hasMany('App\Models\Request');
    }

}