<?php

use Illuminate\Support\Facades\Auth;

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
    function getUserName($request=null){
        if(Auth::user()->use_company == 1){
            return Auth::user()->company;
        }else{
            return Auth::user()->name.' '.Auth::user()->last_name;
        }
    }
}
