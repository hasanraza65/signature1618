<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\User;

class GlobalSettingController extends Controller
{
    public function useCompany(Request $request){

        $data = Auth::user();
        $data->use_company = $request->use_company;
        $data->update();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);

    }
}
