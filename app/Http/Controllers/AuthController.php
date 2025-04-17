<?php
namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Models\TransactionDetails;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Mail;
use Carbon\Carbon;
use App\Models\ResetOTP;
use App\Models\UserOtp;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Team;
use App\Models\UserGlobalSetting;
use DB;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Passport\Token;
use Google_Client;
use Illuminate\Support\Facades\Http;



class AuthController extends Controller
{


    public function __construct()
    {

    }
    
    public function redirectToLinkedIn()
    {
        try {
            \Log::info('Redirecting to LinkedIn...');
            return Socialite::driver('linkedin-openid')->redirect();
        } catch (\Exception $e) {
            \Log::error('LinkedIn Redirect Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to redirect to LinkedIn.'], 500);
        }
    }

    /*
    public function handleLinkedInCallback()
    {
        try {
            $linkedinUser = Socialite::driver('linkedin-openid')->user();
            
            // Log user details to verify response
            \Log::info('LinkedIn User:', (array) $linkedinUser);
    
            // Normal processing
            return response()->json(['user' => $linkedinUser]);
    
        } catch (\Exception $e) {
            \Log::error('LinkedIn OAuth Error: ' . $e->getMessage());
    
            return response()->json(['error' => 'LinkedIn authentication failed', 'message' => $e->getMessage()], 401);
        }
    } */
    
   

    public function handleLinkedInCallback(Request $request)
{
    try {
        $linkedinUser = Socialite::driver('linkedin-openid')->user();

        $linkedinUserEmail = $linkedinUser->getEmail();
        $linkedinUserName = $linkedinUser->getName();
        $linkedinUserId = $linkedinUser->getId();

        // Split the full name into first name and last name
        $nameParts = explode(' ', $linkedinUserName);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';

        // Check if user already exists
        $user = User::where('email', $linkedinUserEmail)->first();
        
        $fbc = $request->fbc;
        $fbp = $request->fbp;
        $externalId = $request->externalId;

        if (!$user) {
            $uuid = Str::uuid();

            $user = User::create([
                'name' => $firstName,
                'last_name' => $lastName,
                'email' => $linkedinUserEmail,
                'linkedin_id' => $linkedinUserId,
                'unique_id' => $uuid,
                'is_verified' => 1
            ]);

            // Call function to send data to Google Sheet
            $this->sendDataToGoogleSheet($user, $fbc, $fbp, $externalId);

            $this->updateDefaultSettings($user->id);
            Auth::login($user, true);

            $dataUserWelcome = [
                'first_name' => $user->name,
                'last_name' => $user->last_name,
            ];

            $subjectToWelcome = 'Welcome to Signature1618 - Sign and Manage Documents effortlessly!';
            Mail::to($user->email)->send(new \App\Mail\WelcomeEmail($dataUserWelcome, $subjectToWelcome));
        } else {
            Auth::login($user, true);

            if ($user->contact_type == 1) {
                $dataUserWelcome = [
                    'first_name' => $user->name,
                    'last_name' => $user->last_name,
                ];

                $subjectToWelcome = 'Welcome to Signature1618 - Sign and Manage Documents effortlessly!';
                Mail::to($user->email)->send(new \App\Mail\WelcomeEmail($dataUserWelcome, $subjectToWelcome));
                
                
                $this->sendDataToGoogleSheet($user, $fbc, $fbp, $externalId);

                $user->update([
                    'contact_type' => 0,
                    'linkedin_id' => $linkedinUserId,
                    'is_verified' => 1
                ]);
                
            }
        }

        // Check if user has a subscription plan
        $plan = Subscription::with(['plan', 'plan.planFeatures'])->where('user_id', $user->id)->first();
        if (!$plan) {
            $trialPlan = Plan::where('is_trial', 1)->first();
            $planId = $trialPlan ? $trialPlan->id : 1;

            $subscription = new Subscription();
            $subscription->user_id = $user->id;
            $subscription->plan_id = $planId;
            $subscription->price = 0;
            $subscription->expiry_date = Carbon::now()->addDays(14)->toDateString();
            $subscription->save();

            $plan = Subscription::with(['plan', 'plan.planFeatures'])->where('user_id', $user->id)->first();
        }

        // Generate Passport token
        $token = $user->createToken('LaravelAuthApp')->accessToken;

        // Get user global settings
        $userGlobalSettings = UserGlobalSetting::where('user_id', $user->id)->get();
        
        return redirect("https://signature1618.app/signin?token=$token");

        return response()->json([
            'token' => $token,
            'user' => $user,
            'plan' => $plan,
            'user_global_settings' => $userGlobalSettings
        ], 200);

    } catch (\Exception $e) {
        return response()->json(['error' => 'LinkedIn authentication failed'], 401);
    }
    }

    /**
     * Send user data to Google Sheet using API
     */
    private function sendDataToGoogleSheet($user, $fbc=null, $fbp=null, $externalId=null)
{
    $apiUrl = "https://script.google.com/macros/s/AKfycbzpTMnfCUrLEAc9B_WCPcgMY6JP3xSoqtSDtdp9yeOEIHQg24JLegO9iBkHZ685iDzGrw/exec";

    $postData = [
        "id" => $user->id,
        "name" => $user->name,
        "last_name" => $user->last_name,
        "email" => $user->email,
        "username" => $user->username,
        "user_role" => $user->user_role,
        "email_verified_at" => $user->email_verified_at,
        "profile_img" => $user->profile_img,
        "status" => $user->status,
        "phone" => $user->phone,
        "language" => $user->language,
        "company" => $user->company,
        "company_size" => $user->company_size,
        "company_logo" => $user->company_logo,
        "use_company" => $user->use_company,
        "contact_type" => $user->contact_type,
        "stripe_token" => $user->stripe_token,
        "unique_id" => $user->unique_id,
        "is_verified" => $user->is_verified,
        "google_id" => $user->google_id,
        "linkedin_id" => $user->linkedin_id,
        "accept_terms" => $user->accept_terms,
        "fav_img" => $user->fav_img,
        "created_at" => $user->created_at->toIso8601String(),
        "updated_at" => $user->updated_at->toIso8601String(),
        "externalId" => $user->unique_id,
        "fbc" => $fbc,
        'fbp' => $fbp,
        'externalId' => $externalId
    ];

    // Convert data to JSON
    $jsonData = json_encode($postData);

    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Send JSON data
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json', // Set JSON content type
        'Content-Length: ' . strlen($jsonData)
    ]);

    // Execute cURL request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Close cURL session
    curl_close($ch);

    // Log response for debugging
    \Log::info('Google Sheets API Response:', [
        'response' => $response,
        'status_code' => $httpCode
    ]);
}


    
    public function getAuthData(Request $request){
        
        $user = User::find(Auth::user()->id);
        $plan = Subscription::with(['plan', 'plan.planFeatures'])->where('user_id', $user->id)->first();
        $userGlobalSettings = UserGlobalSetting::where('user_id', $user->id)->get();
        
         return response()->json([
                'user' => $user,
                'plan' => $plan,
                'user_global_settings' => $userGlobalSettings
            ], 200);
            
        
    }
    
   

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        $idToken = $request->input('credential');
        $googleClientId = env('GOOGLE_CLIENT_ID');

        $client = new Google_Client(['client_id' => $googleClientId]);
        $payload = $client->verifyIdToken($idToken);

        if (!$payload) {
            return response()->json(['error' => 'Failed to authenticate with Google'], 401);
        }

        $googleUserEmail = $payload['email'];
        $googleUserName = $payload['name']; // This contains the full name
        $googleUserId = $payload['sub'];

        // Split the full name into first name and last name
        $nameParts = explode(' ', $googleUserName);
        $firstName = $nameParts[0]; // First name
        $lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : ''; // Last name (if exists)

        // Check if the user already exists in your database
        $user = User::where('email', $googleUserEmail)->first();
        
        $fbc = $request->fbc;
        $fbp = $request->fbp;
        $externalId = $request->externalId;
    

        if ($user) {
            // If the user already exists, log them in
            Auth::login($user, true);
            
            if($user->contact_type == 1){
                

                //sending welcome mail
                $dataUserWelcome = [
                    'first_name' => $user->name,
                    'last_name' => $user->last_name,
                    
                ];

            
                $subjectToWelcome = 'Welcome to Signature1618 - Sign and Manage Documents effortlessly!';
            
                Mail::to($user->email)->send(new \App\Mail\WelcomeEmail($dataUserWelcome, $subjectToWelcome));
                //ending welcome mail

                $this->sendDataToGoogleSheet($user, $fbc, $fbp, $externalId);
                
                //\Log::info('mail to '.$user->email);
                
                
                
                $update_user = User::where('email', $googleUserEmail)->first();
                $update_user->contact_type = 0;
                $update_user->google_id = $googleUserId;
                $update_user->is_verified = 1;
                $update_user->update();
            }
            
        } else {
            
            $uuid = Str::uuid();
            // If the user doesn't exist, create a new user
            $user = User::create([
                'name' => $firstName,
                'last_name' => $lastName, // Save the last name in the database
                'email' => $googleUserEmail,
                'google_id' => $googleUserId,
                'unique_id' => $uuid,
                'is_verified' => 1
            ]);

            
            Auth::login($user, true);
            
            $this->sendDataToGoogleSheet($user, $fbc, $fbp, $externalId);
            
            //sending welcome mail
            $dataUserWelcome = [
                'first_name' => $user->name,
                'last_name' => $user->last_name,
                
            ];
        
            $subjectToWelcome = 'Welcome to Signature1618 - Sign and Manage Documents effortlessly!';
        
            Mail::to($user->email)->send(new \App\Mail\WelcomeEmail($dataUserWelcome, $subjectToWelcome));
            //ending welcome mail

            //update global settings
            $this->updateDefaultSettings($user->id);
            //ending update global settings
        }

        // Check if the user has a subscription plan
        $plan = Subscription::with(['plan', 'plan.planFeatures'])->where('user_id', $user->id)->first();
        if (!$plan) {
            // Add trial subscription if user doesn't have a plan
            $trialPlan = Plan::where('is_trial', 1)->first();
            $planId = $trialPlan ? $trialPlan->id : 1;

            $subscription = new Subscription();
            $subscription->user_id = $user->id;
            $subscription->plan_id = $planId;
            $subscription->price = 0;

            // Set expiry date
            $expiryDate = Carbon::now()->addDays(14)->toDateString();
            $subscription->expiry_date = $expiryDate;

            $subscription->save();

            $plan = Subscription::with(['plan', 'plan.planFeatures'])->where('user_id', $user->id)->first();
        }

        // Generate Passport token
        $token = $user->createToken('LaravelAuthApp')->accessToken;

        // Get user global settings
        $userGlobalSettings = UserGlobalSetting::where('user_id', $user->id)->get();

        // Return response with token and user details
        return response()->json([
            'token' => $token,
            'user' => $user,
            'plan' => $plan,
            'user_global_settings' => $userGlobalSettings
        ], 200);
    }


    public function showRegisterForm()
    {
        return view('landing.register');
    }

    public function register(Request $request)
    {
        //check user
        $usercheck = User::where('email',$request->email)->first();
        if($usercheck && $usercheck->contact_type == 1){

            $usercheck->name = $request->name;
            $usercheck->last_name = $request->last_name;
            $usercheck->password = bcrypt($request->password);
            $usercheck->contact_type = 0;
            $usercheck->company = $request->company;
            $usercheck->update();

            $accessToken = $usercheck->createToken('LaravelAuthApp')->accessToken;

           

            //sending email otp verification
                $otp = new UserOtp();
                $otp->user_id = $usercheck->id;
                $otpcode = rand(100000, 999999);
                $otp->otp = $otpcode;
                $otp->save();

                $dataUser = [
                    'email'=>$usercheck->email,
                    'otp'=>$otpcode,
                    'user_name'=>$usercheck->name.' '.$usercheck->last_name,
            ];

                $subject = $usercheck->name." Your Signup One-Time Password";

            Mail::to($usercheck->email)->send(new \App\Mail\OTPEmailSignUp($dataUser, $subject));

                //ending email otp verification sending


            //return response(['user' => $usercheck, 'access_token' => $accessToken]);
            return response(['user' => $usercheck, 'message' => 'OTP sent to your email id']);

        }
        
        //ending check user

            $input = $request->all();
            $rules = array(
                'name' => 'required|max:55',
                'email' => 'required|unique:users',
                'password' => 'required'
            );
            $validator = Validator::make($input, $rules);
            if ($validator->fails()) {
                return response(["status" => 400, "message" => $validator->errors()->first(), "data" => array()],400);
            }
            $input['password'] = bcrypt($request->password);
            $user = User::create($input);
            $accessToken = $user->createToken('LaravelAuthApp')->accessToken;

            //sending email otp verification
            $otp = new UserOtp();
            $otp->user_id = $user->id;
            $otpcode = rand(100000, 999999);
            $otp->otp = $otpcode;
            $otp->save();

            $dataUser = [
                'email'=>$user->email,
                'otp'=>$otpcode,
                'user_name'=>$user->name.' '.$user->last_name
        ];

        $subject = $user->name." Your Signup One-Time Password";

        Mail::to($user->email)->send(new \App\Mail\OTPEmailSignUp($dataUser, $subject));

        //ending email otp verification sending

        //update global settings
        $this->updateDefaultSettings($user->id);
        //ending update global settings

        //return response(["status" => 200, 'user' => $user, 'access_token' => $accessToken]);
        return response(["status" => 200, 'user' => $user, 'message' => 'OTP sent to your email id']);
    }

    public function updateDefaultSettings($user_id){

        

        $expiry_date = $this->generateExpiryData();
        UserGlobalSetting::insert([
            [
                'meta_key' => 'use_company',
                'meta_value' => 1,
                'user_id' => $user_id
            ],
            [
                'meta_key' => 'protection',
                'meta_value' => 'numerical',
                'user_id' => $user_id
            ],
            [
                'meta_key' => 'default_reminder',
                'meta_value' => '{"type":"weekly","count":15}',
                'user_id' => $user_id
            ],
            [
                'meta_key' => 'default_expiry',
                'meta_value' => $expiry_date,
                'user_id' => $user_id
            ],
            [
                'meta_key' => 'sign_certificate',
                'meta_value' => 'public',
                'user_id' => $user_id
            ],
            [
                'meta_key' => 'brand_bg_color',
                'meta_value' => '#f8fafc',
                'user_id' => $user_id
            ],
            [
                'meta_key' => 'brand_button_color',
                'meta_value' => '#009c4a',
                'user_id' => $user_id
            ],
            [
                'meta_key' => 'brand_header_color',
                'meta_value' => '#f8fafc',
                'user_id' => $user_id
            ],
            [
                'meta_key' => 'brand_button_text_color',
                'meta_value' => '#fffff',
                'user_id' => $user_id
            ],
            [
                'meta_key' => 'brand_enable',
                'meta_value' => 0,
                'user_id' => $user_id
            ]
           
        ]);

    }



    public function generateExpiryData()
    {
        // Get the current date and add 6 months
        $expiryDate = Carbon::now()->addMonths(6)->toISOString();

        // Prepare the response data in the desired format
        $data = [
            "expiryType" => "Validity",
            "count" => 6,
            "type" => "months",
            "date" => $expiryDate,
        ];

        // Encode the array as a JSON string and return it
        return json_encode($data);
    }

    public function otpVerification(Request $request){

        $otp = $request->otp;
        $user_id = $request->user_id;

        $data = UserOtp::where('user_id',$user_id)->where('otp',$otp)->first();
        if($data){

            //adding trial subscription
            $trialplan = Plan::where('is_trial',1)->first();
            if(!$trialplan){
                $plan_id = 1;
            }else{
                $plan_id = $trialplan->id;
            }

            $subscription = new Subscription();
            $subscription->user_id = $user_id;
            $subscription->plan_id = $plan_id;
            $subscription->price = 0;

            //get expiry date
            $today = Carbon::now();
            $dateAfter14Days = $today->addDays(14)->toDateString();
            //ending get expiry date

            $subscription->expiry_date = $dateAfter14Days;
            $subscription->save();
            //ending adding trial subscription

            $data->delete();

            $user = User::find($user_id);
            $user->is_verified = 1;
            $user->update();

            $accessToken = $user->createToken('LaravelAuthApp')->accessToken;

            //plan detail
            /*
            if(isset($request->join_team) && $request->join_team != null && $request->join_team != ""){
                $team_member = Team::where('unique_id',$request->join_team)->first();
                $team_member->member_user_id = $user_id;
                $team_member->update();
            } */
            $plan = Subscription::with(['plan','plan.planFeatures'])->where('user_id',$user->id)->first();
            if(!$plan){
                //adding trial subscription
                $trialplan = Plan::where('is_trial',1)->first();
                if(!$trialplan){
                    $plan_id = 1;
                }else{
                    $plan_id = $trialplan->id;  
                    
                }

                    $subscription = new Subscription();
                    $subscription->user_id = $user->id;
                    $subscription->plan_id = $plan_id;
                    $subscription->price = 0;

                    //get expiry date
                    $today = Carbon::now();
                    $dateAfter14Days = $today->addDays(14)->toDateString();
                    //ending get expiry date

                    $subscription->expiry_date = $dateAfter14Days;
                    $subscription->save();
                    //ending adding trial subscription

                    $plan = Subscription::with(['plan','plan.planFeatures'])->where('user_id',$user->id)->first();
            }
            //ending plan detail

            //sending welcome mail
            $dataUserWelcome = [
                'first_name' => $user->name,
                'last_name' => $user->last_name,
                
            ];
        
            $subjectToWelcome = 'Welcome to Signature1618 - Sign and Manage Documents effortlessly!';
        
            Mail::to($user->email)->send(new \App\Mail\WelcomeEmail($dataUserWelcome, $subjectToWelcome));
            //ending welcome mail

            return response(["status" => 200, 'user' => $user,'plan'=>$plan, 'message' => 'OTP Matched', 'access_token' => $accessToken]);
            

        }

        return response(["message" => "OTP Not Matched"],401);

    }

    public function registerWithoutOTP(Request $request)
    {
        $input = $request->all();
        
        // Validation rules
        $rules = [
            'name' => 'required|max:55',
            'email' => 'required|unique:users',
            'password' => 'required',
        ];
        
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response(["status" => 400, "message" => $validator->errors()->first(), "data" => []], 400);
        }

        // Hash password and create user
        $input['password'] = bcrypt($request->password);
        $user = User::create($input);
        $accessToken = $user->createToken('LaravelAuthApp')->accessToken;

        // Assign trial subscription
        $trialplan = Plan::where('is_trial', 1)->first();
        $plan_id = $trialplan ? $trialplan->id : 1;

        $subscription = new Subscription();
        $subscription->user_id = $user->id;
        $subscription->plan_id = $plan_id;
        $subscription->price = 0;
        $subscription->expiry_date = Carbon::now()->addDays(14)->toDateString();
        $subscription->save();

        // Retrieve plan details
        $plan = Subscription::with(['plan', 'plan.planFeatures'])->where('user_id', $user->id)->first();

        // Send welcome email
        $dataUserWelcome = [
            'first_name' => $user->name,
            'last_name' => $user->last_name,
        ];
        
        $subjectToWelcome = 'Welcome to Signature1618 - Sign and Manage Documents effortlessly!';
        Mail::to($user->email)->send(new \App\Mail\WelcomeEmail($dataUserWelcome, $subjectToWelcome));

        // Return response
        return response([
            "status" => 200,
            'user' => $user,
            'plan' => $plan,
            'message' => 'Registration successful',
            'access_token' => $accessToken
        ]);
    }


    public function resendOtp(Request $request){

        //$otp = $request->otp;
        $user_id = $request->user_id;
        $user = User::find($user_id);

        $data = UserOtp::where('user_id',$user_id)->first();
        if($data){

            $data->delete();

        }

        //sending email otp verification
        $otp = new UserOtp();
        $otp->user_id = $user->id;
        $otpcode = rand(100000, 999999);
        $otp->otp = $otpcode;
        $otp->save();

        $dataUser = [
            'email'=>$user->email,
            'otp'=>$otpcode,
            'user_name'=>$user->name.' '.$user->last_name
        ];

        $subject = $user->name." Your Signup One-Time Password";

        Mail::to($user->email)->send(new \App\Mail\OTPEmailSignUp($dataUser, $subject));

        //ending email otp verification sending


        return response(["status" => 200, 'user' => $user, 'message' => 'OTP sent to your email id']);

    }

    public function login(Request $request)
    {

        //check user
        $usercheck = User::where('email',$request->email)->first();
        if($usercheck && $usercheck->contact_type == 1){

            return response()->json(['error' => "You don't have any account here"], 401);

        }
        //ending check user

        $loginField = filter_var($request->input('email'), FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $data = [
            $loginField => $request->input('email'),
            'password' => $request->password,
        ];

        if (auth()->attempt($data)) {
            $user = Auth::user();

            if($user->is_verified == 0){
                return response()->json(['error' => "Please verify your account first", 'user_id'=>$user->id], 401);
            }

            $plan = Subscription::with(['plan','plan.planFeatures'])->where('user_id',$user->id)->first();
            if(!$plan){
                //adding trial subscription
                $trialplan = Plan::where('is_trial',1)->first();
                if(!$trialplan){
                    $plan_id = 1;
                }else{
                    $plan_id = $trialplan->id;  
                    
                }

                    $subscription = new Subscription();
                    $subscription->user_id = $user->id;
                    $subscription->plan_id = $plan_id;
                    $subscription->price = 0;

                    //get expiry date
                    $today = Carbon::now();
                    $dateAfter14Days = $today->addDays(14)->toDateString();
                    //ending get expiry date

                    $subscription->expiry_date = $dateAfter14Days;
                    $subscription->save();
                    //ending adding trial subscription

                    $plan = Subscription::with(['plan','plan.planFeatures'])->where('user_id',$user->id)->first();
            }

            

            $token = $user->createToken('LaravelAuthApp')->accessToken;

            $user_global_settings = UserGlobalSetting::where('user_id',$user->id)->get();

            

            return response()->json([
                'token' => $token,
                'user' => $user,
                'plan' => $plan,
                'user_global_settings' => $user_global_settings
            ], 200);
        } else {
            return response()->json(['error' => 'Invalid credentials, kindly verify your email and password.'], 401);
        }
    }

    public function logout()
    {
        $user = Auth::user();

        if ($user) {
            $user->tokens->each(function ($token, $key) {
                $token->delete();
            });
        }

        return response()->json(['message' => 'User has been logged out'], 200);
    }

    public function changePassword(Request $request) 
    {
        // Validate the new password
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        // Get current user
        $user = Auth::user();

        // Check if the old password is correct
        if (!Hash::check($request->old_password, $user->password)) {
            // The passwords do not match
            return response()->json([
                'message' => 'Old password is incorrect'
            ], 400);
        }

        // The passwords match, so update the password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password updated successfully'
        ], 200);
    }

    public function sendForgetMail(Request $request){

        $input = $request->all();
        $rules = array(
            'email' => 'required',
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response(["status" => 400, "message" => $validator->errors()->first(), "data" => null]);
        }

        $user = User::where('email',$request->email)->first();

        if(!$user){
            return response(["status" => 400, "message" => "No any user available with this email."]); 

        }

        $otpdata = ResetOTP::where('email', $request->email)->delete();
    

            ///********** *///////
            //sending email
            ///********** *///////
            $email = $request->email;
    		$otp = random_int(100000, 999999);

            //inserting to otp table
            $otpData = new ResetOTP();
            $otpData->email = $email;
            $otpData->otp = $otp;
            $otpData->save();
            //ending inserting to otp table data

            /*

            $content = 'Your OTP is '.$otp;
            $response = Mail::raw("", function ($message) use ($email,$otp,$content) {
            $body = new \Symfony\Component\Mime\Part\TextPart($content);
            $message->to($email)
                ->subject('Your OTP is '.$otp)
                ->from("help@integrityflowers.co.uk")
                ->setBody($body);
            }); */

            $customerEmail = $email;
            $dataUser = ['otp'=>$otp,
                        'first_name'=>$user->name,
                        'last_name' => $user->last_name
                        ];
            $subject = 'Password Reset OTP - Signature1618';

            Mail::to($customerEmail)
            ->send(new \App\Mail\UserOTPMail($dataUser, $subject));

            //ending sending email

            return response(["status" => 200, "message" => "OTP has been sent"]);
    }

    public function verifyOTP(Request $request)
    {
        $input = $request->all();
        $rules = array(
            'otp' => 'required',
            'email' => 'required',
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response(["status" => 400, "message" => $validator->errors()->first(), "data" => null]);
        }

        // Check OTP matching
        $otpdata = ResetOTP::where('email', $request->email)->first();

        if (!$otpdata) {
            return response(["status" => 400, "message" => "No OTP available with this email."]);
        }

        // Check if the OTP is expired
        $otpCreationTime = Carbon::parse($otpdata->created_at);
        $currentTime = Carbon::now();
        if ($currentTime->diffInMinutes($otpCreationTime) > 5) {
            $otpdata->delete();
            return response(["status" => 400, "message" => "OTP expired."]);
        }

        if ($otpdata->otp != $request->otp) {
            return response(["status" => 400, "message" => "OTP mismatched."]);
        }

        // Mark the OTP as used
        $otpdata->status = 1;
        $otpdata->update();

        return response(["status" => 200, "message" => "OTP Matched!"]);
    }

    public function updatePassword(Request $request){

        $input = $request->all();
        $rules = array(
            'email' => 'required',
            'password' => 'required'
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response(["status" => 400, "message" => $validator->errors()->first(), "data" => null]);
        }

        $otpdata = ResetOTP::where('email', $request->email)->first();

        if($otpdata){
            if($otpdata->status == 0){
                return response(["status" => 400, "message" => "Please verify your OTP first."]);
            }elseif($otpdata->status == 1){
                $otpdata->delete();
            }
        }else{
            return response(["status" => 400, "message" => "Please verify your OTP first."]);
        }
        

        $user = User::where('email',$request->email)->first();
        $password = bcrypt($request->password);
        $user->password = $password;
        $user->update();

        return response(["status" => 200, "message" => "Password Has Been Updated!", 'data'=>$user]); 

    }

    

   
}