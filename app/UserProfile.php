<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id', 'image','status'
    ];

    protected $hidden = [
        'user_id','created_at','updated_at',
    ];

    public function userProfile()
    {
        return $this->belongsTo('App\User');
    }
}
