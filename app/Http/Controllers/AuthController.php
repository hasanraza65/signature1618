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
            $usercheck->password = bcrypt($request->password);
            $usercheck->contact_type = 0;
            $usercheck->update();

            $accessToken = $usercheck->createToken('LaravelAuthApp')->accessToken;
            return response(['user' => $usercheck, 'access_token' => $accessToken]);

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
            return response(["status" => 400, "message" => $validator->errors()->first(), "data" => array()]);
        }
        $input['password'] = bcrypt($request->password);
        $user = User::create($input);
        $accessToken = $user->createToken('LaravelAuthApp')->accessToken;
        return response(['user' => $user, 'access_token' => $accessToken]);
    }

    public function login(Request $request)
    {

        //check user
        $usercheck = User::where('email',$request->email)->first();
        if($usercheck && $usercheck->contact_type == 1){

            return response()->json(['error' => 'Unauthorised'], 401);

        }
        //ending check user

        $loginField = filter_var($request->input('email'), FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $data = [
            $loginField => $request->input('email'),
            'password' => $request->password,
        ];

        if (auth()->attempt($data)) {
            $user = Auth::user();
            $token = $user->createToken('LaravelAuthApp')->accessToken;

            return response()->json([
                'token' => $token,
                'user' => $user,
            ], 200);
        } else {
            return response()->json(['error' => 'Unauthorised'], 401);
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
            return response(["status" => 400, "message" => "Error: No any user available with this email."]); 

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
            return response(["status" => 400, "message" => "Error: No OTP available with this email."]); 
        }

        if($otpdata->otp != $request->otp){
            return response(["status" => 400, "message" => "Error: OTP missmatched."]); 
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