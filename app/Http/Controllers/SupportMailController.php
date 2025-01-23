<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Models\SupportMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SupportMailController extends Controller
{
    public function index(){

        if(Auth::user()->user_role == 2){
            // Get support mails where user is authenticated and userDetail exists
            $data = SupportMail::with('userDetail')
                        ->where('user_id', Auth::user()->id)
                        ->whereHas('userDetail') // Ensure userDetail relation exists
                        ->get();
        } elseif(Auth::user()->user_role == 1){
            // Get all support mails where userDetail exists
            $data = SupportMail::with('userDetail')
                        ->whereHas('userDetail') // Ensure userDetail relation exists
                        ->get();
        }
    
        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);
    }


    public function store(Request $request)
    {
        $email = "support@siganture1618.com";
        $useremail = Auth::user()->email;
        $subject = 'New support request: ' . $request->subject;
        $today = Carbon::now();
        $formattedDate = $today->format('m/d/Y');

        $userName = getUserName($request);
        $dataUser = [
            'email' => Auth::user()->email,
            'receiver_name' => $userName,
            'subject' => $request->subject,
            'feature_related_query' => $request->feature_related_query,
            'message' => $request->message,
            'status' => 'On Going',
            'submission_date' => $formattedDate
        ];

        $file = $request->hasFile('attachment') ? $request->file('attachment') : null;
        $filePath = null;

        if ($file) {
            $directory = 'support_attachments';
            $fileName = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path($directory), $fileName);
            $filePath = $directory . '/' . $fileName;
        }

        try {
            $data = new SupportMail();
            $data->subject = $request->subject;
            $data->feature_related_query = $request->feature_related_query;
            $data->message = $request->message;
            $data->user_id = Auth::user()->id;
            $data->user_email = Auth::user()->email;
            $data->attachment = $filePath;
            $data->save();

            // Pass file path instead of file object
            Mail::to($email)->send(new \App\Mail\SupportMail($dataUser, $subject, $filePath));
            Mail::to($useremail)->send(new \App\Mail\SupportMailUser($dataUser, $subject, $filePath));

        } catch (\Exception $e) {
            Log::error('Failed to send email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => 'Email sent successfully.'
        ], 200);
    }
    public function show($id){

        $data = SupportMail::with('userDetail')->find($id);

        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);


    }

    public function destroy($id){

        $data = SupportMail::find($id);

        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        $data->delete();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);


    }

    public function changeTicketStatus(Request $request){

        $data = SupportMail::find($request->ticket_id);
        $user = User::find($data->user_id);
        $useremail = $user->email;
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        $subject = $request->subject;
        $today = Carbon::now();
        // Format the date
        $formattedDate = $today->format('m/d/Y');

        $submitted_date = $data->created_at;
        $formattedDateSubmitted = $submitted_date->format('m/d/Y');

        $dataUser = [
            'email' => $user->email,
            'receiver_name' => $user->name.' '.$user->last_name,
            'subject' => $data->subject,
            'feature_related_query' => $data->feature_related_query,
            'message' => $data->message,
            'status' => 'Resolved',
            'submission_date' => $formattedDate,
            'formattedDateSubmitted' => $formattedDateSubmitted
        ];

        $data->status = $request->status;
        $data->update();

        if($request->status == 'Solved'){
            Mail::to($useremail)->send(new \App\Mail\TicketResolved($dataUser, $subject));
        }
        

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);

    }
}
