<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Notification extends Model
{

    protected $table = 'notifications';
    public $timestamps = true;
    protected $fillable = array('type', 'data','read_at');

    public function user()
    {
        return $this->belongsTo(User::class,'notifiable');
    }

}