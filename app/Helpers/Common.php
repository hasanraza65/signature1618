<?php 

namespace App\Helpers;

use Carbon\Carbon;

class Common
{
    public static function dateFormat($date)
    {
        // Ensure $date is a valid Carbon instance
        $date = Carbon::parse($date);

    
        return $date->format('m/d/Y H:i') . ' GMT +2'; 
    }
}
