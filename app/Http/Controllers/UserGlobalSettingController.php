<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserGlobalSetting;
use Auth;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UserGlobalSettingController extends Controller
{
    public function index()
    {
        // Retrieve the user's global settings
        $data = UserGlobalSetting::where('user_id', Auth::user()->id)->get();

        // Return the merged data in the JSON response
        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);
    }

    public function show($meta_key){

        $data = UserGlobalSetting::where('user_id', Auth::user()->id)
        ->where('meta_key',$meta_key)
        ->first();

        // Return the merged data in the JSON response
        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }


    public function store(Request $request){

        $user_id = Auth::user()->id;
        //$data = new UserGlobalSetting();
        //$data->user_id = $user_id;

        foreach ($request->all() as $key => $value) {
            if ($key !== '_token') { // Skip the CSRF token if present
                // Check if the meta_key already exists for the user
                $existingSetting = UserGlobalSetting::where('user_id', $user_id)
                                                     ->where('meta_key', $key)
                                                     ->first();
                
                if ($existingSetting) {
                    // Update the existing record
                    $existingSetting->meta_value = $value;
                    $existingSetting->save();
                } else {
                    // Create a new record
                    $newSetting = new UserGlobalSetting();
                    $newSetting->user_id = $user_id;
                    $newSetting->meta_key = $key;
                    $newSetting->meta_value = $value;
                    $newSetting->save();
                }
            }
        }

        return response()->json([
            'data' => $request->all(),
            'message' => 'Success'
        ],200);

    }

    /*
    public function store(Request $request){

        $dataObj = UserGlobalSetting::where('user_id',Auth::user()->id)->first();

        if(!$dataObj){
            $data = new UserGlobalSetting();
            $data->user_id = Auth::user()->id;
        }else{
            $data = UserGlobalSetting::where('user_id',Auth::user()->id)->first();
        }

        if(isset($request->decline_sign)){
        $data->decline_sign = $request->decline_sign;
        }


        $data->save();

        $user = Auth::user();
        if(isset($request->company)){
            $user->company = $request->company;
        }

        if(isset($request->use_company)){
            $user->use_company = $request->use_company;
        }

        $user->update();

        return response()->json([
            'data' => $request->all(),
            'message' => 'Success'
        ],200);

    } */

}
