<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\UserProfile;

class Comment extends Model
{
    protected $fillable = [
        'user_id', 'picture_id','comment'
    ];

    public function pictureComment()
    {
        return $this->belongsTo('App\UserProfile');
    }
}
