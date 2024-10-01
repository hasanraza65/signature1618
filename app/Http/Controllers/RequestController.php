<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserRequest;
use App\Models\Signer;
use App\Models\RequestField;
use App\Models\Contact;
use App\Models\User;
use App\Models\RequestOtp;
use App\Models\RequestReminderDate;
use App\Models\Approver;
use App\Models\RadioButton;
use App\Models\RequestLog;
use App\Models\UserGlobalSetting;
use Auth;
use DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Services\TwilioService;
use Twilio\Rest\Client;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;

class RequestController extends Controller
{

    protected $twilio;

    public function __construct(TwilioService $twilio)
    {
        $this->twilio = $twilio;
    }

    public function index(){

        if(Auth::user()->user_role == 1){
            $data = UserRequest::with(['userDetail','signers','signers.requestFields','signers.signerContactDetail','approvers','approvers.approverContactDetail', 'approvers.approverContactDetail.contactUserDetail','signers.signerContactDetail.contactUserDetail'])
            ->where('is_trash',0)
            ->orderBy('id','desc')
            ->get();
        }else{
            $data = UserRequest::with(['userDetail','signers','signers.requestFields','signers.signerContactDetail','approvers','approvers.approverContactDetail', 'approvers.approverContactDetail.contactUserDetail','signers.signerContactDetail.contactUserDetail'])
            ->where('user_id',Auth::user()->id)
            ->where('is_trash',0)
            ->orderBy('id','desc')
            ->get();
        }
       
        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);

    }

    public function inbox() {
    $signers = Signer::where('recipient_user_id', Auth::user()->id)->pluck('request_id')->toArray();

    $data = UserRequest::with([
        'signers.requestFields.radioFields', 'userDetail', 'signers', 'signers.requestFields', 
        'signers.signerContactDetail', 'approvers', 'approvers.approverContactDetail', 
        'approvers.approverContactDetail.contactUserDetail', 'signers.signerContactDetail.contactUserDetail'
    ])
    ->whereIn('id', $signers)
    ->where('is_trash', 0)
    ->whereNot('status', 'draft')
    ->orderBy('id', 'desc')
    ->get();

    $responseData = [];

    foreach ($data as $request) {
        // Retrieve the file path for each request
        $filePath = public_path($request->file);
        $signedFilePath = public_path($request->signed_file); // Get the signed file path

        // Handle the regular file
        if (!File::exists($filePath)) {
            $request->pdf_file = null; // File not found, return null
        } else {
            try {
                $fileContent = File::get($filePath);
                //$compressedContent = gzencode($fileContent, 9);
                $base64CompressedContent = base64_encode($fileContent);
                $request->pdf_file = $base64CompressedContent;
            } catch (\Exception $e) {
                $request->pdf_file = null; // In case of an error, return null
            }
        }

        // Handle the signed file
        if (!File::exists($signedFilePath)) {
            $request->signed_pdf_file = null; // Set signed_pdf_file to null if not found
        } else {
            try {
                $signedFileContent = File::get($signedFilePath);
                //$compressedSignedContent = gzencode($signedFileContent, 9);
                $base64CompressedSignedContent = base64_encode($signedFileContent);
                $request->signed_pdf_file = $base64CompressedSignedContent; // Assign signed file content
            } catch (\Exception $e) {
                $request->signed_pdf_file = null; // In case of an error, return null
            }
        }

        // Add the modified request object to the response data
        $responseData[] = $request;
    }

    // Generate response with modified data
    return response()->json([
        'data' => $responseData,
        'message' => 'Success'
    ], 200);
}


