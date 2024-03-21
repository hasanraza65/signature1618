<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserRequest;
use App\Models\Signer;
use App\Models\RequestField;
use App\Models\Contact;
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
    public function store(Request $request){
        
        try {
            // Validate incoming request
            $request->validate([
                'file' => 'required|mimes:pdf',
                'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'signerId' => 'array', // Ensure signerId is an array
                'signerId.*' => 'exists:users,id', // Validate each signerId against users table
                'signer_status' => 'array', // Ensure signer_status is an array
            ]);
    
            // Store file
            $filePath = $this->storeFile($request->file('file'), 'files');
            $originalFileName = $request->file('file')->getClientOriginalName();
    
            // Store thumbnail
            $thumbnailPath = $this->storeFile($request->file('thumbnail'), 'thumbnails');
    
            // Create request
            $userRequest = new UserRequest();
            $userRequest->user_id = Auth::id();
            $userRequest->file = $filePath;
            $userRequest->thumbnail = $thumbnailPath;
            $userRequest->unique_id = $request->unique_id;
            $userRequest->file_name = $originalFileName;
            $userRequest->save();
    
            //creating signers
            if ($request->has('recipientId')) {
                $this->createSigners($request->recipientId, $request->signer_status, $userRequest->id, $request->signer_unique_id, $request->type, $request->x, $request->y, $request->height, $request->width, $request->recipientId, $request->question, $request->is_required, $request->page_index);
            }
            //ending creating signers
    
            return response()->json([
                'data' => $userRequest,
                'message' => 'Request created successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create request. ' . $e->getMessage()
            ], 500);
        }
    }
    
    
    
    public function createSigners(array $signerIds, array $signerStatuses, $requestId, array $signerUniqueId, array $type, array $x, array $y, array $height, array $width, array $recipientId, array $question, array $is_required, array $page_index)
    {
        for ($i = 0; $i < count($signerIds); $i++) {
            $userId = Contact::where('unique_id', $recipientId[$i])->first();

            $signer = new Signer();
            $signer->request_id = $requestId;
            $signer->recipient_unique_id = $recipientId[$i];
            $signer->recipient_user_id = $userId->user_id;
            $signer->recipient_contact_id = $userId->id;
            $signer->status = $signerStatuses[$i];
            $signer->unique_id = $signerUniqueId[$i];
            $signer->save();

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

        $data = UserRequest::with(['signers','signers.requestFields'])
            ->where('unique_id',$request->request_unique_id)
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

    public function answerRequest(Request $request){

        $requestdata = UserRequest::where('unique_id',$request->request_unique_id)->first();
        
        if($requestdata) {
        $filePath = $this->storeFile($request->file('signed_file'), 'files');
        $requestdata->signed_file = $filePath;
        $requestdata->status =  'Done';
        $requestdata->update();
        }

        /*
        for($i=0; $i<count($request->field_id); $i++){

            $data = RequestField::find($request->field_id[$i]);
            if($data){
                $data->answer = $request->answer[$i];
                $data->update();
            }
            

            $signer = Signer::find($data->recipientId);
            if($signer){
                $signer->status = "done";
                $signer->update();
            }
            

        } */

        return response()->json([
           
            'message' => 'Request answered successfully.'
        ], 200);

    }

    private function storeFile($file, $directory){
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path($directory), $fileName);
        return "$directory/$fileName";
    }
}
