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
        $data = UserGlobalSetting::where('user_id', Auth::user()->id)->first();

        // Retrieve the authenticated user's data
        $userData = Auth::user();

        // Convert both objects to arrays
        $dataArray = $data ? $data->toArray() : [];
        $userDataArray = $userData ? $userData->toArray() : [];

        // Merge the arrays
        $mergedData = array_merge($dataArray, $userDataArray);

        // Return the merged data in the JSON response
        return response()->json([
            'data' => $mergedData,
            'message' => 'Success'
        ], 200);
    }


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

    }

    /*
    public function supportMail(Request $request)
    {
        $email = 'ranahasanraza24@gmail.com';
        $subject = 'New support request: ' . $request->subject;
        $dataUser = [
            'email' => Auth::user()->email,
            'subject' => $request->subject,
            'feature_related_query' => $request->feature_related_query,
            'message' => $request->message,
        ];

        $file = $request->hasFile('attachment') ? $request->file('attachment') : null;

        try {
            Mail::to($email)->send(new \App\Mail\SupportMail($dataUser, $subject, $file));
            //Log::info('Email sent successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to send email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Email sent successfully.'
        ], 200);
    } */

}