public function declineRequest(Request $request){

        $userName = getUserName($request);

        $data = UserRequest::where('unique_id',$request->request_unique_id)->first();

        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        $user = User::find($data->user_id);

        $data->status = $request->request_status;
        $data->update();

        //get company name 
        $globalsettings = UserGlobalSetting::where('user_id',$data->user_id)->where('meta_key','company')->first();
        if(!$globalsettings){
            $company_name = $user->name.' '.$user->last_name;
        }else{
            $company_name = $globalsettings->meta_value;
        }
        //end get company name

            $getsigner = Signer::where('unique_id',$request->signer_unique_id)->first();
            
            $signeruser = User::find($getsigner->recipient_user_id);
            
            $request_data = UserRequest::where('unique_id',$request->request_unique_id)->first();

            $today_date = Carbon::now();
            $formatted_date = $today_date->format('Y-m-d H:i:s');

            $signer = Signer::where('recipient_user_id',$signeruser->id)->where('request_id',$request_data->id)->first();
            $signer->status = "declined";
            $signer->declined_date = $formatted_date;
            $signer->update();

            //adding activity log 
            $this->addRequestLog("declined_request", "Request has been declined", $userName, $request_data->id);
            //ending adding activity log

            //send mail
            $dataUser = [
                'email' => $user->email,
                'sender_name' => $user->name.' '.$user->last_name,
                'requestUID' => $request_data->unique_id,
                'receiver_name' => $signeruser->name.' '.$signeruser->last_name,
                'signerUID' => $signer->unique_id,
                'organization_name' => $company_name,
                'file_name' => $request_data ->file_name
            ];
        
            $subject = 'Request to Sign Declined by '.$signeruser->name.' '.$signeruser->last_name;
        
            Mail::to($user->email)->send(new \App\Mail\DeclineSignSender($dataUser, $subject));
            //ending send mail

            //send mail to signer
            $dataUserSigner = [
                'email' => $signeruser->email,
                'first_name' => $signeruser->name,
                'last_name' => $signeruser->last_name,
                'requestUID' => $request_data->unique_id,
                'organization_name' => $company_name,
                'signerUID' => $signer->unique_id,
                'file_name' => $request_data->file_name
            ];
        
            $subjectSigner = 'Request to Sign Declined';
        
            Mail::to($signeruser->email)->send(new \App\Mail\DeclineSignSigner($dataUserSigner, $subjectSigner));
            //ending send mail to signer

            //send mail to OTHER signer
            $otherSigners = Signer::where('request_id',$request_data->id)->whereNot('recipient_user_id',$signeruser->id)->get();
            

            foreach($otherSigners as $otherSigner){
                
                $otheruser = User::find($otherSigner->recipient_user_id);
                
                $dataUserOtherSigner = [
                    'email' => $otheruser->email,
                    'user_first_name' => $otheruser->name,
                    'user_last_name' => $otheruser->last_name,
                    'declined_by_first_name' => $user->name,
                    'declined_by_last_name' => $user->last_name,
                    'requestUID' => $request_data->unique_id,
                    'organization_name' => $company_name,
                    'signerUID' => $signer->unique_id,
                    'document_name' => $request_data->file_name
                ];
            
                $subjectOtherSigner = 'Request to Sign Declined by '.$user->name.' '.$user->last_name;
            
                Mail::to($otheruser->email)->send(new \App\Mail\DeclineSignOther($dataUserOtherSigner, $subjectOtherSigner));

            }
            
            return response()->json([
                'message' => 'Success'
            ], 200);
            //ending send mail to OTHER signer

    }
    
    
    public function getFileBase($request_id)
    {
        $responseData = [];
        $requestdata = UserRequest::find($request_id);
        
        if (!$requestdata || !$requestdata->file) {
            return response()->json([
                'message' => 'File not found'
            ], 200);
        }
    
        $filePath = public_path($requestdata->file);
        if (!File::exists($filePath)) {
            return response()->json([
                'message' => 'File not found'
            ], 200);
        }
    
        // Read the file content
        $fileContent = File::get($filePath);
        
        // Compress the file content
        $compressedContent = gzencode($fileContent, 9);
        
        // Encode compressed content to base64
        $base64CompressedContent = base64_encode($compressedContent);
        
        return response()->json([
            'data' => $base64CompressedContent,
            'message' => 'Success'
        ], 200);
    }



    public function createDraft(Request $request){

        try {
            // Validate incoming request
            $request->validate([
                'file' => 'required|mimes:pdf',
                'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            $filePath = $this->storeFile($request->file('file'), 'files');
            $originalFileName = $request->file('file')->getClientOriginalName();
    
            // Store thumbnail
            $thumbnailPath = $this->storeFile($request->file('thumbnail'), 'thumbnails');

            $userRequest = new UserRequest();
            $userRequest->user_id = Auth::id();
            $userRequest->file = $filePath;
            $userRequest->thumbnail = $thumbnailPath;
            $userRequest->unique_id = $request->unique_id;
            $userRequest->file_name = $originalFileName;
            $userRequest->save();

            $userName = getUserName($request);

            //adding activity log 
            $this->addRequestLog("new_request", "Signature request created", $userName, $userRequest->id);
            //ending adding activity log

            return response()->json([
                'data' => $userRequest,
                'message' => 'Request created successfully.'
            ], 200);

            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to create request. ' . $e->getMessage() . ' at line '.$e->getLine() 
                ], 500);
            }
    }

    
    public function store(Request $request){
       
        try {
            // Process your data here
            $requestData = $request->all();

            // Check if 'status' key is present
            if (!isset($requestData['status'])) {
                throw new \Exception("Status key is missing in the request data.");
            }
    
            // Assuming you need to access specific keys
            $status = $requestData['status'];
            $uniqueId = $requestData['unique_id'];
            $recipients = $requestData['recipients'];
            if(isset($requestData['reminder_dates']) && $requestData['reminder_dates'] != null){
                $reminder_dates = $requestData['reminder_dates'];
            }
            
            if(isset($requestData['approvers']) && $requestData['approvers'] != null){
                $approvers = $requestData['approvers'];
            }

            //get decline to sign check for current user
            $userglobalsettings = UserGlobalSetting::where('meta_key','decline_sign')->where('user_id',Auth::user()->id)->first();
            $decline_to_sign = 0;
            if($userglobalsettings){
                $decline_to_sign = $userglobalsettings->meta_value;
            }
            //ending get decline to sign check

            $userRequestData = UserRequest::where('unique_id', $uniqueId)->first();

            $userRequestData->email_otp = $requestData['email_otp'] ?? 0;
            $userRequestData->sms_otp = $requestData['sms_otp'] ?? 0;
            $userRequestData->file_name = $requestData['file_name'];
            $userRequestData->status = $request->status;
            if(isset($requestData['expiry_date']) && $requestData['expiry_date'] != null){
                $userRequestData->expiry_date = $requestData['expiry_date'];
            }
            
            $userRequestData->custom_message = $requestData['custom_message'];
            if(isset($approvers) && $approvers != null){
                $userRequestData->approve_status = 0;
            }

            $userName = getUserName($request);

            $userRequestData->sent_date = Carbon::now();
            $userRequestData->expiry_type = $request->expiry_type;
            $userRequestData->expiry_data_count = $request->expiry_data_count;
            $userRequestData->expiry_data_type = $request->expiry_data_type;
            $userRequestData->automatic_reminders = $request->automatic_reminders;
            $userRequestData->reminder_data_type = $request->reminder_data_type;
            $userRequestData->reminder_data_count = $request->reminder_data_count;
            $userRequestData->allow_decline = $decline_to_sign;
            $userRequestData->sender_name = $userName;

            $userRequestData->update();

            //create reminder dates
            if(isset($reminder_dates) && $reminder_dates != null){
                foreach($reminder_dates as $date){

                    $reminderdate_obj = new RequestReminderDate();
                    $reminderdate_obj->request_id = $userRequestData->id;
                    $reminderdate_obj->date = $date['reminder_date'];
                    $reminderdate_obj->save();

                }
            }
            
            //ending create reminder dates

            // Process recipient data and save to database
            $requestId = UserRequest::where('unique_id', $uniqueId)->first()->id;

            //create approver 
            if(isset($requestData['approvers']) && count($requestData['approvers'])>0){
                Approver::where('request_id',$requestId)->delete();
            }
            if(isset($requestData['approvers']) && $requestData['approvers'] != null){
           
                foreach($approvers as $approver){

                    $recipientId = $approver['recipientId'];

                    $userId = Contact::where('unique_id', $recipientId)->first();
                    $contactmaildata = User::find($userId->contact_user_id);
                    $usermail = $contactmaildata->email;

                    
                    $approverStatus = $approver['approver_status'];
                    $approverUniqueId = $approver['approver_unique_id'];

                    $approver_obj = new Approver();
                    $approver_obj->request_id = $requestId;
                    $approver_obj->recipient_unique_id = $recipientId; // Assuming this is recipientId
                    $approver_obj->recipient_user_id = $userId->contact_user_id;
                    $approver_obj->recipient_contact_id = $userId->id;
                    $approver_obj->status = $approverStatus;
                    $approver_obj->unique_id = $approverUniqueId;
                    $approver_obj->save();
                    
                    if($request->status != "draft"){
                    $this->sendMailApprover($approverUniqueId,$requestId,$usermail,$type=2);
                    }
                }

            }
            //ending create approver

    
            // Now you can iterate over recipients and process each one
            if(isset($requestData['recipients']) && count($recipients)>0 && $request->status == "draft"){
                Signer::where('request_id',$requestId)->delete();
            }
            foreach ($recipients as $recipient) {
                // Access recipient data
                $recipientId = $recipient['recipientId'];
                $signerStatus = $recipient['signer_status'];
                $signerUniqueId = $recipient['signer_unique_id'];
                $color = $recipient['color'];
                $fields = $recipient['fields'];
                
                $userId = Contact::where('unique_id', $recipientId)->first();
                $contactmaildata = User::find($userId->contact_user_id);
                $usermail = $contactmaildata->email;
                
                $signer = Signer::where('recipient_unique_id',$recipientId)->where('request_id',$requestId)->first();
                if(!$signer){
                    $signer = new Signer();
                    $signer->unique_id = $signerUniqueId;
                }else{
                    $signerUniqueId = $signer->unique_id;
                }
                $signer->request_id = $requestId;
                $signer->recipient_unique_id = $recipientId; // Assuming this is recipientId
                // You may need to adjust the following based on your database structure
                // Assuming you have a mapping between recipientId and user_id and contact_id
                $userId = Contact::where('unique_id', $recipientId)->first();
                $signer->recipient_user_id = $userId->contact_user_id;
                $signer->recipient_contact_id = $userId->id;
                $signer->status = $signerStatus;
                
                $signer->color = $color;
                $signer->save();

                if(isset($approvers)){

                }else{
                    if($request->status != "draft"){
                        $this->sendMail($signerUniqueId,$requestId,$usermail,$type=1);
                    }
                    
                }
    
                // Assuming you need to iterate over fields for each recipient
                if(isset($recipient['fields']) && count($fields)>0){
                    RequestField::where('request_id',$requestId)
                    ->where('recipientId',$signer->id)
                    ->delete();
                }
                foreach ($fields as $field) {
                    $requestField = new RequestField();
                    $requestField->request_id = $requestId;
                    $requestField->type = $field['type'];
                    if($field['type'] != "radio"){
                        $requestField->x = $field['x'];
                        $requestField->y = $field['y'];
                        $requestField->height = $field['height'];
                        $requestField->width = $field['width'];
                
                        $requestField->question = $field['question'] ?? null;
                    }

                    $requestField->is_required = $field['is_required'] == 'true' ? 1 : 0;
                    $requestField->page_index = $field['page_index'];
                    // Assuming recipientId here refers to signer's id, you may need to adjust this
                    $requestField->recipientId = $signer->id;
                    $requestField->save();

                    if($field['type'] == "radio"){

                        foreach($field['radioOptions'] as $optionquestion){
                            $radio =  new RadioButton();    
                            $radio->option_question = $optionquestion['option_question'];
                            $radio->x = $optionquestion['option_x'];
                            $radio->y = $optionquestion['option_y'];
                            $radio->field_id = $requestField->id;
                            $radio->save();
                        }
                        

                    }
                }
            }

             
            //adding activity log 
            if($request->status != "draft"){
            $userName = getUserName($request);    
            $this->addRequestLog("sent_request", "Signature request sent", $userName, $userRequestData->id);
            }
            //ending adding activity log
    
            // Return response
            return response()->json([
                'message' => 'Request processed successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to process request. ' . $e->getMessage() . ' at line '.$e->getLine() 
            ], 500);
        }
    }
    

    public function fetchRequest(Request $request){

        $data = UserRequest::with(['signers','signers.requestFields','signers.requestFields.radioFields','signers.signerContactDetail'])
            ->where('unique_id',$request->request_unique_id)
            ->first();
        
        if(!$data){
            return response()->json([
                'message' => 'No data available.',
                'error_code' => 'no_data'
            ], 200);
        }

        $sender = User::find($data->user_id);

        if($data->is_trash == 1){

            return response()->json([
                'message' => 'Request has been trashed.',
                'error_code' => 'request_trashed',
                'file_name' => $data->file_name,
                'sender'=> $sender
            ], 200);

        }

        if($data->status == 'cancelled'){

            return response()->json([
                'message' => 'Signature request cancelled',
                'data' => $sender,
                'file_name' => $data->file_name,
                'error_code' => 'request_cancelled'
            ], 200);

        }

        if($data->approve_status == 0){
            return response()->json([
                'message' => 'Approve pending.',
                'file_name' => $data->file_name,
            ], 200);
        }elseif($data->approve_status == 2){

            return response()->json([
                'message' => 'File rejected.',
                'file_name' => $data->file_name,
            ], 200);

        }

        $signer = Signer::where('unique_id',$request->recipient_unique_id)
            ->where('request_id',$data->id)
            ->first();

        $contact = Contact::where('id',$signer->recipient_contact_id)->first();

        $full_name = $contact->contact_first_name.' '.$contact->contact_last_name;

        //adding activity log 
        $this->addRequestLog("consulted_request", "Consulted document", $full_name, $data->id);
        //ending adding activity log

        if($data->email_otp == 1){

            if($signer->otp_verified == 0){
                
                return response()->json([
                    'message' => 'Email OTP.'
                ], 200);

            }
        }

        if($data->sms_otp == 1){

            if($signer->otp_verified == 0){
                
                return response()->json([
                    'message' => 'SMS OTP.'
                ], 200);

            }

        }

        if (Carbon::parse($data->expiry_date)->isPast()) {
            return response()->json([
                'message' => 'This link has been expired.',
                'file_name' => $data->file_name,
                'sender_name' => $sender->name.' '.$sender->last_name,
                'error_code' => 'link_expired',
            ], 200);
        } 

        //check signer status
        $signercheck = Signer::where('request_id',$data->id)
        ->where('unique_id',$request->recipient_unique_id)
        ->first();

        if($signercheck && $signercheck->status == 'signed'){
            return response()->json([
                'pdf_file' => '', // Convert file content to base64
                'message' => 'Already signed',
                'file_name' => $data->file_name,
                'signer' => $signer
            ], 200);
        }
        //ending check signer status
    
        // Retrieve the file path
        $filePath = public_path($data->file);
    
        // Check if the file exists
        if (!File::exists($filePath)) {
            return response()->json([
                'message' => 'File not found.',
                'error_code' => 'no_data',
            ], 200);
        }
    
        // Read the file content
        $fileContent = File::get($filePath);

       if($data->signed_file != null){
            $signedFilePath = public_path($data->signed_file);

            if (!File::exists($signedFilePath)) {
                $signedfileContent = null;
            }else{
                $signedfileContent = File::get($signedFilePath);
            }
        }else{
            $signedfileContent = null;
        }


    
        // Generate response with file content and other data
        return response()->json([
            'data' => $data,
            'pdf_file' => base64_encode($fileContent), // Convert file content to base64
            'signed_file' => base64_encode($signedfileContent),
            'message' => 'Success'
        ], 200);
    
    }

    public function show($id){

        $data = UserRequest::with(['reminders','logs','signers','signers.requestFields','signers.requestFields.radioFields','signers.signerContactDetail','approvers','approvers.approverContactDetail', 'approvers.approverContactDetail.contactUserDetail','signers.signerContactDetail.contactUserDetail'])
            ->where('unique_id',$id)
            ->first();
        
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }
    
        // Retrieve the file path
        $filePath = public_path($data->file);
    
        // Check if the file exists
        if (!File::exists($filePath)) {
            return response()->json([
                'message' => 'File not found.'
            ], 404);
        }
    
        // Read the file content
        $fileContent = File::get($filePath);
        
        if($data->signed_file != null){
            $signedFilePath = public_path($data->signed_file);

            if (!File::exists($signedFilePath)) {
                $signedfileContent = null;
            }else{
                $signedfileContent = File::get($signedFilePath);
            }
        }else{
            $signedfileContent = null;
        }

        
    
        // Generate response with file content and other data
        return response()->json([
            'data' => $data,
            'pdf_file' => base64_encode($fileContent), // Convert file content to base64
            'signed_file' => base64_encode($signedfileContent),
            'message' => 'Success'
        ], 200);
    
    }

    public function approverFetchRequest(Request $request){

        $data = UserRequest::with(['signers','signers.requestFields','signers.requestFields.radioFields','signers.signerContactDetail','approvers','approvers.approverContactDetail', 'approvers.approverContactDetail.contactUserDetail','signers.signerContactDetail.contactUserDetail'])
            ->where('unique_id',$request->request_unique_id)
            ->first();
        
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        if($data->is_trash == 1){

            return response()->json([
                'message' => 'Request has been trashed.'
            ], 200);

        }

        $sender = User::find($data->user_id);

        if($data->status == 'cancelled'){

            return response()->json([
                'message' => 'Signature request cancelled',
                'file_name' => $data->file_name,
                'sender'=> $sender
            ], 200);

        }

        if (Carbon::parse($data->expiry_date)->isPast()) {
            return response()->json([
                'message' => 'This link has been expired.'
            ], 401);
        } 

        if ($data->approve_status == 1 || $data->approve_status == 2) {
            return response()->json([
                'message' => 'Already answered.',
                'file_name' => $data->file_name,
                'sender'=> $sender,
            ], 200);
        } 

        //check signer status
        $approvercheck = Approver::where('request_id',$data->id)
        ->where('unique_id',$request->recipient_unique_id)
        ->first();

        if($approvercheck && $approvercheck->status == 'approved'){
            return response()->json([
                'pdf_file' => '', // Convert file content to base64
                'message' => 'Already approved',
                'file_name' => $data->file_name,
                'sender'=> $sender,
                'approver'=> $approvercheck
            ], 200);
        }
        //ending check signer status


        $contact = Contact::where('id',$approvercheck->recipient_contact_id)->first();

        $full_name = $contact->contact_first_name.' '.$contact->contact_last_name;

        //adding activity log 
        $this->addRequestLog("consulted_request", "Consulted document", $full_name, $data->id);
        //ending adding activity log
    
        // Retrieve the file path
        $filePath = public_path($data->file);

        if($data->signed_file != null){
            $signedFilePath = public_path($data->signed_file);

            if (!File::exists($signedFilePath)) {
                $signedfileContent = null;
            }else{
                $signedfileContent = File::get($signedFilePath);
            }
        }else{
            $signedfileContent = null;
        }
    
        // Check if the file exists
        if (!File::exists($filePath)) {
            return response()->json([
                'message' => 'File not found.'
            ], 404);
        }

        
    
        // Read the file content
        $fileContent = File::get($filePath);
    
        // Generate response with file content and other data
        return response()->json([
            'data' => $data,
            'pdf_file' => base64_encode($fileContent), // Convert file content to base64
            'signed_file' => base64_encode($signedfileContent),
            'message' => 'Success'
        ], 200);
    
    }

    public function sendOTP(Request $request){

        RequestOtp::where('request_unique_id',$request->request_unique_id)
        ->where('recipient_unique_id',$request->recipient_unique_id)
        ->delete();

        $data = new RequestOtp();
        $data->recipient_unique_id = $request->recipient_unique_id;
        $otp = rand(100000, 999999);
        $data->otp = $otp;
        $data->type = $request->type;
        $data->request_unique_id = $request->request_unique_id;
        $data->save();

        $requestdata = UserRequest::where('unique_id',$request->request_unique_id)
            ->first();
        
        $signer = Signer::where('unique_id',$request->recipient_unique_id)
            ->where('request_id',$requestdata->id)
            ->first();

        $getContact = Contact::where('id',$signer->recipient_contact_id)->first();
        $getUser = User::find($getContact->contact_user_id);

        $email = $getUser->email;

        if($request->type == 'email'){

        $dataUser = [
                'receiver_name'=>$getUser->name.' '.$getUser->last_name,
                'email'=>$email,
                'otp'=>$otp
           ];

        $subject = $getContact->contact_first_name." Your One-Time Password";

        \Mail::to($email)->send(new \App\Mail\OTPEmail($dataUser, $subject));



        }elseif($request->type == 'sms'){

            $phone = $getContact->contact_phone;

            if($phone!= null && $phone != ""){
                $this->sendSMSOTP($phone, $otp);
            }

           
        }

        return response()->json([
            'message' => 'OTP Sent'
        ], 200);

    }

    public function verifyOTP(Request $request){

        $data = RequestOtp::where('otp',$request->otp)
        ->where('recipient_unique_id',$request->recipient_unique_id)
        ->orderBy('id','desc')
        ->first();

        if(!$data){
            return response()->json([
                'message' => 'Error: Wrong OTP.'
            ], 401);
        }

        $data->delete();

        $requestdata = UserRequest::where('unique_id',$request->request_unique_id)
            ->first();

        $signer = Signer::where('unique_id',$request->recipient_unique_id)
            ->where('request_id',$requestdata->id)
            ->first();

        $signer->otp_verified = 1;
        $signer->update();
        
        
        $uRequestdata = UserRequest::with(['signers','signers.requestFields','signers.signerContactDetail'])
            ->where('unique_id',$request->request_unique_id)
            ->first();
            
            // Retrieve the file path
        $filePath = public_path($uRequestdata->file);
    
        // Check if the file exists
        if (!File::exists($filePath)) {
            return response()->json([
                'message' => 'File not found.'
            ], 404);
        }
    
        // Read the file content
        $fileContent = File::get($filePath);

        if($data->signed_file != null){
            $signedFilePath = public_path($data->signed_file);

            if (!File::exists($signedFilePath)) {
                $signedfileContent = null;
            }else{
                $signedfileContent = File::get($signedFilePath);
            }
        }else{
            $signedfileContent = null;
        }
    
        // Generate response with file content and other data
        return response()->json([
            'data' => $uRequestdata,
            'pdf_file' => base64_encode($fileContent), // Convert file content to base64
            'signed_file' => base64_encode($signedfileContent),
            'message' => 'OTP Matched'
        ], 200);

    /*
        return response()->json([
            'message' => 'OTP Matched'
        ], 200); */
        
    }

    public function answerRequest(Request $request){

        $requestdata = UserRequest::where('unique_id',$request->request_unique_id)->first();

        if($requestdata->status == 'cancelled'){

            return response()->json([
                'message' => 'Signature request cancelled'
            ], 200);

        }

        /*
        
        if($requestdata) {
        $filePath = $this->storeFile($request->file('signed_file'), 'files');
        $requestdata->signed_file = $filePath;
        //$requestdata->status =  'Done';
        $requestdata->update();
        } */
        
        //storing fields answers
        
        for($i=0; $i<count($request->field_id); $i++){
            $data = RequestField::find($request->field_id[$i]);
            if($data){
                $data->answer = $request->answer[$i];
                $data->update();
            }  
            
        } 
        //ending storing fields answers

        $today_date = Carbon::now();
        $formatted_date = $today_date->format('Y-m-d H:i:s');

        Signer::where('request_id',$requestdata->id)
        ->where('unique_id',$request->recipient_unique_id)
        ->update(['status'=>'signed','signed_date'=>$formatted_date]);

        $signeruser = Signer::where('request_id',$requestdata->id)
        ->where('unique_id',$request->recipient_unique_id)->first();
        $signeruser_data = User::find($signeruser->recipient_user_id);
        $signeruser_contact = Contact::find($signeruser->recipient_contact_id);


        //manage signed file 

        $this->addSignerPDF($requestdata->id,$signeruser->id);

        //ending manage signed file

        //sign registered

        $senderUser = User::find($requestdata->user_id);
        $useremail = $senderUser->email;
        $subject = 'Signature registered on '.$requestdata->file_name;

        $dataUser = [
            'email' => $signeruser_data->email,
            'sender_name' => $signeruser_contact->name.' '.$senderUser->last_name,
            'file_name' => $requestdata->file_name,
            'file' => $requestdata->file,
            'requestUID' => $requestdata->unique_id,
            'signed_file' => $requestdata->signed_file,
            'signer_name' => $signeruser_contact->contact_first_name.' '.$signeruser_contact->contact_last_name,
            
        ];

        Mail::to($signeruser_data->email)->send(new \App\Mail\SignRegister($dataUser, $subject));

        //sign registered ending

        

        //update status for request
        $signercheck = Signer::where('request_id',$requestdata->id)
        ->where('status','pending')
        ->first();

        

        if(!$signercheck){

            $requestdata->signed_at = Carbon::now();
            $requestdata->update();

            UserRequest::where('unique_id',$request->request_unique_id)->update(['status'=>'done']);

            //fully signed email

            $senderUser = User::find($requestdata->user_id);
            $useremail = $senderUser->email;
            $subject = $requestdata->file_name.' is Fully Signed';

            $dataUser = [
                'email' => $senderUser->email,
                'sender_name' => $senderUser->name.' '.$senderUser->last_name,
                'file_name' => $requestdata->file_name,
                'requestUID' => $requestdata->unique_id,
                
            ];
   
            Mail::to($useremail)->send(new \App\Mail\FullySigned($dataUser, $subject));

            //ending fully signed email
        }else{

            //inform other signers

            $pendingSigners = Signer::where('request_id', $requestdata->id)
                ->where('status', 'pending')
                ->get();

            $totalSigners = Signer::where('request_id', $requestdata->id)
                ->count('id');

            // Calculate the percentage of signed documents
            $signedCount = $totalSigners - $pendingSigners->count();
            $percentageSigned = $totalSigners > 0 ? ($signedCount / $totalSigners) * 100 : 0;

            // Format the percentage to 0 decimal places
            $percentageSigned = number_format($percentageSigned, 0);

            foreach($pendingSigners as $pending){

                $senderUser = User::find($pending->recipient_user_id);
                $useremail = $senderUser->email;
                $subject = $requestdata->file_name . ' is ' . $percentageSigned . '% Signed - Signature1618';

                $dataUser = [
                    'email' => $senderUser->email,
                    'sender_name' => $senderUser->name.' '.$senderUser->last_name,
                    'file_name' => $requestdata->file_name,
                    'requestUID' => $requestdata->unique_id,
                    'signatory_a' => $signeruser_contact->contact_first_name.' '.$signeruser_contact->contact_last_name,
                    'expiry_date' => $requestdata->expiry_date,
                    'signer_unique_id' => $pending->unique_id
                    
                ];

                Mail::to($useremail)->send(new \App\Mail\InformSigner($dataUser, $subject));

            }

            //ending inform other

        }

        //ending update status for request

        $signer = Signer::where('request_id',$requestdata->id)
        ->where('unique_id',$request->recipient_unique_id)
        ->first();

        $contact = Contact::where('id',$signer->recipient_contact_id)->first();

        $full_name = $contact->contact_first_name.' '.$contact->contact_last_name;

        //adding activity log 
        $this->addRequestLog("signed_request", "Signed document", $full_name, $requestdata->id);
        //ending adding activity log

        return response()->json([
           
            'message' => 'Request answered successfully.'
        ], 200);

    }

    public function approveRequest(Request $request){

        $requestdata = UserRequest::where('unique_id',$request->request_unique_id)->first();
        
        if($requestdata) {
        //$requestdata->status =  'Done';
        //$requestdata->update();
        }

        if($requestdata->status == 'cancelled'){

            return response()->json([
                'message' => 'Signature Request Cancelled'
            ], 200);

        }
        
        $senderuser = User::find($requestdata->user_id);

        $today_date = Carbon::now();
        $formatted_date = $today_date->format('Y-m-d H:i:s');

        $checkng = Approver::where('request_id',$requestdata->id)
        ->where('unique_id',$request->approver_unique_id)
        ->update(['status'=>'approved','approved_date'=>$formatted_date]);

        //notify sender
        $senderemail = $senderuser->email;
        $approver_data = Approver::where('request_id',$requestdata->id)
        ->where('unique_id',$request->approver_unique_id)->first();
        $approver_user = User::find($approver_data->recipient_user_id);
        $approver_name = $approver_user->name.' '.$approver_user->last_name;
        $subject = $requestdata->file_name." Is Approved by ".$approver_name;
        
         $dataUser = [
             'sender_name' => $senderuser->name.' '.$senderuser->last_name,
             'file_name' => $requestdata->file_name,
             'requestUID' => $requestdata->unique_id,
             'approver_name' =>$approver_name
         ];

         Mail::to($senderemail)->send(new \App\Mail\ApprovedMail($dataUser, $subject));

        //ending notify sender

        //notify himself 

      
        $dataUserToApprover = [
            'user_first_name' => $approver_user->name,
            'user_last_name' => $approver_user->contact_last_name,
            'organization_name' => $senderuser->company,
            'document_name' => $requestdata->file_name,
            'sender_name' => $senderuser->name.' '.$senderuser->last_name,
            'approver_name' => $approver_user->contact_first_name.' '.$approver_user->contact_last_name,
            'file_name' => $requestdata->file_name,
        ];
                    
        $subjectToApprover = 'You Approved '.$requestdata->file_name.' from '.$senderuser->company;
                    
        Mail::to($approver_user->email)->send(new \App\Mail\RequestApprovedToApprover($dataUserToApprover, $subjectToApprover));

        //ending notify himself


        //update status for request
        $approvercheck = Approver::where('request_id',$requestdata->id)
        ->where('status','pending')
        ->first();

        if(!$approvercheck){
            UserRequest::where('unique_id',$request->request_unique_id)->update(['approve_status'=>1]);

            //sending mail to signers 
            $signers = Signer::where('request_id',$requestdata->id)->get();
            foreach($signers as $signer){

                $signer_user = User::find($signer->recipient_user_id);

                //return $signer_user->email;

                $this->sendMail($signer->unique_id,$requestdata->id,$signer_user->email,$type=1);

            }
            
            //ending sending mail to signers

        }

        //ending update status for request

        

        $approver = Approver::where('request_id',$requestdata->id)
        ->where('unique_id',$request->approver_unique_id)
        ->first();

        $contact = Contact::where('id',$approver->recipient_contact_id)->first();

        $full_name = $contact->contact_first_name.' '.$contact->contact_last_name;

        //adding activity log 
        $this->addRequestLog("approved_request", "Approved document", $full_name, $requestdata->id);
        //ending adding activity log

        return response()->json([
           
            'message' => 'Request answered successfully.'
        ], 200);

    }

    public function rejectRequest(Request $request){

        $requestdata = UserRequest::where('unique_id',$request->request_unique_id)->first();
       

        Approver::where('request_id',$requestdata->id)
        ->where('unique_id',$request->approver_unique_id)
        ->update(['status'=>'rejected', 'comment'=>$request->comment]);


        $today_date = Carbon::now();
        $formatted_date = $today_date->format('Y-m-d H:i:s');
        Approver::where('request_id',$requestdata->id)
        ->update(['status'=>'rejected','rejected_date'=>$formatted_date]);


        //update status for request
        $approvercheck = Approver::where('request_id',$requestdata->id)
        ->where('status','pending')
        ->first();

        UserRequest::where('unique_id',$request->request_unique_id)->update(['approve_status'=>2,'status'=>'rejected']);

        /*if(!$approvercheck){
            UserRequest::where('unique_id',$request->request_unique_id)->update(['approve_status'=>2]);
        } */

        //ending update status for request

        //sending mail to signers 
        $sender = User::where('id',$requestdata->user_id)->first();
        //$this->sendMail($sender->unique_id,$requestdata->id,$sender->email,$type=3);

        $signers = Signer::where('request_id',$requestdata->id)->get();
        /*
        foreach($signers as $signer){

            $signer_user = User::find($signer->recipient_user_id);

            //return $signer_user->email;

            $this->sendMail($signer->unique_id,$requestdata->id,$signer_user->email,$type=3);

        } */
        
    
       
        //ending sending mail to signers

        $approver = Approver::where('request_id',$requestdata->id)
        ->where('unique_id',$request->approver_unique_id)
        ->first();

        $contact = Contact::where('id',$approver->recipient_contact_id)->first();

        $full_name = $contact->contact_first_name.' '.$contact->contact_last_name;

        //adding activity log 
        $this->addRequestLog("rejected_request", "Rejected document", $full_name, $requestdata->id);
        //ending adding activity log


        //get company name 
        $globalsettings = UserGlobalSetting::where('user_id',$requestdata->user_id)->where('meta_key','company')->first();
        if(!$globalsettings){
            $company_name = $sender->name.' '.$sender->last_name;
        }else{
            $company_name = $globalsettings->meta_value;
        }
        //end get company name

        //send mail to approvers

        $approver_contact = Contact::where('id',$approver->recipient_contact_id)->first();
        $approver_user = User::where('id',$approver->recipient_user_id)->first();
        $dataUserToApprover = [
            'user_first_name' => $approver_contact->contact_first_name,
            'user_last_name' => $approver_contact->contact_last_name,
            'organization_name' => $company_name,
            'document_name' => $requestdata->file_name,
            'sender_name' => $sender->name.' '.$sender->last_name,
            'approver_name' => $approver_contact->contact_first_name.' '.$approver_contact->contact_last_name,
            'file_name' => $requestdata->file_name,
        ];
                    
        $subjectToApprover = 'You Rejected '.$requestdata->file_name.' from '.$company_name;
                    
        Mail::to($approver_user->email)->send(new \App\Mail\RequestRejectedToApprover($dataUserToApprover, $subjectToApprover));
                
        //ending send mail

        //send mail to sender

        $dataUserToSender = [
            'sender_first_name' => $sender->name,
            'sender_last_name' => $sender->last_name,
            'approver_first_name' => $approver_contact->contact_first_name,
            'approver_last_name' => $approver_contact->contact_last_name,
            'organization_name' => $company_name,
            'document_name' => $requestdata->file_name,
            'requestUID'=>$requestdata->unique_id,
            'sender_name' => $sender->name.' '.$sender->last_name,
            'approver_name' => $approver_contact->contact_first_name.' '.$approver_contact->contact_last_name,
            'file_name' => $requestdata->file_name
        ];
                    
        $subjectToSender = $requestdata->file_name.' Was Rejected by '.$approver_contact->contact_first_name.' '.$approver_contact->contact_last_name;
                    
        Mail::to($sender->email)->send(new \App\Mail\RequestRejectedToSender($dataUserToSender, $subjectToSender));
                
        //ending send mail to sender


        return response()->json([
            'message' => 'Request answered successfully.'
        ], 200);

    }

    private function storeFile($file, $directory){
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path($directory), $fileName);
        return "$directory/$fileName";
    }

    private function sendMail($signerUId, $requestId, $email, $type) {

        $request = request();
        $userName = getUserName($request);

        $userRequest = UserRequest::where('id', $requestId)->first();
        $requestUid = $userRequest->unique_id;

        //get company name 
        $admin_user = User::find($userRequest->user_id);
        $globalsettings = UserGlobalSetting::where('user_id',$admin_user->user_id)->where('meta_key','company')->first();
        if(!$globalsettings){
            $company_name = $admin_user->name.' '.$admin_user->last_name;
        }else{
            $company_name = $globalsettings->meta_value;
        }
        //end get company name
        $date = $userRequest->expiry_date;
        //$formattedDate = $date->format('m/d/Y');

        $signerdata = Signer::where('unique_id',$signerUId)->where('request_id',$requestId)->first();
        $signercontact = User::find($signerdata->recipient_user_id);
        
        /*
        $approverdata = Approver::where('unique_id',$signerUId)->where('request_id',$requestId)->first();
        $approvercontact = User::find($approverdata->recipient_user_id); */
    
        $dataUser = [
            'email' => $email,
            'receiver_name' => $signercontact->name.' '.$signercontact->last_name,
            'signerUID' => $signerUId,
            'requestUID' => $requestUid,
            'company_name' => $company_name,
            'file_name' => $userRequest->file_name,
            'sender_first_name' => $admin_user->name,
            'sender_last_name' => $admin_user->last_name,
            'sender_name' => $admin_user->name.' '.$admin_user->last_name,
            'expiry_date' => $date,
            'approver_name'=>$userName,
        ];
    
        $subject = '';
        switch ($type) {
            case 1:
                $subject = 'Signature Request for '.$userRequest->file_name.' from '.$company_name;
                break;
            case 2:
                $subject = 'Approver Mail';
                break;
            case 3:
                $subject = 'Rejected Mail';
                break;
            default:
                // Handle default case
                break;
        }
        
         $now_datetime = \App\Helpers\Common::dateFormat(Carbon::now()->toDateTimeString());
    
        // Append current timestamp to subject
        //$subject .= ' - ' . $now_datetime;
    
        // Send email
        if ($type == 1) {
            Mail::to($email)->send(new \App\Mail\NewRequest($dataUser, $subject));
        } elseif ($type == 2) {
            Mail::to($email)->send(new \App\Mail\ApproverMail($dataUser, $subject));
        } elseif ($type == 3) {
            Mail::to($email)->send(new \App\Mail\RejectedMail($dataUser, $subject));
        }
    
        return "Mail sent";
    }

    private function sendMailApprover($signerUId, $requestId, $email, $type) {
        $request = request();
        $userName = getUserName($request);

        $userRequest = UserRequest::where('id', $requestId)->first();
        $requestUid = $userRequest->unique_id;

        //get company name 
        $admin_user = User::find($userRequest->user_id);
        $globalsettings = UserGlobalSetting::where('user_id',$admin_user->user_id)->where('meta_key','company')->first();
        if(!$globalsettings){
            $company_name = $admin_user->name.' '.$admin_user->last_name;
        }else{
            $company_name = $globalsettings->meta_value;
        }
        //end get company name
        $date = $userRequest->expiry_date;
        //$formattedDate = $date->format('m/d/Y');

        $signerdata = Approver::where('unique_id',$signerUId)->where('request_id',$requestId)->first();
        $signercontact = User::find($signerdata->recipient_user_id);
    
        $dataUser = [
            'email' => $email,
            'receiver_name' => $signercontact->name.' '.$signercontact->last_name,
            'signerUID' => $signerUId,
            'requestUID' => $requestUid,
            'company_name' => $company_name,
            'file_name' => $userRequest->file_name,
            'sender_first_name' => $admin_user->name,
            'sender_last_name' => $admin_user->last_name,
            'expiry_date' => $date
        ];
    
        $subject = '';
        switch ($type) {
            case 1:
                $subject = 'Signature Request for '.$userRequest->file_name.' from '.$company_name.' via Signature1618';
                break;
            case 2:
                $subject = 'Approval Request for '.$userRequest->file_name.' from '.$company_name;
                break;
            case 3:
                $subject = 'Rejected Mail';
                break;
            default:
                // Handle default case
                break;
        }
        
        
        $now_datetime = \App\Helpers\Common::dateFormat(Carbon::now()->toDateTimeString());
        
        //\Log::info('date time of current hr '.$now_datetime);
    
        // Append current timestamp to subject
        $subject .= ' - ' . $now_datetime;
    
        // Send email
        if ($type == 1) {
            Mail::to($email)->send(new \App\Mail\NewRequest($dataUser, $subject));
        } elseif ($type == 2) {
            Mail::to($email)->send(new \App\Mail\ApproverMail($dataUser, $subject));
        } elseif ($type == 3) {
            Mail::to($email)->send(new \App\Mail\RejectedMail($dataUser, $subject));
        }
    
        return "Mail sent";
    }

    public function sendSMSOTP($phone, $otp)
    {
        $to = $phone;
        $message = 'Your OTP for Signature1618: '.$otp;
        //$senderName = 'Signature1618'; // Replace 'YourSenderName' with your desired sender name


        $this->twilio->sendSMS($to, $message);

        return true;

        //return response()->json(['message' => 'SMS sent successfully']);
    }

    public function destroy(Request $request,$id){

        $userId = getUserId($request);

        // Find the contact by ID and user ID
        $data = UserRequest::where('id', $id)->where('user_id', $userId)->first();

        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        //deleting signers
        Signer::where('request_id',$id)->delete();
        //deleting fields
        RequestField::where('request_id',$id)->delete();
        //deleing approvers
        Approver::where('request_id',$id)->delete();
        //otps
        RequestOtp::where('request_unique_id',$data->unique_id)->delete();
        //radio buttons

        $data->delete();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function addToTrash(Request $request){

        $data = UserRequest::where('unique_id',$request->request_unique_id)->first();
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }
        $data->is_trash = 1;
        $data->update();

        //adding activity log 
        $userName = getUserName($request);
        $this->addRequestLog("trashed_request", "Request moved to trash", $userName, $data->id);
        //ending adding activity log

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function removeFromTrash(Request $request){

        $data = UserRequest::where('unique_id',$request->request_unique_id)->first();
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }
        $data->is_trash = 0;
        $data->update();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function allTrashItems(){

        if(Auth::user()->user_role == 1){
            $data = UserRequest::with(['userDetail','signers','signers.requestFields','signers.signerContactDetail','approvers','approvers.approverContactDetail', 'approvers.approverContactDetail.contactUserDetail','signers.signerContactDetail.contactUserDetail'])
            ->where('is_trash',1)
            ->orderBy('id','desc')
            ->get();
        }else{
            $data = UserRequest::with(['userDetail','signers','signers.requestFields','signers.signerContactDetail','approvers','approvers.approverContactDetail', 'approvers.approverContactDetail.contactUserDetail','signers.signerContactDetail.contactUserDetail'])
            ->where('user_id',Auth::user()->id)
            ->where('is_trash',1)
            ->orderBy('id','desc')
            ->get();
        }
       
        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);

    }

    public function allBookmarkedItems(){

        if(Auth::user()->user_role == 1){
            $data = UserRequest::with(['userDetail','signers','signers.requestFields','signers.signerContactDetail','approvers','approvers.approverContactDetail', 'approvers.approverContactDetail.contactUserDetail','signers.signerContactDetail.contactUserDetail'])
            ->where('is_trash',0)
            ->where('is_bookmark',1)
            ->orderBy('id','desc')
            ->get();
        }else{
            $data = UserRequest::with(['userDetail','signers','signers.requestFields','signers.signerContactDetail','approvers','approvers.approverContactDetail', 'approvers.approverContactDetail.contactUserDetail','signers.signerContactDetail.contactUserDetail'])
            ->where('user_id',Auth::user()->id)
            ->where('is_trash',0)
            ->where('is_bookmark',1)
            ->orderBy('id','desc')
            ->get();
        }
       
        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);

    }

    public function addToBookmarks(Request $request){

        $data = UserRequest::where('unique_id',$request->request_unique_id)->first();
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }
        $data->is_bookmark = 1;
        $data->update();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function removeFromBookmarks(Request $request){

        $data = UserRequest::where('unique_id',$request->request_unique_id)->first();
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }
        $data->is_bookmark = 0;
        $data->update();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    
    public function sendReminder(Request  $request) {

        //check last reminder sent time
        $req_obj_reminder = UserRequest::where('unique_id', $request->request_unique_id)
            ->first();

        $sender_user = User::find($req_obj_reminder->user_id);

        if ($req_obj_reminder) {
            $last_reminder = RequestLog::where('request_id', $req_obj_reminder->id)
                ->where('type','reminder_request')
                ->orderBy('created_at', 'desc') // Ensure you are getting the most recent reminder
                ->first();

            if ($last_reminder) {
                // Get the current time
                $currentTime = Carbon::now();
                
                // Get the time of the last reminder
                $lastReminderTime = $last_reminder->created_at;
                
                // Calculate the difference in minutes
                $timeDifference = $currentTime->diffInMinutes($lastReminderTime);

                if ($timeDifference < 15) {
                    // Calculate how many minutes are left to send the next reminder
                    $minutesLeft = 15 - $timeDifference;

                    return response()->json([
                        'message' => 'You can send another reminder after ' . $minutesLeft . ' minutes.',
                        'error_code' => 'reminder_too_soon'
                    ], 200);
                }
            }

            // Proceed with sending the reminder since it's been more than 15 minutes
            // Your reminder logic here

        } else {
            return response()->json([
                'message' => 'No data available.',
                'error_code' => 'no_data'
            ], 200);
        }
        
        //ending check last reminder sent time

        $globalsettings = UserGlobalSetting::where('user_id',$sender_user->id)->where('meta_key','company')->first();
        if(!$globalsettings){
            $company_name = $sender_user->name.' '.$sender_user->last_name;
        }else{
            $company_name = $globalsettings->meta_value;
        }

        //reminder 
        $subject = "Reminder: Do Sign ".$req_obj_reminder->file_name.' from '.$company_name;
        
        $request_obj_approver = UserRequest::where('unique_id',$request->request_unique_id)
            ->where('approve_status',0)
            ->where('status','in progress')
            ->first();

            if($request_obj_approver){

                $subject = "Reminder: Do Approve ".$request_obj_approver->file_name.' from '.$company_name;

                $approver_obj = Approver::where('request_id',$request_obj_approver->id)
                ->where('status','pending')
                ->get();

                foreach($approver_obj as $approver){

                    $user_obj = User::find($approver->recipient_user_id);

                    $email = $user_obj->email;

                    $dataUser = [
                        'expiry_date'=>$request_obj_approver->expiry_date,
                        'file_name'=>$request_obj_approver->file_name,
                        'company_name'=> $sender_user->company,
                        'receiver_name'=> $user_obj->name.''.$user_obj->last_name,
                        'email' => $email,
                        'requestUID'=>$request_obj_approver->unique_id,
                        'signerUID'=>$approver->unique_id,
                        'custom_message'=>$request_obj_approver->custom_message,
                    ];

                    \Mail::to($email)->send(new \App\Mail\ReminderEmailApprover($dataUser, $subject));

                    $contact = Contact::where('id',$approver->recipient_contact_id)->first();

                    $full_name = $contact->contact_first_name.' '.$contact->contact_last_name;

                    $userName = getUserName($request);

                    //adding activity log 
                    $this->addRequestLog("reminder_request", "Reminder sent to ".$full_name, $userName,  $request_obj_approver->id);
                    //ending adding activity log


                }

                
                return response()->json([
                    'message' => 'Success'
                ], 200);

            }

        $request_obj = UserRequest::where('unique_id',$request->request_unique_id)
        ->where('approve_status',1)
        ->where('status','in progress')
        ->first();
        
        if($request_obj){

            $signer_obj = Signer::where('request_id',$request_obj->id)
            ->where('status','pending')
            ->get();

            foreach($signer_obj as $signer){

                $user_obj = User::find($signer->recipient_user_id);

                $email = $user_obj->email;

                $dataUser = [
                    'expiry_date'=>$request_obj->expiry_date,
                    'file_name'=>$request_obj->file_name,
                    'company_name'=> $sender_user->company,
                    'receiver_name'=> $user_obj->name.''.$user_obj->last_name,
                    'email' => $email,
                    'requestUID'=>$request_obj->unique_id,
                    'signerUID'=>$signer->unique_id,
                    'custom_message'=>$request_obj->custom_message,
                ];

                \Mail::to($email)->send(new \App\Mail\ReminderEmail($dataUser, $subject));


                $contact = Contact::where('id',$signer->recipient_contact_id)->first();

                $full_name = $contact->contact_first_name.' '.$contact->contact_last_name;

                $userName = getUserName($request);

                //adding activity log 
                $this->addRequestLog("reminder_request", "Reminder sent to ".$full_name, $userName, $request_obj->id);
                //ending adding activity log

            }

        }
        

        //ending reminder

        return response()->json([
            'message' => 'Success'
        ], 200);

    }


    public function changeRequestStatus(Request $request){

        $userName = getUserName($request);

        $data = UserRequest::where('unique_id',$request->request_unique_id)->first();

        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        $user = User::find($data->user_id);

        $data->status = $request->request_status;
        $data->update();

        //get company name 
        $globalsettings = UserGlobalSetting::where('user_id',$data->user_id)->where('meta_key','company')->first();
        if(!$globalsettings){
            $company_name = $user->name.' '.$user->last_name;
        }else{
            $company_name = $globalsettings->meta_value;
        }
        //end get company name

        if($request->request_status == "cancelled"){

            //send mail to approvers
            $allapprovers = Approver::where('request_id',$data->id)->get();

            foreach($allapprovers as $approver){
                $approver_contact = Contact::where('id',$approver->recipient_contact_id)->first();
                $approver_user = User::where('id',$approver->recipient_user_id)->first();
                $dataUserToApprover = [
                    'user_first_name' => $approver_contact->contact_first_name,
                    'user_last_name' => $approver_contact->contact_last_name,
                    'organization_name' => $company_name,
                    'document_name' => $data->file_name
                ];
            
                $subjectToApprover = $data->file_name.' Has Been Cancelled by '.$company_name;
            
                Mail::to($approver_user->email)->send(new \App\Mail\RequestCancelledBySenderToApprover($dataUserToApprover, $subjectToApprover));
            }

            //mail to signers
            if(count($allapprovers) == 0 || $data->status == 'approved'){
                $allSigners = Signer::where('request_id',$data->id)->get();

                foreach($allSigners as $signer){
                    $signer_contact = Contact::where('id',$signer->recipient_contact_id)->first();
                    $signer_user = User::where('id',$signer->recipient_user_id)->first();
                    $dataUserToSigner = [
                        'user_first_name' => $signer_contact->contact_first_name,
                        'user_last_name' => $signer_contact->contact_last_name,
                        'organization_name' => $company_name,
                        'document_name' => $data->file_name
                    ];
                
                    $subjectToSigner = $data->file_name.' Has Been Cancelled by '.$company_name;
                
                    Mail::to($$signer_user->email)->send(new \App\Mail\RequestCancelledBySenderToSigner($dataUserToApprover, $subjectToApprover));
                }
            }


            //ending mail to signer
           
            //ending send mail

            //adding activity log 
            $this->addRequestLog("cancelled_request", "Signature request cancelled", $userName, $data->id);
            //ending adding activity log

        }elseif($request->request_status == "declined"){
        /*
            if(Auth::check()){
                $signeruser = Auth::user();
            }else{

                $getsigner = Signer::where('unique_id',$request->signer_unique_id)->first();
                $signeruser = User::find($getsigner->recipient_user_id);

            } */
            
            $getsigner = Signer::where('unique_id',$request->signer_unique_id)->first();
            
            
          
            $signeruser = User::find($getsigner->recipient_user_id);
            
            
         
            
            $request_data = UserRequest::where('unique_id',$request->request_unique_id)->first();
            
             

            $signer = Signer::where('recipient_user_id',$signeruser->id)->where('request_id',$request_data->id)->first();
            
            
            
            $signer->status = "declined";
            $signer->update();

            //adding activity log 
            $this->addRequestLog("declined_request", "Request has been declined", $userName, $request_data->id);
            //ending adding activity log

            //send mail
            $dataUser = [
                'email' => $user->email,
                'sender_name' => $user->name.' '.$user->last_name,
                'requestUID' => $request_data->unique_id,
                'receiver_name' => $signeruser->name.' '.$signeruser->last_name,
                'signerUID' => $signer->unique_id,
                'organization_name' => $company_name,
                'file_name' => $request_data ->file_name
            ];
        
            $subject = 'Request to Sign Declined by '.$signeruser->name.' '.$signeruser->last_name;
        
            Mail::to($user->email)->send(new \App\Mail\DeclineSignSender($dataUser, $subject));
            //ending send mail

            //send mail to signer
            $dataUserSigner = [
                'email' => $signeruser->email,
                'first_name' => $signeruser->name,
                'last_name' => $signeruser->last_name,
                'requestUID' => $request_data->unique_id,
                'organization_name' => $company_name,
                'signerUID' => $signer->unique_id,
                'file_name' => $request_data->file_name
            ];
        
            $subjectSigner = 'Request to Sign Declined';
        
            Mail::to($signeruser->email)->send(new \App\Mail\DeclineSignSigner($dataUserSigner, $subjectSigner));
            //ending send mail to signer

            //send mail to OTHER signer
            $otherSigners = Signer::where('request_id',$request_data->id)->whereNot('recipient_user_id',$signeruser->id)->get();
            

            foreach($otherSigners as $otherSigner){
                
                $otheruser = User::find($otherSigners->recipient_user_id);

                $userName = getUserName($request);
                
                $dataUserOtherSigner = [
                    'email' => $otheruser->email,
                    'user_first_name' => $otheruser->name,
                    'user_last_name' => $otheruser->last_name,
                    'declined_by_first_name' => $userName,
                    'declined_by_last_name' => '',
                    'requestUID' => $request_data->unique_id,
                    'organization_name' => $company_name,
                    'signerUID' => $signer->unique_id,
                    'document_name' => $request_data->file_name
                ];
            
                $subjectOtherSigner = 'Request to Sign Declined by '.$userName;
            
                Mail::to($otheruser->email)->send(new \App\Mail\DeclineSignOther($dataUserOtherSigner, $subjectOtherSigner));

            }
            
            //ending send mail to OTHER signer

        }

        return response()->json([
            'message' => 'Success'
        ], 200);

    }

    private function addRequestLog($type=null, $message=null, $user_name=null, $request_id=null) {

        $data = new RequestLog();
        $data->request_id = $request_id;
        $data->type = $type;
        $data->message = $message;
        $data->user_name = $user_name;
        $data->save();

        return true;

    }
    

   public function addSignerPDF($request_id = null, $signer_id = null)
    {
        // Fetching data as per your original code
    $data = UserRequest::with(['userDetail','signers','signers.requestFields','signers.signerContactDetail','approvers','approvers.approverContactDetail', 'approvers.approverContactDetail.contactUserDetail','signers.signerContactDetail.contactUserDetail'])
        ->where('is_trash', 0)
        ->where('id', $request_id)
        ->orderBy('id', 'desc')
        ->first();

    // Original PDF file path
    $pdfPath = public_path($data->file);

    // Check if the file exists
    if (!file_exists($pdfPath)) {
        return response()->json([
            'message' => 'PDF file not found',
        ], 404);
    }

    // Signed PDF file path
    $signedFileName = 'signed_' . Str::afterLast($data->file, '/');  // Example: 'signed_1726043140_sample.pdf'
    $signedFilePath = public_path('files/' . $signedFileName);

    // Initialize FPDI and check if a signed file already exists
    $pdf = new Fpdi();

    if (file_exists($signedFilePath)) {
        // If signed file already exists, load that file
        $pdfPath = $signedFilePath;
    } else {
        // If not, load the original PDF file
        $pdfPath = public_path($data->file);
    }

    // Set the source file
    $pageCount = $pdf->setSourceFile($pdfPath);

    // Array to track processed signer IDs
    $processedSigners = [];

    // Loop through all pages and add the annotation
    for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
        // Import each page
        $templateId = $pdf->importPage($pageNumber);

        // Get the page size of the imported page
        $size = $pdf->getTemplateSize($templateId);

        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($templateId);

        // Set the new width for the annotation
        $newWidth = 600; // New static width for annotation

        // Calculate the new height to maintain the aspect ratio
        $originalWidth = $size['width'];
        $originalHeight = $size['height'];
        $newHeight = ($newWidth / $originalWidth) * $originalHeight;

        // Calculate the scale factors
        $scaleX = $originalWidth / $newWidth;
        $scaleY = $originalHeight / $newHeight;

        foreach ($data->signers as $signer) {
            if ($signer->id == $signer_id) {
                foreach ($signer->requestFields as $field) {
                    if ($field->page_index == ($pageNumber - 1)) {
                        // Original coordinates
                        $originalX = $field->x;
                        $originalY = $field->y;
        
                        $width = $field->width;
                        $height = $field->height;
        
                        // Calculate the new coordinates based on the scaling
                        $newX = $originalX * $scaleX;
                        $newY = $originalY * $scaleY;
        
                        // Handle signature field
                        if ($field->type == 'signature') {
                            if (!in_array($signer->id, $processedSigners)) {
                                $contactFirstName = $signer->signerContactDetail->contact_first_name ?? 'Not Found';
                                $contactLastName = $signer->signerContactDetail->contact_last_name ?? 'Not Found';
                                $fullName = $contactFirstName . ' ' . $contactLastName;
                                $processedSigners[] = $signer->id;
                            }
        
                            // Convert signer name to image
                            $signatureImagePath = $this->createSignatureImage($fullName);
        
                            // Image dimensions and position
                            $imageWidth = $width * $scaleX;
                            $imageHeight = $height * $scaleY;
        
                            // Check if the image file exists
                            if (file_exists($signatureImagePath)) {
                                // Add the signature image to the PDF
                                $pdf->Image($signatureImagePath, $newX, $newY, $imageWidth, $imageHeight);
                            }
                        }
        
                        // Handle text input field
                        elseif ($field->type == 'textinput') {
                            $answer = $field->answer;
        
                            // Set font and size (adjust to fit your needs)
                            $pdf->SetFont('Helvetica', '', 16);
                            
                            // Calculate the text position and width
                            $textX = $newX;
                            $textY = $newY;
        
                            // Optionally, adjust the text to fit within the field's width
                            $pdf->SetXY($textX, $textY);
        
                            // Place the text on the PDF (adjust width for fitting within field)
                            $pdf->MultiCell($width * $scaleX, 10, $answer);
                        }elseif($field->type == 'mention'){
                            
                            $answer = $field->question;
        
                            // Set font and size (adjust to fit your needs)
                            $pdf->SetFont('Helvetica', '', 16);
                            
                            // Calculate the text position and width
                            $textX = $newX;
                            $textY = $newY;
        
                            // Optionally, adjust the text to fit within the field's width
                            $pdf->SetXY($textX, $textY);
        
                            // Place the text on the PDF (adjust width for fitting within field)
                            $pdf->MultiCell($width * $scaleX, 10, $answer);
                            
                        }elseif ($field->type == 'radio') {
                    // Retrieve all radio buttons associated with the current field
                    $radioButtons = RadioButton::where('field_id', $field->id)->get();
                
                    // Check if field answer is valid and points to an existing radio button
                    if (!is_null($field->answer) && isset($radioButtons[$field->answer])) {
                        // Get the selected radio button based on the index stored in field answer
                        $selectedRadioButton = $radioButtons[$field->answer];
                
                        // Get the position for the selected radio button
                        $radioX = $selectedRadioButton->x; // X position of the selected radio button
                        $radioY = $selectedRadioButton->y; // Y position of the selected radio button
                
                        // Apply the same scaling and transformations as the signature image
                        $scaledX = $radioX * $scaleX; // Apply horizontal scaling (if necessary)
                        $scaledY = $radioY * $scaleY; // Apply vertical scaling (if necessary)
                
                        // Set the size of the radio button image (adjust as needed)
                        $imageWidth = 8;  // Width for the radio button image
                        $imageHeight = 8; // Height for the radio button image
                
                        // Determine which image to use based on selection status
                        $radioImagePath = public_path('radio-checked.png');
                
                        // Check if the image file exists
                        if (file_exists($radioImagePath)) {
                            // Add the radio button image to the PDF at the scaled position
                            $pdf->Image($radioImagePath, $scaledX, $scaledY, $imageWidth, $imageHeight);
                        } else {
                            // Fallback if the image is not found
                            $pdf->SetFont('Helvetica', 'B', 10); // Adjust font and size for text fallback
                            $pdf->Text($scaledX, $scaledY, '✓'); // Simple text fallback (✓ if selected)
                        }
                    } else {
                        // No valid answer or radio button found, handle unselected or fallback
                        $pdf->SetFont('Helvetica', 'B', 10); // Adjust font and size for text fallback
                        $pdf->Text($newX, $newY, '○'); // Simple text fallback (empty circle for unselected)
                    }
                }elseif($field->type == 'checkbox') {
                            // Answer is either 'true' (checked) or 'false' (unchecked)
                            $isChecked = $field->answer === 'true';
                        
                            // Set dimensions for checkbox (adjust as needed)
                            $boxSize = 5; // Set a fixed box size or adjust based on your scaling
                        
                            // Draw the checkbox (a square)
                            $pdf->Rect($newX, $newY, $boxSize, $boxSize);
                        
                            // If checked, draw a checkmark manually using lines
                            if ($isChecked) {
                                // Drawing a simple checkmark using lines
                                $pdf->Line($newX + 1, $newY + 2, $newX + 2, $newY + 4); // Diagonal line (left)
                                $pdf->Line($newX + 2, $newY + 4, $newX + 4, $newY + 1); // Diagonal line (right)
                            } else {
                                // If not checked, draw an 'X' or leave it empty
                                // Option 2: Drawing an 'X' if unchecked (if you prefer marking unchecked boxes)
                                $pdf->Line($newX, $newY, $newX + $boxSize, $newY + $boxSize); // Diagonal line from top-left to bottom-right
                                $pdf->Line($newX + $boxSize, $newY, $newX, $newY + $boxSize); // Diagonal line from top-right to bottom-left
                            }
                            
                            
                        }elseif($field->type == 'readonlytext'){
                            
                            $answer = $field->question;
        
                            // Set font and size (adjust to fit your needs)
                            $pdf->SetFont('Helvetica', '', 16);
                            
                            // Calculate the text position and width
                            $textX = $newX;
                            $textY = $newY;
        
                            // Optionally, adjust the text to fit within the field's width
                            $pdf->SetXY($textX, $textY);
        
                            // Place the text on the PDF (adjust width for fitting within field)
                            $pdf->MultiCell($width * $scaleX, 12, $answer);
                            
                        }

                    }
                    
                    if ($field->type == 'initials') {
                        // Retrieve the first and last names from the signer contact details
                        $contactFirstName = $signer->signerContactDetail->contact_first_name ?? 'Not Found';
                        $contactLastName = $signer->signerContactDetail->contact_last_name ?? 'Not Found';
                        
                        // Original coordinates
                        $originalX = $field->x;
                        $originalY = $field->y;
        
                        $width = $field->width;
                        $height = $field->height;
        
                        // Calculate the new coordinates based on the scaling
                        $newX = $originalX * $scaleX;
                        $newY = $originalY * $scaleY;
                        
                        // Get the initials by taking the first letter of each name
                        $initials = strtoupper(substr($contactFirstName, 0, 1)) . strtoupper(substr($contactLastName, 0, 1));
                        
                        // Create initials image
                        $imagePath = $this->createInitialsImage($initials);
                        
                        // Define the position for the initials image (adjust $newX and $newY accordingly)
                        $initialsY = $newY; // Y position for initials
                        
                        // Set default X position
                        $defaultX = 0; // Default X position
                    
                        // Determine the X position based on alignment
                        switch (strtolower($field->question)) {
                            case 'left':
                                $initialsX = $defaultX; // Align to the left
                                break;
                            case 'center':
                                $pageWidth = $pdf->GetPageWidth();
                                $imageWidth = 40; // Width of the image
                                $initialsX = ($pageWidth - $imageWidth) / 2; // Center horizontally
                                break;
                            case 'right':
                                $pageWidth = $pdf->GetPageWidth();
                                $imageWidth = 40; // Width of the image
                                $initialsX = $pageWidth - $imageWidth; // Align to the right
                                break;
                            default:
                                $initialsX = $defaultX; // Default X position if position is not recognized
                        }
                        
                        // Add initials image to the PDF at the specified position
                        $pdf->Image($imagePath, $initialsX, $initialsY, 40, 20); // Adjust width and height as needed
                    }
                }
            }
        }
        
    }

    // Save the new or updated signed PDF
    $pdf->Output($signedFilePath, 'F');
    
    $signedPath = 'files/' . $signedFileName;
        
    UserRequest::where('id',$request_id)->update(['signed_file'=>$signedPath]);

    return response()->json([
        'message' => 'PDF annotated and saved successfully',
        'signed_pdf' => asset('files/' . $signedFileName),
    ], 200);

}
    
    
    
    /**
     * Create an image from the signer name
     *
     * @param string $name
     * @return string Path to the generated image
     */
    private function createSignatureImage($name)
    {
        // Path to the font file
        $fontPath = public_path('BrightSunshine.ttf'); // Replace with the path to your handwritten font
    
        // Path to save the signature image
        $imagePath = public_path('files/signatures/' . time() . '_signature.png');
    
        // Set initial image dimensions
        $width = 720;
        $height = 400;
        $im = imagecreatetruecolor($width, $height);
    
        // Allocate a color for the background (transparent)
        $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
        imagefill($im, 0, 0, $transparent);
    
        // Enable alpha blending and save alpha channel
        imagealphablending($im, false);
        imagesavealpha($im, true);
    
        // Allocate a color for the text (black)
        $textColor = imagecolorallocate($im, 0, 0, 0);
    
        // Start with a large font size
        $fontSize = 200;
        $box = imagettfbbox($fontSize, 0, $fontPath, $name);
    
        // Calculate text width and adjust font size
        $textWidth = $box[2] - $box[0];
        while ($textWidth > $width - 50) {
            $fontSize -= 1;
            $box = imagettfbbox($fontSize, 0, $fontPath, $name);
            $textWidth = $box[2] - $box[0];
        }
    
        // Calculate text position
        $y = $height / 1.7;
        $x = ($width - $textWidth) / 2;
    
        // Add text to image
        imagettftext($im, $fontSize, 0, $x, $y, $textColor, $fontPath, $name);
    
        // Save the image with PNG format to maintain transparency
        imagepng($im, $imagePath);
        imagedestroy($im);
    
        return $imagePath;
    }
    
    private function createInitialsImage($initials)
{
    // Path to the font file
    $fontPath = public_path('BrightSunshine.ttf'); // Replace with the path to your custom font

    // Path to save the initials image
    $directoryPath = public_path('files/initials/');
    // Ensure the directory exists, create it if necessary
    if (!is_dir($directoryPath)) {
        mkdir($directoryPath, 0755, true);
    }
    
    $imagePath = $directoryPath . time() . '_initials.png';

    // Set initial image dimensions
    $width = 200;
    $height = 100;
    $im = imagecreatetruecolor($width, $height);

    // Allocate a color for the background (transparent)
    $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
    imagefill($im, 0, 0, $transparent);

    // Enable alpha blending and save alpha channel
    imagealphablending($im, false);
    imagesavealpha($im, true);

    // Allocate a color for the text (black)
    $textColor = imagecolorallocate($im, 0, 0, 0);

    // Start with a large font size
    $fontSize = 30;
    $box = imagettfbbox($fontSize, 0, $fontPath, $initials);

    // Calculate text width and adjust font size if necessary
    $textWidth = $box[2] - $box[0];
    while ($textWidth > $width - 20) {
        $fontSize -= 1;
        $box = imagettfbbox($fontSize, 0, $fontPath, $initials);
        $textWidth = $box[2] - $box[0];
    }

    // Calculate text position
    $y = ($height + $fontSize) / 2; // Vertical center
    $x = ($width - $textWidth) / 2; // Horizontal center

    // Add text to image
    imagettftext($im, $fontSize, 0, $x, $y, $textColor, $fontPath, $initials);

    // Save the image with PNG format to maintain transparency
    imagepng($im, $imagePath);
    imagedestroy($im);

    return $imagePath;
}


        

}
