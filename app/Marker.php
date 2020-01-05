<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
class Marker extends Model
{
    protected $fillable = [
        'user_id', 'lat', 'lng',
    ];

    public static function getByDistance($lat, $lng, $distance)
    {
        // $results = DB::select(DB::raw('SELECT id, ( 3959 * acos( cos( radians(' . $lat . ') ) * cos( radians( lat ) ) * cos( radians( lng ) - radians(' . $lng . ') ) + sin( radians(' . $lat .') ) * sin( radians(lat) ) ) ) AS distance FROM markers HAVING distance < ' . $distance . ' ORDER BY distance') );
        $circle_radius = 3959;
        $results = DB::select(
            'SELECT * FROM
                 (SELECT id, lat, lng, (' . $circle_radius . ' * acos(cos(radians(' . $lat . ')) * cos(radians(lat)) *
                 cos(radians(lng) - radians(' . $lng . ')) +
                 sin(radians(' . $lat . ')) * sin(radians(lat))))
                 AS distance
                 FROM markers) AS distances
             WHERE distance < ' . $distance . '
         ');
        return $results;
    }
}
