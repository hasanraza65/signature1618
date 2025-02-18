<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserRequest;
use App\Models\Signer;
use App\Models\User;
use App\Models\Contact;

class AuditTrailController extends Controller
{
    public function index(Request $request){

        UserRequest::findOrFail($request->request_id);

        $data = UserRequest::with([
            'signers',
            'signers.signerContactDetail',
            'signers.signerContactDetail.contactUserDetail.requestLogs' => function ($query) use ($request) {
                $query->where('request_id', $request->request_id); // Apply request_id filter here
            },
            'userDetail'
        ])
        ->where('id', $request->request_id)
        ->first();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function getSignCertificate(Request $request){

        $request_data = UserRequest::where('unique_id',$request->request_unique_id)->first();

        if (!$request_data) {
            return response()->json([
                'message' => 'No request data available.'
            ], 400);
        }

        $sender_user = User::find($request_data->user_id);

        $signer_data = Signer::where('unique_id',$request->signer_unique_id)->first();

       

        if (!$signer_data) {
            return response()->json([
                'message' => 'No signer data available.'
            ], 400);
        }

        $signer_contact = Contact::where('contact_user_id',$signer_data->recipient_user_id)->first();
        $signer_user = User::find($signer_data->recipient_user_id);

        $data = [
            "sign_ref_num" => $signer_data->unique_id ?? "",
            "signer_name" => $signer_contact->contact_first_name.' '.$signer_contact->contact_last_name,
            "signer_email" => $signer_user->email ?? "",
            "received_at" => $request_data->sent_date ?? "",
            "signed_at" => $signer_data->signed_date ?? "",
            "signer_location" => $signer_data->signer_ip_address ?? "",
            "signer_ip_address" => $signer_data->signer_ip_address ?? "",
            "sender_name" => $sender_user->name.' '.$sender_user->user_last_name,
            "sender_email" => $sender_user->email,
            "signed_image" => $signer_data->signed_image,
            "original_file_name"=>$request_data->original_file_name,
            "file_name" => $request_data->file_name

        ];

        return response()->json([
            'data'=>$data,
            'message' => 'Success'
        ]);

    }
}
