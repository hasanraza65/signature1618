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
use DB;


class AuthController extends Controller
{


    public function __construct()
    {

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
                    'otp'=>$otpcode
            ];

                $subject = $usercheck->name." your OTP for registration";

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
                'otp'=>$otpcode
        ];

            $subject = $user->name." your OTP for registration";

        Mail::to($user->email)->send(new \App\Mail\OTPEmailSignUp($dataUser, $subject));

        //ending email otp verification sending

        //return response(["status" => 200, 'user' => $user, 'access_token' => $accessToken]);
        return response(["status" => 200, 'user' => $user, 'message' => 'OTP sent to your email id']);
    }

    public function otpVerification(Request $request){

        $otp = $request->otp;
        $user_id = $request->user_id;

        $data = UserOtp::where('user_id',$user_id)->where('otp',$otp)->first();
        if($data){

            $data->delete();

            $user = User::find($user_id);
            $user->is_verified = 1;
            $user->update();

            $accessToken = $user->createToken('LaravelAuthApp')->accessToken;

            return response(["status" => 200, 'user' => $user, 'message' => 'OTP Matched', 'access_token' => $accessToken]);
            

        }

        return response(["message" => "OTP Not Matched"],401);

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
            'otp'=>$otpcode
        ];

        $subject = $user->name." your OTP for registration";

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

            $token = $user->createToken('LaravelAuthApp')->accessToken;

            return response()->json([
                'token' => $token,
                'user' => $user,
            ], 200);
        } else {
            return response()->json(['error' => 'Please check your email & password again'], 401);
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

            $content = 'Your OTP is '.$otp;
            $response = Mail::raw("", function ($message) use ($email,$otp,$content) {
            $body = new \Symfony\Component\Mime\Part\TextPart($content);
            $message->to($email)
                ->subject('Your OTP is '.$otp)
                ->from("noreply@idexchportal.com")
                ->setBody($body);
            });
            //ending sending email

            return response(["status" => 200, "message" => "OTP has been sent"]);
    }

    public function verifyOTP(Request $request){

        $input = $request->all();
        $rules = array(
            'otp' => 'required',
            'email' => 'required',
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return response(["status" => 400, "message" => $validator->errors()->first(), "data" => null]);
        }

        //check otp matching
        $otpdata = ResetOTP::where('email',$request->email)->first();

        if(!$otpdata){
            return response(["status" => 400, "message" => "No OTP available with this email."]); 
        }

        if($otpdata->otp != $request->otp){
            return response(["status" => 400, "message" => "OTP missmatched."]); 
        }

        $otpdata->delete();

        return response(["status" => 200, "message" => "OTP Matched!"]); 
        //ending check otp

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

        $user = User::where('email',$request->email)->first();
        $password = bcrypt($request->password);
        $user->password = $password;
        $user->update();

        return response(["status" => 200, "message" => "Password Has Been Updated!", 'data'=>$user]); 

    }

    public function authPermissions(){

        $role_id = Auth::user()->user_role;

        $data = RolePermission::where('role_id',$role_id)
        ->with('permissionDetail')
        ->get();

        return response(["status" => 200, "data" => $data]); 

    }

   
}