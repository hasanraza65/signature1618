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