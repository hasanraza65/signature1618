<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserRequest;
use App\Models\Signer;
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
    
    private function storeFile($file, $directory){
        $fileName = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path($directory), $fileName);
        return "$directory/$fileName";
    }
    
    public function createSigners(array $signerIds, array $signerStatuses, $requestId, array $signerUniqueId, array $type, array $x, array $y, array $height, array $width, array $recipientId, array $question, array $is_required, array $page_index){
        $signers = [];
        $requestFields = [];
    
        foreach ($signerIds as $index => $signerId) {

            //get userid
            $userId = Contact::where('unique_id',$signerId)->first();
            //ending get userid

            $signers[] = [
                'recipient_unique_id' => $signerId,
                'recipient_user_id' => $userId->user_id,
                'recipient_contact_id' => $userId->id,
                'request_id' => $requestId,
                'status' => $signerStatuses[$index],
                'unique_id' => $signerUniqueId[$index]
            ];

            if($is_required[$index] == true){
                $isrequired = 1;
            }else{
                $isrequired = 0;
            }
    
            $requestFields[] = [
                'request_id' => $requestId,
                'type' => $type[$index],
                'x' => $x[$index],
                'y' => $y[$index],
                'height' => $height[$index],
                'width' => $width[$index],
                'recipientId' => $recipientId[$index],
                'page_index' => $page_index[$index],
                'question' => $question[$index],
                'is_required' => $isrequired
            ];
        }
    
        // Insert signers
        DB::table('signers')->insert($signers);
    
        // Insert request fields
        DB::table('request_fields')->insert($requestFields);
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
}
