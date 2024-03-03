<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact;
use App\Models\User;
use Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ContactsImport;

class ContactController extends Controller
{
    public function index(){

        if(Auth::user()->user_role == 1){
            $data = Contact::with(['userDetail','contactUserDetail'])->get();
        }else{
            $data = Contact::with(['userDetail','contactUserDetail'])->where('user_id',Auth::user()->id)->get();
        }
       

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);

    }

    public function store(Request $request) {

        // Check if the email is already registered
        $existingUser = User::where('email', $request->email)->first();

        if ($existingUser) {
            $contact_user_id = $existingUser->id;

            $existingContact = Contact::where('contact_user_id', $contact_user_id)->first();
            if ($existingContact) {
                return response()->json([
                    'message' => 'Error: Contact already added in contacts list.'
                ], 400);
            }

        }else{
            // Create a new user
            $user = new User();
            $user->email = $request->email;
            $user->name = $request->first_name.' '.$request->last_name;
            $user->language = $request->language;
            $user->phone = $request->phone;
            $user->contact_type = 1; //if this user only for contact at the moment and not registered officially here
            
            // Generate a random password
            $password = Str::random(12); // You can adjust the length of the password as needed
            $user->password = bcrypt($password);
            $user->save();
            $contact_user_id = $user->id;
        }

        
        // Create contact data for the user
        $data = new Contact();
        $data->user_id = getUserId($request); // Using the helper function
        $data->contact_user_id = $contact_user_id; 
        $data->job_title = $request->job_title;
        $data->company_name = $request->company_name;
        $data->address_line_1 = $request->address_line_1;
        $data->address_line_2 = $request->address_line_2;
        $data->zip_code = $request->zip_code;
        $data->city = $request->city;
        $data->country = $request->country;
        $data->contact_first_name = $request->first_name;
        $data->contact_last_name = $request->last_name;
        $data->contact_phone = $request->phone;
        $data->contact_language = $request->language;
        $data->save();
    
        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);
    }

    public function show(Request $request, $id){

        $userId = getUserId($request);

        // Find the contact by ID and user ID
        $data = Contact::with(['userDetail','contactUserDetail'])->where('id', $id)->where('user_id', $userId)->first();
        if(!$data){
            return response()->json([
                'message' => 'Error: No data available.'
            ], 400);
        }

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function update(Request $request, $id) {

        // Create contact data for the user
        $userId = getUserId($request);

        // Find the contact by ID and user ID
        $data = Contact::where('id', $id)->where('user_id', $userId)->first();

        if(!$data){
            return response()->json([
                'message' => 'Error: No data available.'
            ], 400);
        }


        //checking email user
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser) {
            $contact_user_id = $existingUser->id;
        }else{
            // Create a new user
            $user = new User();
            $user->email = $request->email;
            $user->name = $request->first_name.' '.$request->last_name;
            $user->language = $request->language;
            $user->phone = $request->phone;
            $user->contact_type = 1; //if this user only for contact at the moment and not registered officially here
            
            // Generate a random password
            $password = Str::random(12); // You can adjust the length of the password as needed
            $user->password = bcrypt($password);
            $user->save();
            $contact_user_id = $user->id;
        }
        //ending checking email user

        $data->contact_user_id = $contact_user_id;
        $data->job_title = $request->job_title;
        $data->company_name = $request->company_name;
        $data->address_line_1 = $request->address_line_1;
        $data->address_line_2 = $request->address_line_2;
        $data->zip_code = $request->zip_code;
        $data->city = $request->city;
        $data->country = $request->country;
        $data->contact_first_name = $request->first_name;
        $data->contact_last_name = $request->last_name;
        $data->contact_phone = $request->phone;
        $data->contact_language = $request->language;
        $data->update();
    
        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);
    }

    public function destroy(Request $request,$id){

        $userId = getUserId($request);

        // Find the contact by ID and user ID
        $data = Contact::where('id', $id)->where('user_id', $userId)->first();

        if(!$data){
            return response()->json([
                'message' => 'Error: No data available.'
            ], 400);
        }
        $data->delete();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function bulkImport(Request $request){
        if ($request->hasFile('excel_file')) {
            $file = $request->file('excel_file');
    
            Excel::import(new ContactsImport, $file);
    
            return response()->json([
                'message' => 'Success'
            ], 200);
        }
    
        return response()->json([
            'message' => 'Error: Data not imported'
        ], 401);
    }

    public function bulkDelete(Request $request){

        $userId = getUserId($request);

        for($i=0; $i<count($request->ids); $i++){

            $data = Contact::where('id', $request->ids[$i])
            ->where('user_id', $userId)
            ->first();

            if($data){
                $data->delete();
            }
            
        }

        return response()->json([
            'message' => 'Success'
        ], 200);

    }
    
}
