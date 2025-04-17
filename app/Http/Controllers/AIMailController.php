<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\UserRequest;
use App\Models\User;
use App\Models\RequestLog;

class AIMailController extends Controller
{
    public function getUserSuggestions()
    {
        $users = User::where('contact_type', 0)
            ->where('user_role', 2)
            ->latest() // Latest users first
            ->take(15) // Limit to 15 users
            ->get();
    
        $responseData = [];
    
        foreach ($users as $user) {
            $requests = UserRequest::where('user_id', $user->id)->get();
    
            if ($requests->isEmpty()) {
                $activity = 'no_request';
            } else {
                $hasSent = false;
    
                foreach ($requests as $request) {
                    $sentLog = $request->logs()->where('type', 'sent_request')->first();
                    if ($sentLog) {
                        $hasSent = true;
                        break;
                    }
                }
    
                if (!$hasSent) {
                    $activity = 'created_but_not_sent';
                } else {
                    continue; // Skip users who already sent a request
                }
            }
    
            $context = [
                "name" => $user->name,
                "email" => $user->email,
                "activity" => $activity,
            ];
    
            $aiData = $this->getGeminiResponse($context);
    
            $responseData[] = [
                'name' => $user->name,
                'email' => $user->email,
                'activity' => $activity,
                'subject' => $aiData['subject'] ?? 'N/A',
                'message' => $aiData['message'] ?? 'N/A',
            ];
        }
    
        return response()->json([
            'status' => true,
            'count' => count($responseData),
            'users' => $responseData,
        ]);
    }


    private function getGeminiResponse($data)
    {
        $apiKey = "AIzaSyDHsWIji8M9qjvE6UMcWI2UKMMKcyjvgDQ";
        
        if ($data['activity'] === 'no_request') {
            $prompt = "You're helping with onboarding emails. Respond ONLY with a JSON object like this: {\"subject\": \"...\", \"message\": \"...\"}. The user named {$data['name']} has signed up but hasn't created a request yet. Write an email encouraging them to start their first signature request.";
        } elseif ($data['activity'] === 'created_but_not_sent') {
            $prompt = "You're helping with follow-up emails. Respond ONLY with a JSON object like this: {\"subject\": \"...\", \"message\": \"...\"}. The user named {$data['name']} has created a signature request but hasn't sent it yet. Encourage them to complete it.";
        } else {
            $prompt = "You're helping with emails. Respond ONLY with a JSON object like this: {\"subject\": \"...\", \"message\": \"...\"}.";
        }
    
        // Use a Gemini 2 model
        $model = "gemini-2.0-flash"; // This is an example for Gemini 2 Flash
        // You could replace this with another Gemini 2 model, e.g., "gemini-2-pro-latest"
    
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
            'contents' => [[
                'parts' => [['text' => $prompt]]
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json' // Request JSON response
            ]
        ]);
        
        \Log::info('Gemini response', ['response' => $response->json()]);
    
        $result = $response->json();
    
        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
    
        $subject = '';
        $message = '';
    
        if ($text) {
            $json = json_decode($text, true);
        
            if (is_array($json)) {
                $subject = $json['subject'] ?? '';
                $message = $json['message'] ?? '';
            } else {
                // Fallback if Gemini replies in plain text instead of JSON
                $message = $text;
            }
        }
    
        return [
            'subject' => $subject,
            'message' => $message,
        ];
    }

}
