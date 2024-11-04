<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use Auth;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\User;
use App\Models\UserGlobalSetting;

class TeamController extends Controller
{
    public function index()
    {
        if (Auth::user()->user_role == 1) {
            $data = Team::with(['userDetail', 'memberDetail'])
                ->orderBy('id', 'desc')
                ->get();
        } else {
            $team_plan = Subscription::where('user_id', Auth::user()->id)->first();
            $team_data = Team::where('email', Auth::user()->email)
                ->whereNot('status', 2)
                ->first();

            $user_id = $team_data ? $team_data->user_id : Auth::user()->id;

            $data = Team::with(['userDetail', 'memberDetail'])
                ->where('user_id', $user_id)
                ->orderBy('id', 'desc')
                ->get();
        }

        // Always maintain the same structure
        $teamResponse = $data->isEmpty() ? [] : $data;  // Ensure $teamResponse is always an array
        //$userDetail = Auth::user(); // Get userDetail for the authenticated user
        $userDetail = User::find($user_id);

        return response()->json([
            'data' => [
                'teams' => $teamResponse,  // Array of teams or empty array if no teams
                'userDetail' => $userDetail  // Always return userDetail
            ],
            'message' => 'Success'
        ], 200);
    }


    public function store(Request $request)
    {
        // Validate the request data
        $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:teams,email',
            'job_title' => 'nullable|string|max:255',
        ]);

        //check member plan
        $admin_plan = Subscription::where('user_id',Auth::user()->id)->first();
        if (!$admin_plan) {
            return response()->json(['status_code' => 'no_subscription', 'message' => 'No subscription found for the user'], 401);
        }
    
        // Check if admin plan expiry date is greater than or equal to today
        if ($admin_plan->expiry_date < now()) {
            return response()->json(['status_code' => 'subscription_expired', 'message' => 'Subscription has expired'], 401);
        }
    
        // Check if admin plan's plan_id is 4
        if ($admin_plan->plan_id != 4) {
            return response()->json(['status_code' => 'invalid_plan', 'message' => 'Invalid subscription plan'], 401);
        }
        //ending check member plan

        // Check team member limit remaining
        $total_members = Team::where('user_id', Auth::user()->id)->count('id');
        if ($total_members >= 4) {
            return response()->json(['status_code' => 'members_limit_exceed', 'message' => 'You have already added 4 members'], 401);
        }

        // Create new team member
        $data = new Team();
        $data->user_id = Auth::user()->id;
        $data->first_name = $request->first_name;
        $data->last_name = $request->last_name;
        $data->email = $request->email;
        $data->job_title = $request->job_title;
        $data->unique_id = $request->unique_id;
        $data->save();

        $receiver_name = $request->first_name.' '.$request->last_name;

        //send mail
        $this->sendMail($request->email,$request->unique_id, $receiver_name);
        //ending send mail

        return response()->json(['data' => $data, 'message' => 'Team member added successfully'], 201);
    }


    public function update(Request $request, $id){

        $userId = getUserId($request);

        // Find the team member by ID and user ID
        $data = Team::where('id', $id)->where('user_id', $userId)->first();
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        $data->user_id = Auth::user()->id;
        $data->first_name = $request->first_name;
        $data->last_name = $request->last_name;
        $data->job_title = $request->job_title;
        $data->update();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function show(Request $request, $id){

        $userId = getUserId($request);

        // Find the team member by ID and user ID
        $data = Team::with(['userDetail','memberDetail'])->where('id', $id)->where('user_id', $userId)->first();
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

    public function destroy($id){


        // Find the team member by ID and user ID
        $data = Team::where('id', $id)->where('user_id', Auth::user()->id)->first();
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        $data->delete();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    private function sendMail($email,$unique_id,$receiver_name=null) {
        
        $request = request();
        $userName = getUserName($request);

        $today = Carbon::now();
        // Format the date
        $formattedDate = $today->format('m/d/Y');
    
        $dataUser = [
            'email' => $email,
            'senderName' => $userName,
            'unique_id' => $unique_id,
            'receiver_name' => $receiver_name,
            "date"=> $formattedDate
        ];
    
        $subject = 'Team Invitation from '.$userName.' on signature1618';
       
    
        // Append current timestamp to subject
        //$subject .= ' - ' . Carbon::now()->toDateTimeString();
    
        // Send email
        Mail::to($email)->send(new \App\Mail\InvitationEmail($dataUser, $subject));
    
        return "Mail sent";
    }

    public function joinRequests(){

        $user_email = Auth::user()->email;

        $data = Team::with(['userDetail','memberDetail'])
        ->where('email',$user_email)
        ->where('status',0)
        ->orderBy('id','desc')
        ->get();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function leaveTeam(){

        $user_email = Auth::user()->email;
        $data =  Team::where('email',$user_email)->where('status',1)->first();
        if(!$data){
            return response()->json([
                'message' => "You have not joined any team yet."
            ], 400);
        }

        $data->delete();
        //$data->update();

        $userId = Auth::user()->id;

        $subscription = Subscription::where('user_id',Auth::user()->id)->first();
        
        //return $subscription;
    


        $subscription->user_id = $userId;
        $subscription->team_id = null;
        
        if($subscription->old_plan_id != null){
            $subscription->price = $subscription->old_plan_price;
            $subscription->plan_id = $subscription->old_plan_id;
            $subscription->expiry_date = $subscription->old_expiry_date;
            
            if (isset($subscription->old_expiry_date) && Carbon::parse($subscription->old_expiry_date)->isPast()) {
            $subscription->status = 0;
            }else{
                $subscription->status = 1;
            }
        }else{
            $subscription->price = null;
            $subscription->plan_id = null;
            $subscription->expiry_date = null;
        }
        
        $subscription->old_plan_id = null;
        $subscription->old_expiry_date = null;
        $subscription->old_plan_price = null;
        
        
        // Check if old_expiry_date is smaller than today
        
        $subscription->update();

        $plan = Subscription::with(['plan','plan.planFeatures'])->where('user_id',Auth::user()->id)->first();

        return response()->json([
            'data' => $data,
            'plan' => $plan,
            'message' => 'Success'
        ], 200);

    }


    public function joinTeam(Request $request)
    {
        $user_id=Auth::user()->id;
        //return "email ".Auth::user()->email.' '.'id: '.Auth::user()->id;
        if (isset($request->join_team) && !empty($request->join_team)) {

            // Get team admin subscription data
            $team_admin = Team::where('unique_id', $request->join_team)->first();

            if (!$team_admin) {
                return response()->json(['error' => 'Invalid team ID.'], 400);
            }

            $admin_plan = Subscription::where('user_id', $team_admin->user_id)->first();

            if (!$admin_plan) {
                return response()->json(['error' => 'Team admin does not have a subscription.'], 400);
            }

           

            // Check if admin plan expiry date is equal or greater than today
            if ($admin_plan->expiry_date < now()) {
                return response()->json(['error' => 'Team admin subscription has expired.'], 400);
            }

            // Check if the user is already associated with another team
            $existing_team_member = Subscription::where('user_id', $user_id)->whereNotNull('team_id')->first();

            if ($existing_team_member) {
                return response()->json(['error' => 'User is already a member of another team.'], 400);
            }

            $team_admin->status = 1;
            $team_admin->update();

            // Proceed to join the team
            $userId = Auth::user()->id;

            $subscription_data = Subscription::where('user_id',$userId)->first();
            
            if(!$subscription_data){
                $subscription = new Subscription();
            }else{
                $subscription = $subscription_data;
            }

            $subscription->user_id = $userId;
            $subscription->team_id = $request->join_team;
            $subscription->old_plan_id = $subscription_data->plan_id;
            $subscription->old_expiry_date = $subscription_data->expiry_date;
            $subscription->old_plan_price = $subscription_data->price;
            $subscription->price = 0;
            $subscription->plan_id = 3;
            $subscription->expiry_date = $admin_plan->expiry_date;
            if(!$subscription_data){
               
                $subscription->save();
               
            }else{
                $subscription->update();
            }

            $plan = Subscription::with(['plan','plan.planFeatures'])->where('user_id',Auth::user()->id)->first();


            //send mail 
            $admin_user = User::find($team_admin->user_id);

            //get company name 
            $globalsettings = UserGlobalSetting::where('user_id',$team_admin->user_id)->where('meta_key','company')->first();
            if(!$globalsettings){
                $company_name = $admin_user->name.' '.$admin_user->last_name;
            }else{
                $company_name = $globalsettings->meta_value;
            }
            //end get company name

            $userName = getUserName($request);
            
            $useremail = $admin_user->email;
            $subject = $userName.' Has Joined '.$company_name.' on Signature1618 ';
            $today = Carbon::now();
            // Format the date
            $formattedDate = $today->format('m/d/Y');
            $dataUser = [
                'email' => Auth::user()->email,
                'invited_member_name' => $userName,
                'first_name' => $admin_user->name,
                'last_name' => $admin_user->last_name,
                'subject' => $request->subject,
                'company_name' => $company_name,
                'organization_name' => $company_name,
                'joined_date' => $formattedDate,
                'job_title' => $team_admin->job_title,
                
            ];

            Mail::to($useremail)->send(new \App\Mail\MemberJoinedTeam($dataUser, $subject));
            //ending mail
            

            return response()->json(['plan'=>$plan, 'success' => 'User has successfully joined the team.'], 200);
        } else {
            return response()->json(['error' => 'Invalid request.'], 400);
        }
    }

    public function rejectTeam(Request $request){

        $user_email = Auth::user()->email;
        $data =  Team::where('unique_id',$request->join_team)
        ->where('status',0)
        ->first();
        if(!$data){
            return response()->json([
                'message' => "No data found."
            ], 400);
        }

        $data->status = 2;
        $data->update();

        Subscription::where('user_id',Auth::user()->id)->delete();

         //send mail 
         $admin_user = User::find($data->user_id);

         //get company name 
         $globalsettings = UserGlobalSetting::where('user_id',$data->user_id)->where('meta_key','company')->first();
         if(!$globalsettings){
             $company_name = $admin_user->name.' '.$admin_user->last_name;
         }else{
             $company_name = $globalsettings->meta_value;
         }
         //end get company name

         $userName = getUserName($request);
         
         $useremail = $admin_user->email;
         $subject = $userName.' Has Refused To Joined '.$company_name.' on Signature1618 ';
         $today = Carbon::now();
         $invitation_date = $data->created_at;
         // Format the date
         $formattedDate = $invitation_date->format('m/d/Y');
        
         $dataUser = [
             'email' => Auth::user()->email,
             'invited_member_name' => $userName,
             'first_name' => $admin_user->name,
             'last_name' => $admin_user->last_name,
             'subject' => $request->subject,
             'company_name' => $company_name,
             'organization_name' => $company_name,
             'invitation_date' => $formattedDate,
             'job_title' => $data->job_title,
             
         ];

         Mail::to($useremail)->send(new \App\Mail\MemberRefusedTeam($dataUser, $subject));
         //ending mail

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

}
