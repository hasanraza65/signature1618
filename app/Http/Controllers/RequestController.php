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
use Auth;
use DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Services\TwilioService;
use Twilio\Rest\Client;
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
            $data = UserRequest::with(['userDetail','signers','signers.requestFields','signers.signerContactDetail','approvers','approvers.approverContactDetail', 'approvers.approverContactDetail.contactUserDetail','signers.signerContactDetail.contactUserDetail'])->orderBy('id','desc')->get();
        }else{
            $data = UserRequest::with(['userDetail','signers','signers.requestFields','signers.signerContactDetail','approvers','approvers.approverContactDetail', 'approvers.approverContactDetail.contactUserDetail','signers.signerContactDetail.contactUserDetail'])->where('user_id',Auth::user()->id)->orderBy('id','desc')->get();
        }
       
        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);

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

            $userRequestData = UserRequest::where('unique_id', $uniqueId)->first();

            $userRequestData->email_otp = $requestData['email_otp'];
            $userRequestData->sms_otp = $requestData['sms_otp'];
            $userRequestData->file_name = $requestData['file_name'];
            $userRequestData->status = "pending";
            $userRequestData->expiry_date = $requestData['expiry_date'];
            $userRequestData->custom_message = $requestData['custom_message'];
            if(isset($approvers) && $approvers != null){
                $userRequestData->approve_status = 0;
            }
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
                    
                    $this->sendMail($approverUniqueId,$requestId,$usermail,$type=2);
                }

            }
            //ending create approver

    
            // Now you can iterate over recipients and process each one
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
                    $this->sendMail($signerUniqueId,$requestId,$usermail,$type=1);
                }
    
                // Assuming you need to iterate over fields for each recipient
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
                            $radio->y = $optionquestion['option_x'];
                            $radio->field_id = $requestField->id;
                            $radio->save();
                        }
                        

                    }
                }
            }

            
    
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

        $data = UserRequest::with(['signers','signers.requestFields','signers.signerContactDetail'])
            ->where('unique_id',$request->request_unique_id)
            ->first();
        
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
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

    public function approverFetchRequest(Request $request){

        $data = UserRequest::with(['signers','signers.requestFields','signers.signerContactDetail','approvers','approvers.approverContactDetail', 'approvers.approverContactDetail.contactUserDetail','signers.signerContactDetail.contactUserDetail'])
            ->where('unique_id',$request->request_unique_id)
            ->first();
        
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
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
                'email'=>$email,
                'otp'=>$otp
           ];

        \Mail::to($email)->send(new \App\Mail\OTPEmail($dataUser));


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
        
        if($requestdata) {
        $filePath = $this->storeFile($request->file('signed_file'), 'files');
        $requestdata->signed_file = $filePath;
        //$requestdata->status =  'Done';
        $requestdata->update();
        }

        Signer::where('request_id',$requestdata->id)
        ->where('unique_id',$request->recipient_unique_id)
        ->update(['status'=>'signed']);

        //update status for request
        $signercheck = Signer::where('request_id',$requestdata->id)
        ->where('status','pending')
        ->first();

        if(!$signercheck){
            UserRequest::where('unique_id',$request->request_unique_id)->update(['status'=>'done']);
        }

        //ending update status for request

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


        $checkng = Approver::where('request_id',$requestdata->id)
        ->where('unique_id',$request->approver_unique_id)
        ->update(['status'=>'approved']);


        //update status for request
        $approvercheck = Approver::where('request_id',$requestdata->id)
        ->where('status','pending')
        ->first();

        if(!$approvercheck){
            UserRequest::where('unique_id',$request->request_unique_id)->update(['approve_status'=>1]);
        }

        //ending update status for request

        //sending mail to signers 
        $signers = Signer::where('request_id',$requestdata->id)->get();
        foreach($signers as $signer){

            $signer_user = User::find($signer->recipient_user_id);

            //return $signer_user->email;

            $this->sendMail($signer->unique_id,$requestdata->id,$signer_user->email,$type=1);

        }
        
        //ending sending mail to signers

        return response()->json([
           
            'message' => 'Request answered successfully.'
        ], 200);

    }

    public function rejectRequest(Request $request){

        $requestdata = UserRequest::where('unique_id',$request->request_unique_id)->first();
       

        Approver::where('request_id',$requestdata->id)
        ->where('unique_id',$request->approver_unique_id)
        ->update(['status'=>'rejected', 'comment'=>$request->comment]);


        //update status for request
        $approvercheck = Approver::where('request_id',$requestdata->id)
        ->where('status','pending')
        ->first();

        if(!$approvercheck){
            UserRequest::where('unique_id',$request->request_unique_id)->update(['approve_status'=>2]);
        }

        //ending update status for request

        //sending mail to signers 
        $sender = User::where('id',$requestdata->user_id)->first();
        $this->sendMail($sender->unique_id,$requestdata->id,$sender->email,$type=3);
       
        
        //ending sending mail to signers

        return response()->json([
           
            'message' => 'Request answered successfully.'
        ], 200);

    }

    private function storeFile($file, $directory){
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path($directory), $fileName);
        return "$directory/$fileName";
    }

    private function sendMail($signerUId, $requestId, $email, $type){

        $userrequest = UserRequest::where('id', $requestId)->first();
        $requestUid = $userrequest->unique_id;

        \Log::info('request u id '.$requestUid);

        $dataUser = [
            'email'=>$email,
            'signerUID'=>$signerUId,
            'requestUID'=>$requestUid,
         
       ];
        
       if($type == 1){
        \Mail::to($email)->send(new \App\Mail\NewRequest($dataUser));
       }elseif($type==2){
        \Mail::to($email)->send(new \App\Mail\ApproverMail($dataUser));
       }elseif($type==3){
        \Mail::to($email)->send(new \App\Mail\RejectedMail($dataUser));
       }
       

        return "mail sent";

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

    
    
}
