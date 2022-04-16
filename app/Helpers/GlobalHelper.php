<?php

namespace App\Helpers;
use Illuminate\Support\Facades\DB;

class GlobalHelper
{
    public static function slugify($text, string $divider = '-')
    {
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, $divider);
        $text = preg_replace('~-+~', $divider, $text);
        $text = strtolower($text);
        if (empty($text)) {
            return uniqid();
        }
        return $text;
    }

    public function hmsToSeconds($time){
        $arr = explode(':', $time);
        if (count($arr) === 3) {
            return $arr[0] * 3600 + $arr[1] * 60 + $arr[2];
        }
        return $arr[0] * 60 + $arr[1];
    }

    public static function getNearByLabsByRadius($lat, $lon, $radius=20)
    {
        $sql = 'SELECT * FROM labs WHERE (3958*3.1415926*sqrt((geo_lat-' . $lat . ')*(geo_lat-' . $lat . ') + cos(geo_lat/57.29578)*cos(' . $lat . '/57.29578)*(geo_long-' . $lon . ')*(geo_long-' . $lon . '))/180) <= ' . $radius . ';';
        $result = DB::select($sql);
        return $result;
    }

    public static function randomPassword($length=7){
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }

}
