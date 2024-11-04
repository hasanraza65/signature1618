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
    public function index()
    {
        if (Auth::user()->user_role == 1) {
            $data = Contact::with(['userDetail', 'contactUserDetail'])
                ->where('is_deleted', 0)
                ->whereHas('contactUserDetail') // Ensure contactUserDetail is not null (exists in users table)
                ->orderBy('id', 'desc')
                ->get();
        } else {
            $data = Contact::with(['userDetail', 'contactUserDetail'])
                ->where('user_id', Auth::user()->id)
                ->where('is_deleted', 0)
                ->whereHas('contactUserDetail') // Ensure contactUserDetail is not null (exists in users table)
                ->orderBy('id', 'desc')
                ->get();
        }
    
        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);
    }


    public function store(Request $request) {

        // Validate the request data
        $request->validate([
            'first_name' => 'required|string|max:15',  
            'last_name' => 'required|string|max:15',   
            'email' => 'required|email',               
            'phone' => 'nullable|string',              
            'job_title' => 'nullable|string',
            'company_name' => 'nullable|string',
            'address_line_1' => 'nullable|string',
            'address_line_2' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'user_unique_id' => 'nullable|string',
            'contact_unique_id' => 'nullable|string',
        ]);
    
        // Check if the email is already registered
        $existingUser = User::where('email', $request->email)->first();
    
        if ($existingUser) {
            $contact_user_id = $existingUser->id;
    
            $existingContact = Contact::where('contact_user_id', $contact_user_id)
                ->where('user_id', Auth::user()->id)
                ->where('is_deleted', 0)
                ->first();
    
            if ($existingContact) {
                return response()->json([
                    'message' => 'Contact already in contacts list.'
                ], 400);
            }
    
        } else {
            // Create a new user
            $user = new User();
            $user->email = $request->email;
            $user->name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->language = $request->language ? $request->language : "en";
            $user->phone = $request->phone;
            $user->unique_id = $request->user_unique_id;
            $user->contact_type = 1; // if this user only for contact at the moment and not registered officially here
    
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
        $data->contact_language = $request->language ? $request->language : "en";
        $data->unique_id = $request->contact_unique_id;
        $data->save();
    
        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);
    }
    

    public function show(Request $request, $id){

        $userId = getUserId($request);

        // Find the contact by ID and user ID
        $data = Contact::with(['userDetail','contactUserDetail'])
        ->where('unique_id', $id)
        ->where('user_id', $userId)
        ->where('is_deleted',0)
        ->first();
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

    public function update(Request $request, $id) {

        // Validate the request data
        $request->validate([
            'first_name' => 'required|string|max:15',  // First name must be a string with max 15 characters
            'last_name' => 'required|string|max:15',   // Last name must be a string with max 15 characters
            'email' => 'required|email',               // Validate email format
            'phone' => 'nullable|string',              // Validate phone if provided
            'job_title' => 'nullable|string',
            'company_name' => 'nullable|string',
            'address_line_1' => 'nullable|string',
            'address_line_2' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'city' => 'nullable|string',
            'country' => 'nullable|string',
            'language' => 'nullable|string',
        ]);
    
        // Create contact data for the user
        $userId = getUserId($request);
    
        // Find the contact by ID and user ID
        $data = Contact::where('unique_id', $id)->where('user_id', $userId)->where('is_deleted', 0)->first();
    
        if (!$data) {
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }
    
        // Check if the email is already associated with a user
        $existingUser = User::where('email', $request->email)->first();
        if ($existingUser) {
            $contact_user_id = $existingUser->id;
        } else {
            // Create a new user
            $user = new User();
            $user->email = $request->email;
            $user->name = $request->first_name . ' ' . $request->last_name;
            $user->language = $request->language;
            $user->phone = $request->phone;
            $user->contact_type = 1; // if this user is only for contact at the moment and not registered officially here
            
            // Generate a random password
            $password = Str::random(12); // Adjust the length as needed
            $user->password = bcrypt($password);
            $user->save();
            $contact_user_id = $user->id;
        }
    
        // Update contact data for the user
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

    public function updatePhones(Request $request){

        for($i=0; $i<count($request->contact_unique_id); $i++){

            $data = Contact::where('unique_id',$request->contact_unique_id[$i])->first();
            
            if(!$data){
                return response()->json([
                    'message' => 'No data available.'
                ], 400);
            }

            $data->contact_phone = $request->contact_phone[$i];
            $data->update();

        }

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function destroy(Request $request,$id){

        $userId = getUserId($request);

        // Find the contact by ID and user ID
        $data = Contact::where('id', $id)->where('user_id', $userId)->where('is_deleted',0)->first();

        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }
        $data->update(['is_deleted'=>1]);

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function bulkImport(Request $request){
        if ($request->hasFile('excel_file')) {
            $file = $request->file('excel_file');
            $extension = $file->getClientOriginalExtension();
    
            // Validate file extension
            if (!in_array($extension, ['xlsx', 'xls', 'csv'])) {
                return response()->json([
                    'message' => 'Invalid file type. Supported file types are xlsx, xls, csv.',
                ], 400);
            }
    
            // Check if the header row is correct
            $headerRow = Excel::toArray(new ContactsImport, $file)[0][0];
    
            $expectedHeader = [
                'First name',
                'Last name',
                'Email address',
                'Phone number',
                'Company',
                'Job title'
            ];
    
            if ($headerRow !== $expectedHeader) {
                return response()->json([
                    'message' => 'Invalid header row. Make sure the file has the correct columns.',
                ], 400);
            }
    
            // Validate each row (excluding the header row)
            $rows = Excel::toArray(new ContactsImport, $file)[0];
            $invalidCells = [];
            for ($rowIndex = 1; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[$rowIndex];
                $email = $row[2] ?? null; // Email address is in the third column (index 2)
                $phone = $row[3] ?? null; // Phone number is in the fourth column (index 3)
    
                // Validate email address
                $emailValidator = \Illuminate\Support\Facades\Validator::make(['Email address' => $email], [
                    'Email address' => 'nullable|email',
                ]);
                if ($email && $emailValidator->fails()) {
                    $invalidCells[] = [
                        'row' => $rowIndex + 1,
                        'column' => 'C', // Email address column
                    ];
                }
    
                // Custom phone number validation
                if ($phone && !$this->validatePhoneNumber($phone)) {
                    $invalidCells[] = [
                        'row' => $rowIndex + 1,
                        'column' => 'D', // Phone number column
                    ];
                }
            }
    
            if (!empty($invalidCells)) {
                return response()->json([
                    'message' => 'Some cells contain invalid data.',
                    'invalid_cells' => $invalidCells,
                ], 400);
            }
    
            // If validation passes, import the file
            Excel::import(new ContactsImport, $file);
    
            return response()->json([
                'message' => 'Success',
            ], 200);
        }
    
        return response()->json([
            'message' => 'Data not imported',
        ], 401);
    }
    
    private function validatePhoneNumber($phoneNumber)
    {
        // Regex pattern to validate a US phone number without country code
        $pattern = '/^\d{11}$/';
        return preg_match($pattern, $phoneNumber);
    }

    
    
    
    public function bulkDelete(Request $request){

        $userId = getUserId($request);

        for($i=0; $i<count($request->ids); $i++){

            $data = Contact::where('id', $request->ids[$i])
            ->where('user_id', $userId)
            ->first();

            if($data){
                $data->is_deleted = 1;
                $data->update();
            }
            
        }

        return response()->json([
            'message' => 'Success'
        ], 200);

    }
    
}
