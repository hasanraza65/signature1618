<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Auth;

class ProfileManagementController extends Controller
{
    public function profileData(){

        $data = User::find(Auth::user()->id)->first();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function  updateProfileData(Request $request){

        $data = User::find(Auth::user()->id)->first();

        if(!$data){
            return response()->json([
                'message' => 'Error: No data available.'
            ], 400);
        }

        $data->name = $request->name;
        $data->phone = $request->phone;
        $data->language = $request->language;
        $data->update();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }
}
