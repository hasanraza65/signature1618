<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\SupportMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SupportMailController extends Controller
{
    public function index(){

        $data = SupportMail::where('user_id',Auth::user()->id)->get();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);


    }

    public function store(Request $request){

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

            $data = new SupportMail();
            $data->subject = $request->subject;
            $data->feature_related_query = $request->feature_related_query;
            $data->message = $request->message;
            $data->user_id = Auth::user()->id;
            $data->user_email = Auth::user()->email;
            $data->save();


            Mail::to($email)->send(new \App\Mail\SupportMail($dataUser, $subject, $file));
            //Log::info('Email sent successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to send email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Email sent successfully.'
        ], 200);

    }

    public function show($id){

        $data = SupportMail::find($id);

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);


    }
}
