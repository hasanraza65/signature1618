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

class TeamController extends Controller
{
    public function index(){

        if(Auth::user()->user_role == 1){
            $data = Team::with(['userDetail','memberDetail'])
            ->orderBy('id','desc')
            ->get();
        }else{
            $data = Team::with(['userDetail','memberDetail'])
            ->where('user_id',Auth::user()->id)
            ->orderBy('id','desc')
            ->get();
        }

        return response()->json([
            'data' => $data,
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

        //send mail
        $this->sendMail($request->email,$request->unique_id);
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

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    private function sendMail($email,$unique_id) {

        $userName = getUserName();

    
        $dataUser = [
            'email' => $email,
            'senderName' => $userName,
            'unique_id' => $unique_id,
            
        ];
    
        $subject = $userName.' invited you to join signature1618';
       
    
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

        $data->status = 0;
        $data->update();

        Subscription::where('user_id',Auth::user()->id)->delete();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

    public function joinTeam(Request $request)
    {
        $user_id=Auth::user()->id;
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
            $subscription = new Subscription();
            $subscription->user_id = $user_id;
            $subscription->plan_id = 3;
            $subscription->team_id = $request->join_team;
            $subscription->price = 0;
            $subscription->expiry_date = $admin_plan->expiry_date;
            $subscription->save();

            return response()->json(['success' => 'User has successfully joined the team.'], 200);
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

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ], 200);

    }

}
