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
use App\Models\OtpCharge;
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
use Spatie\PdfToImage\Pdf as SpatiePdf; 
use Stripe\Stripe;
use Stripe\Charge;
use Stripe\PaymentIntent;
use Stripe\Customer;



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
        ->whereNot('status', 'cancelled')
        ->whereNot('status', 'rejected')
        ->whereNot('status', 'declined')
        ->orderBy('id', 'desc')
        ->get();
    
        $responseData = [];
        
         return response()->json([
            'data' => $data,
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
            
            
            Signer::where('request_id',$request_data->id)
            ->whereNot('unique_id',$signer->unique_id)
            ->update(['status'=>'-']);
            
            $contact = Contact::where('id',$signer->recipient_contact_id)->first();
            
            $userName = $contact->contact_first_name.' '.$contact->contact_last_name;

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
        
        
        
        /*
        \Log::info('Request data:', [
            'method' => $request->getMethod(),
            'url' => $request->fullUrl(),
            'data' => $request->all(),
        ]);*/


        try {
            // Validate incoming request
            /*
            $request->validate([
                'file' => 'required|mimes:pdf',
                'thumbnail' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]); */
            
            /*
            $filePath = $this->storeFile($request->file('file'), 'files');
            $originalFileName = $request->file('file')->getClientOriginalName();
            */
    
            // Store thumbnail
            /*
            $thumbnailPath = $this->storeFile($request->file('thumbnail'), 'thumbnails'); */

            $userName = getUserName($request);

            $userRequest = new UserRequest();
            $userRequest->user_id = Auth::id();
            $userRequest->file = $request->file;
            //$userRequest->thumbnail = $thumbnailPath;
            $userRequest->unique_id = $request->unique_id;
            $userRequest->file_name = $request->file_name;
            $userRequest->file_key = $request->file_name;
            $userRequest->sender_name = $userName;
            $userRequest->sent_date = Carbon::now();
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
    
    public function uploadThumbnail(Request $request){
        
        $thumbnailPath = $this->storeFile($request->file('thumbnail'), 'thumbnails');
        
        $request = UserRequest::find($request->request_id);
        
        if($request){
            $request->thumbnail = $thumbnailPath;
            $request->update();
        
             return response()->json([
                    'data' => $request,
                    'message' => 'Thumbnail generated successfully.'
                ], 200);
        }else{
            return response()->json([
                   
                    'message' => 'Request not found.'
                ], 200);
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
                'data' => $userRequestData,
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

        if($data->status == 'draft'){
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

       if ($data->email_otp == 1) {
            if ($signer->otp_verified == 0) {
                return response()->json([
                    'message' => 'Email OTP.'
                ], 200);
            } elseif ($signer->otp_verified == 1) {
                // Check if otp_verified_date is more than 90 seconds ago
                $otpVerifiedAt = Carbon::parse($signer->otp_verified_date);
                if ($otpVerifiedAt->diffInSeconds(now()) > 1) {
                    return response()->json([
                        'message' => 'Email OTP.'
                    ], 200);
                }
            }
        }
        
        if ($data->sms_otp == 1) {
            if ($signer->otp_verified == 0) {
                return response()->json([
                    'message' => 'SMS OTP.'
                ], 200);
            } elseif ($signer->otp_verified == 1) {
                // Check if otp_verified_date is more than 90 seconds ago
                $otpVerifiedAt = Carbon::parse($signer->otp_verified_date);
                if ($otpVerifiedAt->diffInSeconds(now()) > 1) {
                    return response()->json([
                        'message' => 'SMS OTP.'
                    ], 200);
                }
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
        /*
        $filePath = public_path($data->file);
    
        // Check if the file exists
        if (!File::exists($filePath)) {
            return response()->json([
                'message' => 'File not found.',
                'error_code' => 'no_data',
            ], 200);
        }
    
        // Read the file content
        $fileContent = File::get($filePath); */

       if($data->signed_file != null){
           
            $signedfileContent = $data->file;
        }else{
            $signedfileContent = null;
        }
        
        //file from s3 and base64
        // Use the relative path of the file, not the full URL
       $filePath = $data->file_key;  // Path relative to the bucket

    // Step 1: Check if the file exists on S3
    if (!Storage::disk('s3')->exists($filePath)) {
        return response()->json([
            'message' => 'File not found on S3.',
            'error_code' => 'no_data',
        ], 200);
    }
    
    // Step 2: Fetch the file content as a binary stream
    $pdfStream = Storage::disk('s3')->getDriver()->readStream($filePath);
    
    // Step 3: Read the stream into a string
    $pdfContent = stream_get_contents($pdfStream);
    fclose($pdfStream); // Close the stream
    
    // Step 4: Generate response with base64 encoded file content
    return response()->json([
        'data' => $data,
        'pdf_file' => $data->file, // Consistent base64 encoding
        'signed_file' => $data->signed_file,
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
        
        /*
    
        // Retrieve the file path
        $filePath = public_path($data->file);
    
        // Check if the file exists
        if (!File::exists($filePath)) {
            return response()->json([
                'message' => 'File not found.'
            ], 404);
        } 
    
        // Read the file content
        $fileContent = File::get($filePath); */
        
        if($data->signed_file != null){
            $signedFilePath = '';

            if (!File::exists($signedFilePath)) {
                $signedfileContent = null;
            }else{
                $signedfileContent = '';
            }
        }else{
            $signedfileContent = null;
        }

        
    
        // Generate response with file content and other data
        return response()->json([
            'data' => $data,
            'pdf_file' => $data->file, // Convert file content to base64
            'signed_file' => '',
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

        if($data->status == 'draft'){
            return response()->json([
                'message' => 'No data available.',
                'error_code' => 'no_data'
            ], 200);
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
       // $filePath = public_path($data->file);

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
        /*
        if (!File::exists($filePath)) {
            return response()->json([
                'message' => 'File not found.'
            ], 404);
        } */

        
    
        // Read the file content
        //$fileContent = File::get($filePath);
    
        // Generate response with file content and other data
        return response()->json([
            'data' => $data,
             'pdf_file' => $data->file, // Convert file content to base64
            'signed_file' => '',
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
                $this->sendSMSOTP($phone, $otp, $requestdata->user_id, $requestdata->id, $getUser->id);
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

        $signer = Signer::where('unique_id', $request->recipient_unique_id)
            ->where('request_id', $requestdata->id)
            ->first();
        
        if ($signer) {
            // Update the properties
            $signer->otp_verified = 1;
            $signer->otp_verified_date = now(); // or Carbon::now()
        
            // Save the changes
            $signer->save(); // Use save() instead of update()
        }
        
        
        $uRequestdata = UserRequest::with(['signers','signers.requestFields','signers.signerContactDetail'])
            ->where('unique_id',$request->request_unique_id)
            ->first();
            
            // Retrieve the file path
            /*
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
        } */
        
        
        if($data->signed_file != null){
           
            $signedfileContent = $uRequestdata->file;
        }else{
            $signedfileContent = null;
        }
        
        //file from s3 and base64
        // Use the relative path of the file, not the full URL
        $filePath = $uRequestdata->file_key;  // Path relative to the bucket

        // Step 1: Check if the file exists on S3
        if (!Storage::disk('s3')->exists($filePath)) {
            return response()->json([
                'message' => 'File not found on S3.',
                'error_code' => 'no_data',
            ], 200);
        }
        
        // Step 2: Fetch the file content as a binary stream
        $pdfStream = Storage::disk('s3')->getDriver()->readStream($filePath);
        
        // Step 3: Read the stream into a string
        $pdfContent = stream_get_contents($pdfStream);
        fclose($pdfStream); // Close the stream
        
    
        // Generate response with file content and other data
        return response()->json([
            'data' => $uRequestdata,
            'pdf_file' => $uRequestdata->file, // Convert file content to base64
            'signed_file' => $uRequestdata->signed_file,
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

        $protection_key = $this->generateProtectionKey($requestdata->user_id);

        $today_date = Carbon::now();
        $formatted_date = $today_date->format('Y-m-d H:i:s');

        Signer::where('request_id',$requestdata->id)
        ->where('unique_id',$request->recipient_unique_id)
        ->update(['status'=>'signed','signed_date'=>$formatted_date, 'protection_key'=>$protection_key]);

        $signeruser = Signer::where('request_id',$requestdata->id)
        ->where('unique_id',$request->recipient_unique_id)->first();
        $signeruser_data = User::find($signeruser->recipient_user_id);
        $signeruser_contact = Contact::find($signeruser->recipient_contact_id);


        //manage signed file 

        $this->addSignerPDF($requestdata->id,$signeruser->id, $protection_key);
        
        $requestdata = UserRequest::where('unique_id',$request->request_unique_id)->first();

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
            'protection_key' => $protection_key
            
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
        
        $signed_req = UserRequest::where('unique_id',$request->request_unique_id)->first();

        return response()->json([
            'signed_file' => $signed_req->signed_file,
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
        ->whereNot('unique_id',$request->approver_unique_id)
        ->update(['status'=>'-']);


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

    public function sendSMSOTP($phone, $otp, $sender_id, $request_id, $signer_user_id)
    {
        $to = $phone;
        $message = 'Your OTP for Signature1618: ' . $otp;

        // Charge user
        $senderUser = User::find($sender_id);
        if ($senderUser) {
            $stripe_token = $senderUser->stripe_token;

            $otpcharge = OtpCharge::where('request_id', $request_id)
                ->where('signer_user_id', $signer_user_id)
                ->first();

            if (!$stripe_token) {
                // Save as a failed payment due to missing stripe token
                $otpcharge = $otpcharge ?? new OtpCharge();
                $otpcharge->request_id = $request_id;
                $otpcharge->signer_user_id = $signer_user_id;
                $otpcharge->amount = 0.90;
                $otpcharge->stripe_message = 'No Stripe token available for this user.';
                $otpcharge->status = 0;
                $otpcharge->transaction_id = "";
                $otpcharge->save();

                $this->twilio->sendSMS($to, $message);

                return response()->json(['message' => 'SMS sent and charge completed successfully']);
            }

            if (!$otpcharge || $otpcharge->status != 1) {
                $amount = 0.90;
                $stripe_amount = $amount * 100;

                Stripe::setApiKey(env('STRIPE_SECRET'));

                try {
                    $customer = Customer::retrieve($stripe_token);
                    $paymentMethod = $customer->invoice_settings->default_payment_method;

                    if (!$paymentMethod) {
                        return response()->json(['error' => 'No default payment method found for this customer.'], 400);
                    }

                    $paymentIntent = PaymentIntent::create([
                        'amount' => $stripe_amount,
                        'currency' => 'usd',
                        'customer' => $stripe_token,
                        'payment_method' => $paymentMethod,
                        'off_session' => true,
                        'confirm' => true,
                        'description' => 'SMS OTP charge for Signature1618',
                    ]);

                    $otpcharge = $otpcharge ?? new OtpCharge();
                    $otpcharge->request_id = $request_id;
                    $otpcharge->signer_user_id = $signer_user_id;
                    $otpcharge->amount = $amount;
                    $otpcharge->stripe_message = $paymentIntent->status;
                    $otpcharge->status = $paymentIntent->status === 'succeeded' ? 1 : 0;
                    $otpcharge->transaction_id = $paymentIntent->id ?? ''; // Store transaction ID if available
                    $otpcharge->save();
                    
                    if ($paymentIntent->status !== 'succeeded') {
                        return response()->json(['error' => 'Payment not completed, status: ' . $paymentIntent->status], 500);
                    }

                } catch (\Exception $e) {
                    $otpcharge = $otpcharge ?? new OtpCharge();
                    $otpcharge->request_id = $request_id;
                    $otpcharge->signer_user_id = $signer_user_id;
                    $otpcharge->amount = $amount;
                    $otpcharge->stripe_message = $e->getMessage();
                    $otpcharge->status = 0;
                    $otpcharge->transaction_id = "";
                    $otpcharge->save();

                    return response()->json(['error' => $e->getMessage()], 500);
                }
            }
        }

        // Send SMS after the charge is successful
        $this->twilio->sendSMS($to, $message);

        return response()->json(['message' => 'SMS sent and charge completed successfully']);
    }

    public function testCharge()
    {
        $stripe_token = 'cus_R4pfyyge0NwLb0';
    
        // Set up Stripe API key
        Stripe::setApiKey('sk_test_51OpS8qB0Nlv2z5Xgr2fM7Ewp1HAec0u5ovlLG3rLVqH3SA3a1r5dRl2p910lvR5TkFoHZZzFbbU7qifwm213nKQo00dGJu0ECF');
    
        try {
            // Retrieve the customer's default payment method
            $customer = Customer::retrieve($stripe_token);
            $paymentMethod = $customer->invoice_settings->default_payment_method;
    
            if (!$paymentMethod) {
                return response()->json(['error' => 'No default payment method found for this customer.'], 400);
            }
    
            // Create and confirm the PaymentIntent with the default payment method
            $paymentIntent = PaymentIntent::create([
                'amount' => 90, // Amount in cents
                'currency' => 'usd',
                'customer' => $stripe_token,
                'payment_method' => $paymentMethod,
                'off_session' => true, // Indicates that this payment is being made without user interaction
                'confirm' => true, // Confirm the PaymentIntent immediately
                'description' => 'SMS OTP charge for Signature1618',
            ]);
    
            // Optional: Check the status of the payment
            if ($paymentIntent->status === 'succeeded') {
                return "Payment succeeded";
            } else {
                return "Payment not completed, status: " . $paymentIntent->status;
            }
        } catch (\Exception $e) {
            // Handle the error (log it, notify, etc.)
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
        if(!$data || $data->is_trash == 1){
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
        if(!$data || $data->is_trash == 0){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }
        $data->is_trash = 0;
        $data->update();
        
        $userName = getUserName($request);
        
        $this->addRequestLog("restored_request", "Request restored from trash", $userName, $data->id);

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
                        'receiver_name'=> $user_obj->name.' '.$user_obj->last_name,
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
                    'receiver_name'=> $user_obj->name.' '.$user_obj->last_name,
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
                
                    Mail::to($signer_user->email)->send(new \App\Mail\RequestCancelledBySenderToSigner($dataUserToSigner, $subjectToSigner));
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
    
    public function dFile($request_id)
    {
        // Fetch data from the database
        $data = UserRequest::with([
                'userDetail', 'signers', 'signers.requestFields', 'signers.signerContactDetail',
                'approvers', 'approvers.approverContactDetail', 'approvers.approverContactDetail.contactUserDetail',
                'signers.signerContactDetail.contactUserDetail'
            ])
            ->where('is_trash', 0)
            ->where('id', $request_id)
            ->orderBy('id', 'desc')
            ->first();
    
        // Original PDF file path on AWS S3
        $originalFilePath = $data->file_key;
        $pdfContent = Storage::disk('s3')->get($originalFilePath);
    
        // Temporary input file for conversion
        $inputPdfPath = sys_get_temp_dir() . '/' . basename($originalFilePath);
        file_put_contents($inputPdfPath, $pdfContent);
    
        // Define output PDF file path (temp file)
        $outputPdfPath = sys_get_temp_dir() . '/temp_output.pdf';
    
        // Ghostscript command to convert PDF to version 1.4
        $command = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=\"{$outputPdfPath}\" \"{$inputPdfPath}\" 2>&1";
    
        // Execute the command and capture the output
        $output = shell_exec($command);
    
        // Log the command output for debugging
        \Log::info("Ghostscript Output: {$output}");
    
        // Check if the output PDF was created and is not empty
        if (file_exists($outputPdfPath) && filesize($outputPdfPath) > 0) {
            return $outputPdfPath;
        } else {
            return response()->json(['message' => 'Failed to process PDF.', 'error' => $output], 500);
        }
    }
    
    public function sdFile($request_id)
    {
        // Fetch data from the database
        $data = UserRequest::with([
                'userDetail', 'signers', 'signers.requestFields', 'signers.signerContactDetail',
                'approvers', 'approvers.approverContactDetail', 'approvers.approverContactDetail.contactUserDetail',
                'signers.signerContactDetail.contactUserDetail'
            ])
            ->where('is_trash', 0)
            ->where('id', $request_id)
            ->orderBy('id', 'desc')
            ->first();
    
        // Original PDF file path on AWS S3
        $originalFilePath = $data->signed_file_key;
        $pdfContent = Storage::disk('s3')->get($originalFilePath);
    
        // Temporary input file for conversion
        $inputPdfPath = sys_get_temp_dir() . '/' . basename($originalFilePath);
        file_put_contents($inputPdfPath, $pdfContent);
    
        // Define output PDF file path (temp file)
        $outputPdfPath = sys_get_temp_dir() . '/temp_output.pdf';
    
        // Ghostscript command to convert PDF to version 1.4
        $command = "gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=\"{$outputPdfPath}\" \"{$inputPdfPath}\" 2>&1";
    
        // Execute the command and capture the output
        $output = shell_exec($command);
    
        // Log the command output for debugging
        \Log::info("Ghostscript Output: {$output}");
    
        // Check if the output PDF was created and is not empty
        if (file_exists($outputPdfPath) && filesize($outputPdfPath) > 0) {
            return $outputPdfPath;
        } else {
            return response()->json(['message' => 'Failed to process PDF.', 'error' => $output], 500);
        }
    }

    public function addSignerPDF($request_id = null, $signer_id = null, $protection_key = null)
    {
        // Convert the PDF to a compatible version
        $pdfPath = $this->dFile($request_id);
    
        if (!$pdfPath) {
            return response()->json(['message' => 'PDF conversion failed.'], 500);
        }
    
        // Fetch data for annotations
        $data = UserRequest::with([
                'userDetail','signers','signers.requestFields','signers.signerContactDetail',
                'approvers','approvers.approverContactDetail', 'approvers.approverContactDetail.contactUserDetail',
                'signers.signerContactDetail.contactUserDetail'
            ])
            ->where('is_trash', 0)
            ->where('id', $request_id)
            ->orderBy('id', 'desc')
            ->first();
    
        // AWS S3 signed file path
        $signedFileName = 'signed_' . Str::afterLast($data->file, '/');
        
        
        if($data->signed_file_key != null){
            
            $signedFilePath = $data->signed_file_key;
            //$pdfPath = Storage::disk('s3')->get($signedFilePath);
            $pdfPath = $this->sdFile($request_id);
            
        }else{
            $signedFilePath = 'signed_' . Str::afterLast($data->file_key, '/');
        }
        
    
        // Initialize FPDI
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($pdfPath);
    
        // Array to track processed signers
        $processedSigners = [];
    
        // Loop through each page of the PDF
        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templateId = $pdf->importPage($pageNumber);
            $size = $pdf->getTemplateSize($templateId);
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
    
            // Scaling for annotations
            $newWidth = 600;
            $scaleX = $size['width'] / $newWidth;
            $scaleY = $size['height'] / (($newWidth / $size['width']) * $size['height']);
    
            foreach ($data->signers as $signer) {
                if ($signer->id == $signer_id) {
                    foreach ($signer->requestFields as $field) {
                        if ($field->page_index == ($pageNumber - 1)) {
                            $newX = $field->x * $scaleX;
                            $newY = $field->y * $scaleY;
                            $width = $field->width * $scaleX;
                            $height = $field->height * $scaleY;
    
                            // Field Type Handling
                            switch ($field->type) {
                                case 'signature':
                                    $fullName = $signer->signerContactDetail->contact_first_name . ' ' . $signer->signerContactDetail->contact_last_name;
                                    $signatureImagePath = $this->createSignatureImage($fullName, $protection_key);
                                    if (file_exists($signatureImagePath)) {
                                        $pdf->Image($signatureImagePath, $newX, $newY, $width, $height);
                                    }
                                    $processedSigners[] = $signer->id;
                                    break;
    
                                case 'textinput':
                                case 'mention':
                                case 'readonlytext':
                                    $pdf->SetFont('Helvetica', '', 16);
                                    $pdf->SetXY($newX, $newY);
                                    $pdf->MultiCell($width, 10, $field->type == 'textinput' ? $field->answer : $field->question);
                                    break;
    
                                case 'radio':
                                    $radioImagePath = public_path('radio-checked.png');
                                    $selectedRadioButton = RadioButton::where('field_id', $field->id)->get()[$field->answer] ?? null;
                                    if ($selectedRadioButton && file_exists($radioImagePath)) {
                                        $pdf->Image($radioImagePath, $selectedRadioButton->x * $scaleX, $selectedRadioButton->y * $scaleY, 8, 8);
                                    } else {
                                        $pdf->SetFont('Helvetica', 'B', 10);
                                        $pdf->Text($newX, $newY, '✓');
                                    }
                                    break;
    
                                case 'checkbox':
                                // Set the paths for the checked and unchecked images
                                $checkedImagePath = public_path('checked.png');
                                $uncheckedImagePath = public_path('unchecked.png');
                            
                                // Determine the image to use based on the answer
                                $imagePath = ($field->answer === 'true') ? $checkedImagePath : $uncheckedImagePath;
                            
                                // Check if the file exists before adding the image
                                if (file_exists($imagePath)) {
                                    $pdf->Image($imagePath, $newX, $newY, 8, 8); // Adjust the size (8x8) as needed
                                } else {
                                    // Fallback if the image does not exist
                                    $pdf->SetFont('Helvetica', 'B', 10);
                                    $pdf->Text($newX, $newY, '✓'); // Placeholder for the checkmark
                                }
                                break;

    
                                case 'initials':
                                    
                                    
                                    $newY = $newY-5;
                                    $newX = $newX-5;
                                    //$newY = 192.0875;
                                    
                                    if(isset($field->question)){
                                        if($field->question === "center"){
                                        $newX = 88;
                                        }elseif($field->question === "right"){
                                            $newX = 184;
                                        }
                                        
                                        
                                    }
                                    
                                    
                                    
                                    $initials = strtoupper(substr($signer->signerContactDetail->contact_first_name, 0, 1)) . strtoupper(substr($signer->signerContactDetail->contact_last_name, 0, 1));
                                    $initialsImagePath = $this->createInitialsImage($initials);
                                    if (file_exists($initialsImagePath)) {
                                        $pdf->Image($initialsImagePath, $newX, $newY, 40, 20);
                                    }
                                    break;
                            }
                        }
                    }
                }
            }
        }
    
        // Save the signed PDF to a temporary location
        $signedFile = tempnam(sys_get_temp_dir(), 'signed_pdf');
        $pdf->Output($signedFile, 'F');
    
        // Upload the signed file back to AWS S3
        $uploaded = Storage::disk('s3')->put($signedFilePath, file_get_contents($signedFile), [
            'ContentType' => 'application/pdf',
        ]);
        
        $signedPath = Storage::disk('s3')->url($signedFilePath);
        
        UserRequest::where('id',$request_id)->update([
            'signed_file'=>$signedPath,
            'signed_file_key'=>$signedFilePath
        ]);
    
        // Remove the temporary file
        unlink($signedFile);
    
        return response()->json(['message' => 'PDF signed and uploaded to AWS successfully']);
    }

    
    function RoundedRect($pdf, $x, $y, $w, $h, $r, $style = 'S') {
        $k = $pdf->k;
        $hp = $pdf->h;
        $MyArc = 4/3 * (sqrt(2) - 1);
    
        // Move to the start point
        $pdf->_out(sprintf('%.2F %.2F m', ($x + $r) * $k, ($hp - $y) * $k));
    
        // Draw the sides
        $pdf->_out(sprintf('%.2F %.2F l', ($x + $w - $r) * $k, ($hp - $y) * $k));
        $pdf->_Arc($x + $w - $r + $r * $MyArc, $y + $r, $x + $w - $r, $y + $r - $r * $MyArc, $x + $w - $r, $y + $r);
    
        $pdf->_out(sprintf('%.2F %.2F l', ($x + $w) * $k, ($hp - ($y + $h)) * $k));
        $pdf->_Arc($x + $w - $r, $y + $h - $r, $x + $w - $r + $r * $MyArc, $y + $h - $r, $x + $w - $r, $y + $h - $r);
    
        $pdf->_out(sprintf('%.2F %.2F l', ($x + $r) * $k, ($hp - ($y + $h)) * $k));
        $pdf->_Arc($x + $r - $r * $MyArc, $y + $h - $r, $x + $r, $y + $h - $r + $r * $MyArc, $x + $r, $y + $h - $r);
    
        $pdf->_out(sprintf('%.2F %.2F l', $x * $k, ($hp - $y) * $k));
        $pdf->_Arc($x + $r, $y + $r - $r * $MyArc, $x + $r - $r * $MyArc, $y + $r, $x + $r, $y + $r);
    
        $pdf->_out($style == 'F' ? 'f' : 'S'); // Fill or Stroke the rectangle
    }
    
    function _Arc($pdf, $x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $pdf->h;
        $pdf->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c ', 
            $x1 * $pdf->k, ($h - $y1) * $pdf->k,
            $x2 * $pdf->k, ($h - $y2) * $pdf->k,
            $x3 * $pdf->k, ($h - $y3) * $pdf->k));
    }
        
        
        public function convertPdfVersion($oldPdfPath, $newPdfPath)
        {
            // Command to run Ghostscript
            $command = "gswin64c -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dNOPAUSE -dQUIET -dBATCH -sOutputFile=" . $newPdfPath . " " . $oldPdfPath;
    
            // Execute the shell command
            shell_exec($command);
    
            // Check if the new PDF was created successfully
            if (file_exists($newPdfPath)) {
                return response()->json(['message' => 'PDF version converted to 1.4 successfully.']);
            } else {
                return response()->json(['message' => 'Failed to convert PDF version.'], 500);
            }
        }
    
    /**
     * Create an image from the signer name
     *
     * @param string $name
     * @return string Path to the generated image
     */
    private function createSignatureImage($name, $protectionKey)
{
    // Paths to the font files
    $signatureFontPath = public_path('BrightSunshine.ttf'); // Handwritten font for signature
    $protectionFontPath = public_path('arial.ttf'); // Arial font file for protection key

    // Path to save the signature image
    $imagePath = public_path('files/signatures/' . time() . '_signature.png');

    // Set initial image dimensions
    $width = 720;
    $height = 500; // Adjust height to make room for the text
    $im = imagecreatetruecolor($width, $height);

    // Allocate a color for the background (transparent)
    $transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
    imagefill($im, 0, 0, $transparent);

    // Enable alpha blending and save alpha channel
    imagealphablending($im, false);
    imagesavealpha($im, true);

    // Allocate a color for the text (black)
    $textColor = imagecolorallocate($im, 0, 0, 0);

    // Add the signature name (First Line)
    $fontSize = 200;
    $box = imagettfbbox($fontSize, 0, $signatureFontPath, $name);
    $textWidth = $box[2] - $box[0];
    $signatureHeight = $box[1] - $box[7]; // Calculate signature height

    // Adjust font size if text is too wide
    while ($textWidth > $width - 50) {
        $fontSize -= 1;
        $box = imagettfbbox($fontSize, 0, $signatureFontPath, $name);
        $textWidth = $box[2] - $box[0];
        $signatureHeight = $box[1] - $box[7]; // Recalculate height after adjustment
    }

    // Calculate position for signature name
    $y = $height / 2; // Centering the name vertically
    $x = ($width - $textWidth) / 2;

    // Add signature name text
    imagettftext($im, $fontSize, 0, $x, $y, $textColor, $signatureFontPath, $name);

    // Add the combined "Verified by Signature1618" text and protection key on the same line
    $verifiedText = 'Verified by Signature1618';
    $verifiedFontSize = 25; // Font size for verified text
    $verifiedBox = imagettfbbox($verifiedFontSize, 0, $protectionFontPath, $verifiedText);
    $verifiedTextWidth = $verifiedBox[2] - $verifiedBox[0];

    // Add the protection key
    $keyFontSize = 25; // Same size for both
    $keyBox = imagettfbbox($keyFontSize, 0, $protectionFontPath, $protectionKey);
    $keyTextWidth = $keyBox[2] - $keyBox[0];

    // Calculate total width of the combined text
    $totalTextWidth = $verifiedTextWidth + 1 + $keyTextWidth; // Add some spacing between the two strings

    // Calculate position for the combined text to center it
    $combinedX = ($width - $totalTextWidth) / 2;

    // Increase spacing between signature and the "Verified by" text based on signature height
    $dynamicGap = max(10, $signatureHeight * 0.15); // Dynamic gap based on signature height
    $verifiedY = $y + $signatureHeight + $dynamicGap; // Use dynamic gap

    // Adjust the Y position of the verified text to account for a more consistent spacing
    if (strlen($name) < 20) { // If the name is short
        $verifiedY -= 40; // Reduce the gap for shorter names
    } elseif (strlen($name) >= 30) { // If the name is long
        $verifiedY += 30; // Increase the gap for longer names
    }

    // Add the "Verified by Signature1618" text
    imagettftext($im, $verifiedFontSize, 0, $combinedX, $verifiedY, $textColor, $protectionFontPath, $verifiedText);

    // Add the protection key next to the "Verified by Signature1618" text
    $keyX = $combinedX + $verifiedTextWidth + 20; // Place it after the verified text with spacing
    imagettftext($im, $keyFontSize, 0, $keyX, $verifiedY, $textColor, $protectionFontPath, $protectionKey);

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

public function generateProtectionKey($user_id)
{
    // Get user settings
    $user_settings = UserGlobalSetting::where('user_id', $user_id)
        ->where('meta_key', 'protection')
        ->first();

    // Determine protection type (default to 'standard' if not set)
    if ($user_settings) {
        $protection_type = $user_settings->meta_value;
    } else {
        $protection_type = 'standard';
    }

    // Generate protection key based on type (without base signature)
    switch ($protection_type) {
        case 'numerical':
            // Generate a random numerical ID format (ID followed by random numbers)
            $random_number = rand(1000000000, 9999999999); // 10-digit number
            $protection_key = 'ID' . $random_number . '•';
            break;

        case 'advanced':
            // Full set of Greek symbols
            $greek_symbols = [
                'Α', 'Β', 'Γ', 'Δ', 'Ε', 'Ζ', 'Η', 'Θ', 'Ι', 'Κ', 
                'Λ', 'Μ', 'Ν', 'Ξ', 'Ο', 'Π', 'Ρ', 'Σ', 'Τ', 'Υ', 
                'Φ', 'Χ', 'Ψ', 'Ω'
            ];

            // Pick 6 random symbols
            $random_greek_symbols = implode('', array_rand(array_flip($greek_symbols), 6));

            // Generate random numbers
            $random_number = rand(100000, 999999); // 6-digit number

            $protection_key = $random_greek_symbols . $random_number . '•';
            break;

        case 'standard':
        default:
            // Return an empty string for the 'standard' type
            $protection_key = '•'; 
            break;
    }

    // Return or use the generated protection key
    return $protection_key;
}



        

}
