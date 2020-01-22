<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\UserProfile;

class Comment extends Model
{
    protected $fillable = [
        'user_id', 'picture_id','comment'
    ];

    protected $hidden = [
       'created_at','updated_at'
    ];

    public function pictureComment()
    {
        return $this->belongsTo('App\UserProfile','picture_id');
    }
}
