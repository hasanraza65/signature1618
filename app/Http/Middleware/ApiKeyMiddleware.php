<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $apiKey = $request->header('x-api-key');
        $apiSecret = $request->header('x-api-secret');
    
        if (!$apiKey || !$apiSecret) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    
        // 1️⃣ Find user by API key first (fast lookup)
        $user = User::where('api_key', $apiKey)->first();

        $request->merge(['api_user' => $user]);
    
        if (!$user) {
            return response()->json(['message' => 'Invalid API key'], 403);
        }
    
        // 2️⃣ Verify secret using Hash::check()
        if (!Hash::check($apiSecret, $user->api_secret)) {
            return response()->json(['message' => 'Invalid API secret'], 403);
        }
    
        // attach user
        $request->merge(['api_user' => $user]);
    
        return $next($request);
    }
}
