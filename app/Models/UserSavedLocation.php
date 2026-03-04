<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSavedLocation extends Model
{
    protected $fillable = [
        'user_id',
        'address',
        'lat',
        'long',
        'name',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
