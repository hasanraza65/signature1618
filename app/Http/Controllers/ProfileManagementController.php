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
        $data->phone = $request->phone;
        $data->language = $request->language;
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
}
