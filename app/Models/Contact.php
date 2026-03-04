<?php

namespace App\Models;   

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;

class Contact extends Model 
{
    use InteractsWithMedia;

    protected $table = 'contacts';
    public $timestamps = true;
    protected $fillable = array('name', 'email', 'message', 'type', 'is_read');

}