<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
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

    public function  updateProfileData(Request $request){

        $data = User::find(Auth::user()->id)->first();

        if(!$data){
            return response()->json([
                'message' => 'Error: No data available.'
            ], 400);
        }

        $data->name = $request->name;
        $data->last_name = $request->last_name;
        $data->phone = $request->phone;
        $data->language = $request->language;
        $data->company = $request->company;
        if ($request->hasFile('company_logo')) {
            $image = $request->file('company_logo');
            $imageName = time().'.'.$image->getClientOriginalExtension();
            $image->move(public_path('company_logo'), $imageName);

            $data->company_logo = 'company_logos/'.$imageName;

           
        }
        $data->update();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function changeProfileImg(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'profile_img' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjust max file size as per your requirement
        ]);

        // Get the authenticated user
        $user = Auth::user();

        // Store the new profile image
        if ($request->hasFile('profile_img')) {
            $image = $request->file('profile_img');
            $imageName = time().'.'.$image->getClientOriginalExtension();
            $image->move(public_path('profile_images'), $imageName);

           
            $user->profile_img = 'profile_images/'.$imageName;
            $user->save();

            return response()->json([
                'data' => $user,
                'message' => 'Success'
            ], 200);
        }

        return response()->json(['message' => 'Failed to update profile image'], 400);
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
