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
    
            // Check if the file exists
            if (!File::exists($filePath)) {
                // If file not found, add a message to the response data
                $request->pdf_file = null; // Set pdf_file to null if file not found
                $responseData[] = $request;
                continue; // Skip to the next iteration
            }
    
            // Read the file content
            $fileContent = File::get($filePath);
    
            // Compress the file content
            $compressedContent = gzencode($fileContent, 9);
    
            // Encode compressed content to base64
            $base64CompressedContent = base64_encode($compressedContent);
    
            // Append base64 compressed file content to the request object
            $request->pdf_file = $base64CompressedContent;
    
            // Add the modified request object to the response data
            $responseData[] = $request;
        }
    
        // Generate response with modified data
        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);
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

            //adding activity log 
            $this->addRequestLog("new_request", "Signature request created", Auth::user()->name, $userRequest->id);
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

            $userName = getUserName();

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
                    $this->sendMail($approverUniqueId,$requestId,$usermail,$type=2);
                    }
                }

            }
            //ending create approver

    
            // Now you can iterate over recipients and process each one
            if(isset($requestData['recipients']) && count($recipients)>0){
                Signer::where('request_id',$requestId)->delete();
            }
            foreach ($recipients as $recipient) {
                // Access recipient data
                $recipientId = $recipient['recipientId'];
                $signerStatus = $recipient['signer_status'];
                $signerUniqueId = $recipient['signer_unique_id'];
                $fields = $recipient['fields'];
                
                $userId = Contact::where('unique_id', $recipientId)->first();
                $contactmaildata = User::find($userId->contact_user_id);
                $usermail = $contactmaildata->email;
                
    
                $signer = new Signer();
                $signer->request_id = $requestId;
                $signer->recipient_unique_id = $recipientId; // Assuming this is recipientId
                // You may need to adjust the following based on your database structure
                // Assuming you have a mapping between recipientId and user_id and contact_id
                $userId = Contact::where('unique_id', $recipientId)->first();
                $signer->recipient_user_id = $userId->contact_user_id;
                $signer->recipient_contact_id = $userId->id;
                $signer->status = $signerStatus;
                $signer->unique_id = $signerUniqueId;
                $signer->save();

                if(isset($approvers) && $approvers != null){

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
            $this->addRequestLog("sent_request", "Signature request sent", Auth::user()->name, $userRequestData->id);
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

        if($data->is_trash == 1){

            return response()->json([
                'message' => 'Request has been trashed.',
                'error_code' => 'request_trashed'
            ], 200);

        }

        if($data->status == 'cancelled'){

            return response()->json([
                'message' => 'Signature request cancelled',
                'error_code' => 'request_cancelled'
            ], 200);

        }

        if($data->approve_status == 0){
            return response()->json([
                'message' => 'Approve pending.'
            ], 200);
        }elseif($data->approve_status == 2){

            return response()->json([
                'message' => 'File rejected.'
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
                'message' => 'This link has been expired.'
            ], 401);
        } 

        //check signer status
        $signercheck = Signer::where('request_id',$data->id)
        ->where('unique_id',$request->recipient_unique_id)
        ->first();

        if($signercheck && $signercheck->status == 'signed'){
            return response()->json([
                'pdf_file' => '', // Convert file content to base64
                'message' => 'Already signed'
            ], 200);
        }
        //ending check signer status
    
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
    
        // Generate response with file content and other data
        return response()->json([
            'data' => $data,
            'pdf_file' => base64_encode($fileContent), // Convert file content to base64
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
    
        // Generate response with file content and other data
        return response()->json([
            'data' => $data,
            'pdf_file' => base64_encode($fileContent), // Convert file content to base64
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

        if($data->status == 'cancelled'){

            return response()->json([
                'message' => 'Signature request cancelled'
            ], 200);

        }

        if (Carbon::parse($data->expiry_date)->isPast()) {
            return response()->json([
                'message' => 'This link has been expired.'
            ], 401);
        } 

        if ($data->approve_status == 1 || $data->approve_status == 2) {
            return response()->json([
                'message' => 'Already answered.'
            ], 200);
        } 

        //check signer status
        $approvercheck = Approver::where('request_id',$data->id)
        ->where('unique_id',$request->recipient_unique_id)
        ->first();

        if($approvercheck && $approvercheck->status == 'approved'){
            return response()->json([
                'pdf_file' => '', // Convert file content to base64
                'message' => 'Already approved'
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

        $subject = $getContact->contact_first_name." your OTP for request file";

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
    
        // Generate response with file content and other data
        return response()->json([
            'data' => $uRequestdata,
            'pdf_file' => base64_encode($fileContent), // Convert file content to base64
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
        
        if($requestdata) {
        $filePath = $this->storeFile($request->file('signed_file'), 'files');
        $requestdata->signed_file = $filePath;
        //$requestdata->status =  'Done';
        $requestdata->update();
        }
        
        //storing fields answers
        
        for($i=0; $i<count($request->field_id); $i++){
            $data = RequestField::find($request->field_id[$i]);
            if($data){
                $data->answer = $request->answer[$i];
                $data->update();
            }  
            
        } 
        //ending storing fields answers
        

        Signer::where('request_id',$requestdata->id)
        ->where('unique_id',$request->recipient_unique_id)
        ->update(['status'=>'signed']);

        $signeruser = Signer::where('request_id',$requestdata->id)
        ->where('unique_id',$request->recipient_unique_id)->first();
        $signeruser_data = User::find($signeruser->recipient_user_id);
        $signeruser_contact = Contact::find($signeruser->recipient_contact_id);

        //sign registered

        $senderUser = User::find($requestdata->user_id);
        $useremail = $senderUser->email;
        $subject = 'Signature registered - Signature1618';

        $dataUser = [
            'email' => $signeruser_data->email,
            'sender_name' => $signeruser_contact->name.' '.$senderUser->last_name,
            'file_name' => $requestdata->file_name,
            'requestUID' => $requestdata->unique_id,
            'signed_file' => $requestdata->signed_file
            
        ];

        Mail::to($signeruser_data->email)->send(new \App\Mail\SignRegister($dataUser, $subject));

        //sign registered ending

        

        //update status for request
        $signercheck = Signer::where('request_id',$requestdata->id)
        ->where('status','pending')
        ->first();

        

        if(!$signercheck){

            $requestdata->signed_at = Carbon::now()->format('Y-m-d');
            $requestdata->update();

            UserRequest::where('unique_id',$request->request_unique_id)->update(['status'=>'done']);

            //fully signed email

            $senderUser = User::find($requestdata->user_id);
            $useremail = $senderUser->email;
            $subject = $requestdata->file_name.' is fully signed - Signature1618';

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

            $pendingSigners = Signer::where('request_id',$requestdata->id)
            ->where('status','pending')
            ->get();

            foreach($pendingSigners as $pending){

                $senderUser = User::find($pending->recipient_user_id);
                $useremail = $senderUser->email;
                $subject = $requestdata->file_name.' is fully signed - Signature1618';

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
                'message' => 'Signature request cancelled'
            ], 200);

        }
        


        $checkng = Approver::where('request_id',$requestdata->id)
        ->where('unique_id',$request->approver_unique_id)
        ->update(['status'=>'approved']);


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

        Approver::where('request_id',$requestdata->id)
        ->update(['status'=>'rejected']);


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
        $this->sendMail($sender->unique_id,$requestdata->id,$sender->email,$type=3);
       
        //ending sending mail to signers

        $approver = Approver::where('request_id',$requestdata->id)
        ->where('unique_id',$request->approver_unique_id)
        ->first();

        $contact = Contact::where('id',$approver->recipient_contact_id)->first();

        $full_name = $contact->contact_first_name.' '.$contact->contact_last_name;

        //adding activity log 
        $this->addRequestLog("rejected_request", "Rejected document", $full_name, $requestdata->id);
        //ending adding activity log

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

        $userName = getUserName();

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
        $formattedDate = $date->format('m/d/Y');
    
        $dataUser = [
            'email' => $email,
            'signerUID' => $signerUId,
            'requestUID' => $requestUid,
            'company_name' => $company_name,
            'file_name' => $userRequest->file_name,
            'expiry_date' => $formattedDate
        ];
    
        $subject = '';
        switch ($type) {
            case 1:
                $subject = 'Signature Request for'.$userRequest->file_name.' from '.$company_name.' via Signature1618';
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
    
        // Append current timestamp to subject
        $subject .= ' - ' . Carbon::now()->toDateTimeString();
    
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
        $this->addRequestLog("trashed_request", "Request moved to trash", Auth::user()->name.' '.Auth::user()->last_name, $data->id);
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

        //reminder 
        $subject = "Reminder to sign the document";
        
        $request_obj_approver = UserRequest::where('unique_id',$request->request_unique_id)
            ->where('approve_status',0)
            ->where('status','in progress')
            ->first();

            if($request_obj_approver){

                $subject = "Reminder to approve the document";

                $approver_obj = Approver::where('request_id',$request_obj_approver->id)
                ->where('status','pending')
                ->get();

                foreach($approver_obj as $approver){

                    $user_obj = User::find($approver->recipient_user_id);

                    $email = $user_obj->email;

                    $dataUser = [
                        'email' => $email,
                        'requestUID'=>$request_obj_approver->unique_id,
                        'signerUID'=>$approver->unique_id,
                        'custom_message'=>$request_obj_approver->custom_message,
                    ];

                    \Mail::to($email)->send(new \App\Mail\ReminderEmailApprover($dataUser, $subject));

                    $contact = Contact::where('id',$approver->recipient_contact_id)->first();

                    $full_name = $contact->contact_first_name.' '.$contact->contact_last_name;

                    //adding activity log 
                    $this->addRequestLog("reminder_request", "Reminder sent to ".$full_name, Auth::user()->name.' '.Auth::user()->last_name,  $request_obj_approver->id);
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
                    'email' => $email,
                    'requestUID'=>$request_obj->unique_id,
                    'signerUID'=>$signer->unique_id,
                    'custom_message'=>$request_obj->custom_message,
                ];

                \Mail::to($email)->send(new \App\Mail\ReminderEmail($dataUser, $subject));


                $contact = Contact::where('id',$signer->recipient_contact_id)->first();

                $full_name = $contact->contact_first_name.' '.$contact->contact_last_name;

                //adding activity log 
                $this->addRequestLog("reminder_request", "Reminder sent to ".$full_name, Auth::user()->name.' '.Auth::user()->last_name, $request_obj->id);
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

        if($request->request_status == "cancelled"){

            //adding activity log 
            $this->addRequestLog("cancelled_request", "Signature request cancelled", $userName, $data->id);
            //ending adding activity log

        }elseif($request->request_status == "declined"){

            $signer = Signer::where('recipient_user_id',Auth::user()->id)->where('request_id',$data->id)->first();
            $signer->status = "declined";
            $signer->update();

            //adding activity log 
            $this->addRequestLog("declined_request", "Request has been declined", $userName, $data->id);
            //ending adding activity log

            //send mail
            $dataUser = [
                'email' => $user->email,
                'sender_name' => $user->name.' '.$user->last_name,
                'requestUID' => $data->unique_id,
                'receiver_name' => Auth::user()->name.' '.Auth::user()->last_name,
                'signerUID' => $signer->unique_id
            ];
        
            $subject = 'Request to Sign Declined by '.Auth::user()->name.' '.Auth::user()->last_name;
        
            Mail::to($user->email)->send(new \App\Mail\DeclineSignSender($dataUser, $subject));
            //ending send mail

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
    
    
}
