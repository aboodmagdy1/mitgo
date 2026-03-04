<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model 
{

    protected $table = 'subscriptions';
    public $timestamps = true;
    protected $fillable = array('provider_id', 'start_date', 'end_date', 'package_id', 'total', 'is_active');

    public function package()
    {
        return $this->belongsTo('App\Models\Package','package_id');
    }

    public function provider()
    {
        return $this->belongsTo('App\Models\Provider','provider_id');
    }

}