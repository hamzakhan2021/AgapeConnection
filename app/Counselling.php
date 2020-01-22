<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Counselling extends Model
{
    protected $fillable = [
        'user_id', 'date', 'time_slot', 'counselling_type'
    ];
}
