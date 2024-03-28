<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserRequest;
use App\Models\Signer;
use App\Models\RequestField;
use App\Models\Contact;
use App\Models\User;
use App\Models\RequestOtp;
use Auth;
use DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class RequestController extends Controller
{
    public function index(){

        if(Auth::user()->user_role == 1){
            $data = UserRequest::with(['userDetail','signers','signers.requestFields','signers.signerContactDetail'])->orderBy('id','desc')->get();
        }else{
            $data = UserRequest::with(['userDetail','signers','signers.requestFields','signers.signerContactDetail'])->where('user_id',Auth::user()->id)->orderBy('id','desc')->get();
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
            // Validate incoming request
           
            // Process your data here
            //$requestData = $request->all();
            $rawData = $request->getContent();

            // Decode the JSON data
            $requestData = json_decode($rawData, true);

            $requestData = $requestData['data'];

            // Return the decoded data
            //return $requestData['data']['status'];
            
            //return response()->json($request->all());
            
            // Check if 'status' key is present
            if (!isset($requestData['status'])) {
                throw new \Exception("Status key is missing in the request data.");
            }
    
            // Assuming you need to access specific keys
            $status = $requestData['status'];
            $uniqueId = $requestData['unique_id'];
            $recipients = $requestData['recipients'];
    
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
                
              
    
                // Process recipient data and save to database
                $requestId = UserRequest::where('unique_id', $uniqueId)->first()->id;
    
                $signer = new Signer();
                $signer->request_id = $requestId;
                $signer->recipient_unique_id = $recipientId; // Assuming this is recipientId
                // You may need to adjust the following based on your database structure
                // Assuming you have a mapping between recipientId and user_id and contact_id
                $userId = Contact::where('unique_id', $recipientId)->first();
                $signer->recipient_user_id = $userId->user_id;
                $signer->recipient_contact_id = $userId->id;
                $signer->status = $signerStatus;
                $signer->unique_id = $signerUniqueId;
                $signer->save();
                
                $this->sendMail($signerUniqueId,$requestId,$usermail);
    
                // Assuming you need to iterate over fields for each recipient
                foreach ($fields as $field) {
                    $requestField = new RequestField();
                    $requestField->request_id = $requestId;
                    $requestField->type = $field['type'];
                    $requestField->x = $field['x'];
                    $requestField->y = $field['y'];
                    $requestField->height = $field['height'];
                    $requestField->width = $field['width'];
                    // Assuming these fields are nullable in your database
                    $requestField->question = $field['question'] ?? null;
                    $requestField->is_required = $field['is_required'] == 'true' ? 1 : 0;
                    $requestField->page_index = $field['page_index'];
                    // Assuming recipientId here refers to signer's id, you may need to adjust this
                    $requestField->recipientId = $signer->id;
                    $requestField->save();
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
    
    
    
    public function createSigners(array $signerIds, array $signerStatuses, $requestId, array $signerUniqueId, array $type, array $x, array $y, array $height, array $width, array $recipientId, array $question, array $is_required, array $page_index)
    {
        for ($i = 0; $i < count($signerIds); $i++) {
            $userId = Contact::where('unique_id', $recipientId[$i])->first();
            $contactmaildata = User::find($userId->contact_user_id);
            $usermail = $contactmaildata->email;

            $signer = new Signer();
            $signer->request_id = $requestId;
            $signer->recipient_unique_id = $recipientId[$i];
            $signer->recipient_user_id = $userId->user_id;
            $signer->recipient_contact_id = $userId->id;
            $signer->status = $signerStatuses[$i];
            $signer->unique_id = $signerUniqueId[$i];
            $signer->save();

            //sending mail to signer
            $this->sendMail($signerUniqueId[$i],$requestId,$usermail);
            //ending sending mail to signer

            for ($j = 0; $j < count($x); $j++) {
                $requestField = new RequestField();
                $requestField->request_id = $requestId;
                $requestField->type = $type[$j];
                $requestField->x = $x[$j];
                $requestField->y = $y[$j];
                $requestField->height = $height[$j];
                $requestField->width = $width[$j];
                $requestField->recipientId = $signer->id;
                $requestField->page_index = $page_index[$j];
                $requestField->question = $question[$j];
                $requestField->is_required = $is_required[$j] ? 1 : 0;
                $requestField->save();
            }
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

        $signer = Signer::where('unique_id',$request->recipient_unique_id)
            ->where('request_id',$data->id)
            ->first();

        if($data->email_otp == 1){

            if($signer->otp_verified == 0){
                
                return response()->json([
                    'message' => 'Email OTP required to access this request.'
                ], 401);

            }

        }

        if($data->sms_otp == 1){

            if($signer->otp_verified == 0){
                
                return response()->json([
                    'message' => 'Email OTP required to access this request.'
                ], 401);

            }

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

        }

        return response()->json([
            'message' => 'OTP Sent'
        ], 200);

    }

    public function verifyOTP(Request $request){

        $data = RequestOtp::where('otp',$request->otp)
        ->where('recipient_unique_id',$request->recipient_unique_id)
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


        return response()->json([
            'message' => 'OTP Matched'
        ], 200);
        
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

    private function storeFile($file, $directory){
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path($directory), $fileName);
        return "$directory/$fileName";
    }

    private function sendMail($signerUId, $requestId, $email){

        $userrequest = UserRequest::where('id', $requestId)->first();
        $requestUid = $userrequest->unique_id;

        \Log::info('request u id '.$requestUid);

        $dataUser = [
            'email'=>$email,
            'signerUID'=>$signerUId,
            'requestUID'=>$requestUid,
         
       ];
        

        \Mail::to($email)->send(new \App\Mail\NewRequest($dataUser));

        return "mail sent";

    }
}
