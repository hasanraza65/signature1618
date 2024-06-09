<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subscription;
use App\Models\Plan;
use Illuminate\Support\Facades\Validator;
use Mail;
use Carbon\Carbon;

class UserManagementController extends Controller
{
    public function index(){

        $data = User::with(['subscriptionDetail','subscriptionDetail.plan'])->whereNot('user_role',1)->get();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);

    }

    public function store(Request $request){

        $rules = [
            'name' => 'required|max:55',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'last_name' => 'sometimes|max:55',    // Optional last name with max length validation
        ];
    
        $validator = Validator::make($request->all(), $rules);
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 400,
                'message' => $validator->errors()->first(),
                'data' => []
            ], 400);
        }

        $data = new User();
        $data = $request->all();
        $data['password'] = bcrypt($request->password);
        $user = User::create($data);

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

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);

    }

    public function show($id){

        $data = User::with(['subscriptionDetail','subscriptionDetail.plan'])->find($id);
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);

    }

    public function update(Request $request, $id){

        $data = User::with(['subscriptionDetail','subscriptionDetail.plan'])->find($id);
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        $data->name = $request->name;
        if(isset($request->password)){
            $data->password = bcrypt($request->password);
        }
        $data->phone = $request->phone;
        $data->save();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);

    }

    public function destroy($id){

        $data = User::find($id);
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        Subscription::where('user_id',$id)->delete();

        $data->delete();

        return response()->json([
            'message' => 'Success'
        ],200);

    }

    public function changePlan(Request $request){

        $plan = Plan::find($request->plan_id);
        
        $subscription = Subscription::where('user_id',$request->user_id)->first();

        if(!$subscription){

            $newsubscription = new Subscription();
            $newsubscription->plan_id = $request->plan_id;
            $newsubscription->payment_cycle = $request->payment_cycle;
            $newsubscription->status = 1;
            $days = 30;
            $amount = $plan->per_month_charges;
            $payment_cycle = $request->payment_cycle;
            if ($payment_cycle == "monthly") {

                $days = 30;
                $amount = $plan->per_month_charges;

            } elseif ($payment_cycle == "yearly") {

                $days = 365;
                $amount = $plan->per_year_charges;

            }

            $today = Carbon::now();
            $expirydate = $today->addDays($days)->toDateString();

            $newsubscription->expiry_date = $expirydate;
            $newsubscription->price = $amount;
            $newsubscription->save();

        }else{

            $subscription->plan_id = $request->plan_id;
            $subscription->payment_cycle = $request->payment_cycle;
            $subscription->status = 1;
            $days = 30;
            $payment_cycle = $request->payment_cycle;
            $amount = $plan->per_month_charges;
            if ($payment_cycle == "monthly") {
                $days = 30;
                $amount = $plan->per_month_charges;
            } elseif ($payment_cycle == "yearly") {
                $amount = $plan->per_year_charges;
                $days = 365;
            }

            $today = Carbon::now();
            $expirydate = $today->addDays($days)->toDateString();

            $subscription->expiry_date = $expirydate;
            $subscription->price = $amount;
            $subscription->update();
        }

        return response()->json([
            'message' => 'Success'
        ],200);

    }
}
