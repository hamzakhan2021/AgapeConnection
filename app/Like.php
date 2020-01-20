<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $fillable = [
        'user_id', 'picture_id','status'
    ];

    public function pictureLikes()
    {
        return $this->belongsTo('App\UserProfile');
    }
}
