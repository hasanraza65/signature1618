<?php

use Illuminate\Support\Facades\Auth;
use App\Models\UserGlobalSetting;

if (!function_exists('getUserId')) {
    function getUserId($request) {
        if (Auth::user()->user_role == 1) {
            return $request->user_id;
        } elseif (Auth::user()->user_role == 2) {
            return Auth::user()->id;
        }
    }

}

if (!function_exists('getUserName')) {
    function getUserName($request = null) {
        if (Auth::check()) {
            $userId = Auth::user()->id;
            $useCompany = UserGlobalSetting::where('user_id', $userId)
                                            ->where('meta_key', 'use_company')
                                            ->value('meta_value');
            $company = UserGlobalSetting::where('user_id', $userId)
                                         ->where('meta_key', 'company')
                                         ->value('meta_value');
            
            if ($useCompany == 1) {
                return $company;
            } else {
                return Auth::user()->name . ' ' . Auth::user()->last_name;
            }
        }
        return null; // Return null if the user is not authenticated
    }
}
