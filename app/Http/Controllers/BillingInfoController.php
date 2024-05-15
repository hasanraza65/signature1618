<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BillingInfo;
use Auth;

class BillingInfoController extends Controller
{
    public function index(){

        $data = BillingInfo::orderBy('id','desc')
        ->where('user_id',Auth::user()->id)
        ->first();
        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);

    }

    public function update(Request $request){

        $data = BillingInfo::orderBy('id','desc')
        ->where('user_id',Auth::user()->id)
        ->first();

        if($data){
            $data->update($request->all());
        }else{

            $request['user_id'] = Auth::user()->id;
            $data = new BillingInfo();
            $data->create($request->all());

        }

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);
        
    }
}
