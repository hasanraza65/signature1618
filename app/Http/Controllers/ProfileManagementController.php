<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserGlobalSetting;
use Auth;

class ProfileManagementController extends Controller
{
    public function profileData(){

        $data = User::find(Auth::user()->id)->first();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function updateProfileData(Request $request){
        $data = Auth::user();
        if(!$data){
            return response()->json([
                'message' => 'Error: No data available.'
            ], 400);
        }
    
        if($request->has('name')) {
            $data->name = $request->name;
        }
    
        if($request->has('last_name')) {
            $data->last_name = $request->last_name;
        }
    
        if($request->has('phone')) {
            $data->phone = $request->phone;
        }
    
        if($request->has('language')) {
            $data->language = $request->language;
        }
    
        if($request->has('company')) {
            $data->company = $request->company;
        }

        if($request->has('accept_terms')) {
            $data->accept_terms = $request->accept_terms;
        }

        if($request->has('company_size')) {
            $data->company_size = $request->company_size;
        }
    
        $data->update();
        
        $existingSetting = UserGlobalSetting::where('user_id', $data->id)
                                                     ->where('meta_key', 'company')
                                                     ->first();
                
                if ($existingSetting) {
                    // Update the existing record
                    $existingSetting->meta_value = $request->company;
                    $existingSetting->save();
                } else {
                    // Create a new record
                    $newSetting = new UserGlobalSetting();
                    $newSetting->user_id = $data->id;
                    $newSetting->meta_key = 'company';
                    $newSetting->meta_value = $request->company;
                    $newSetting->save();
                }
    
        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);
    }
    

    public function changeProfileImg(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // If profile_img is null, remove the existing image from DB and storage
        if (!$request->hasFile('profile_img')) {
            if ($user->profile_img) {
                $imagePath = public_path($user->profile_img);
                
                // Delete the file from storage if it exists
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }

                // Remove the profile image path from the database
                $user->profile_img = null;
                $user->save();
            }

            return response()->json([
                'data' => $user,
                'message' => 'Success'
            ], 200);
        }

        // Validate the incoming request
        $request->validate([
            'profile_img' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Store the new profile image
        $image = $request->file('profile_img');
        $imageName = time().'.'.$image->getClientOriginalExtension();
        $image->move(public_path('profile_images'), $imageName);

        // Delete the old image from storage if it exists
        if ($user->profile_img) {
            $oldImagePath = public_path($user->profile_img);
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }

        // Update the new profile image path in DB
        $user->profile_img = 'profile_images/'.$imageName;
        $user->save();

        return response()->json([
            'data' => $user,
            'message' => 'Success'
        ], 200);
    }

    public function changeCompanyName(Request $request){
       

        // Get the authenticated user
        $user = Auth::user();

        // Store the new profile image
        if ($request->company) {
            $company_name = $request->company;
            
            $user->company = $company_name;
            $user->save();


            $existingSetting = UserGlobalSetting::where('user_id', $user->id)
                                                     ->where('meta_key', 'company')
                                                     ->first();
                
                if ($existingSetting) {
                    // Update the existing record
                    $existingSetting->meta_value = $company_name;
                    $existingSetting->save();
                } else {
                    // Create a new record
                    $newSetting = new UserGlobalSetting();
                    $newSetting->user_id = $user->id;
                    $newSetting->meta_key = 'company';
                    $newSetting->meta_value = $company_name;
                    $newSetting->save();
                }

            return response()->json([
                'data' => $user,
                'message' => 'Success'
            ], 200);
        }

        return response()->json(['message' => 'Failed to update data'], 400);

    }

    public function changeFavImg(Request $request)
    {

        // Get the authenticated user
        $user = Auth::user();

        // Store the new profile image
        if ($request->hasFile('fav_img')) {
            $image = $request->file('fav_img');
            $imageName = time().'.'.$image->getClientOriginalExtension();
            $image->move(public_path('fav_imgs'), $imageName);

            $user->fav_img = 'fav_imgs/'.$imageName;
            $user->update();

            return response()->json([
                'data' => $user,
                'message' => 'Success'
            ], 200);
        }else{
            $user->fav_img = null;
            $user->update();
            
            return response()->json([
                'data' => $user,
                'message' => 'Success'
            ], 200);
        }

        return response()->json(['message' => 'Failed to update fav image'], 400);
    }

    public function changeLogoImg(Request $request)
    {
        // Validate the incoming request
       

        // Get the authenticated user
        $user = Auth::user();

        // Store the new profile image
        if ($request->hasFile('company_logo')) {
            $image = $request->file('company_logo');
            $imageName = time().'.'.$image->getClientOriginalExtension();
            $image->move(public_path('company_logos'), $imageName);

            $user->company_logo = 'company_logos/'.$imageName;
            $user->update();

            return response()->json([
                'data' => $user,
                'message' => 'Success'
            ], 200);
        }else{
            $user->company_logo = null;
            $user->update();
            
            return response()->json([
                'data' => $user,
                'message' => 'Success'
            ], 200);
        }

        return response()->json(['message' => 'Failed to update logo image'], 400);
    }


    public function generateSignature($name)
{
    // Calculate the width based on the length of the name
    $textLength = strlen($name) * 15;

    // Add extra width to accommodate the text
    $extraWidth = 20; // Adjust the padding as needed
    $totalWidth = $textLength + $extraWidth;

    // Generate the SVG with dynamic width and main signature text
    $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"$totalWidth\" height=\"80\">
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Great+Vibes');
                    text {
                        font-family: 'Great Vibes', cursive; /* Use 'Great Vibes' font */
                        font-size: 30px; /* Adjust font size as needed */
                        fill: #000; /* Adjust font color as needed */
                        font-weight: 400; /* Adjust font weight (normal) */
                    }
                    .static-text {
                        font-family: Arial, sans-serif; /* Use Arial font for static text */
                        font-size: 12px; /* Adjust font size for static text */
                        fill: gold; /* Set text color to gold */
                        text-anchor: end; /* Align text to the end (right) */
                    }
                </style>
                <text x=\"10\" y=\"35\" textLength=\"$textLength\" font-family=\"'Great Vibes', cursive\">$name</text>
                <text x=\"$totalWidth\" y=\"50\" class=\"static-text\">signature1618</text>
            </svg>";

    // Set the response headers
    $headers = [
        'Content-Type' => 'image/svg+xml',
        'Content-Disposition' => 'attachment; filename="signature.svg"'
    ];

    // Return the SVG content as a response with the appropriate headers
    return response($svg, 200, $headers);
}



}
