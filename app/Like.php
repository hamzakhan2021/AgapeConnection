<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $fillable = [
        'user_id', 'picture_id','status'
    ];

    protected $hidden = [
        'created_at','updated_at'
     ];
 

    public function pictureLikes()
    {
        return $this->belongsTo('App\UserProfile');
    }
}
