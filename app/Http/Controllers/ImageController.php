<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function show(Request $request, $imageName)
    {
        // Retrieve the authentication token from the request
        $token = $request->query('auth_token');
    
        // Set the request instance on the guard
        Auth::guard('api')->setRequest($request);
    
        // Authenticate the user using the token
        if (!Auth::guard('api')->check() || !Auth::guard('api')->user()->token()->accessToken === $token) {
            abort(403, 'Unauthorized');
        }
    
        // Construct the image path
        $imagePath = public_path('pdf_images/' . $imageName);
    
        // Check if the file exists
        if (File::exists($imagePath)) {
            // Serve the file
            return response()->file($imagePath);
        } else {
            // Image not found, return 404
            abort(404, 'Image not found');
        }
    }
}
