<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserRequest;
use App\Models\Signer;
use Auth;
use DB;

class RequestController extends Controller
{
    public function index(){

        if(Auth::user()->user_role == 1){
            $data = UserRequest::with(['userDetail'])->orderBy('id','desc')->get();
        }else{
            $data = UserRequest::with(['userDetail'])->where('user_id',Auth::user()->id)->orderBy('id','desc')->get();
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
    
            // Store thumbnail
            $thumbnailPath = $this->storeFile($request->file('thumbnail'), 'thumbnails');
    
            // Create request
            $userRequest = new UserRequest();
            $userRequest->user_id = Auth::id();
            $userRequest->file = $filePath;
            $userRequest->thumbnail = $thumbnailPath;
            $userRequest->unique_id = $request->unique_id;
            $userRequest->save();
    
            //creating signers
            if ($request->has('signerId')) {
                $this->createSigners($request->signerId, $request->signer_status, $userRequest->id, $request->signer_unique_id);
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
    
    public function createSigners(array $signerIds, array $signerStatuses, $requestId, array $signerUniqueId){
        $signers = [];
    
        foreach ($signerIds as $index => $signerId) {
            $signers[] = [
                'user_id' => $signerId,
                'request_id' => $requestId,
                'status' => $signerStatuses[$index],
                'unique_id' => $signerUniqueId[$index]
            ];
        }
    
        DB::table('signers')->insert($signers);
    }

    public function fetchReceivedRequest($id){

        $data = Request::where('unique_id',$id)->first();
        
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }
}
