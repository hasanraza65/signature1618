<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Transaction;
use App\Models\PaymentMethod;
use App\Models\Team;
use Auth;
use Mail;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent;


class SubscriptionController extends Controller
{

    protected $stripe;

    public function __construct(StripeClient $stripe)
    {
        $this->stripe = new StripeClient(['api_key' => config('services.stripe.secret')]);
    }


    public function index(){

        if(Auth::user()->user_role == 1){
            $subscription = Subscription::with(['plan','plan.planFeatures','userDetail'])->orderBy('id','desc')->get();
        }else{
            // Retrieve the single subscription with its associated plans and plan features
            $subscription = Subscription::with(['plan', 'plan.planFeatures'])
                ->where('user_id', Auth::user()->id)
                ->orderBy('id', 'desc')
                ->first();

                // Check if the subscription is not null and has a team_id
                if ($subscription) {
                if ($subscription->team_id != null) {
                    $team_data = Team::with(['userDetail', 'memberDetail'])
                        ->where('unique_id', $subscription->team_id)
                        ->whereNot('status', 2)
                        ->first();
                    
                    // Add team data to the subscription object
                    $subscription->team_data = $team_data;
                } else {
                    // Ensure team_data is set to null if there is no team_id
                    $subscription->team_data = null;
                }
            }
            
        }
       
        return response()->json([
            'data' => $subscription,
            'message' => 'Success'
        ],200);


    }

    public function charge(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    
        $token = $request->input('stripeToken');
        $amount = $request->input('amount') * 100;
        $currency = $request->input('currency', 'USD');
    
        // Check if the user has a Stripe customer ID
        if (Auth::user()->stripe_token == null) {
            // Create a new customer on Stripe
            $customer = Customer::create([
                'email' => Auth::user()->email,
                'source' => $token,
            ]);
            $customerid = $customer->id;
            // Update user's stripe_token with the newly created customer id
            $user = Auth::user();
            $user->stripe_token = $customerid;
            $user->save();
        } else {
            // Use existing customer ID if available
            $customerid = Auth::user()->stripe_token;
        }
        
    
        // Create a charge on Stripe
        $paymentMethodId = $request->payment_method_id;
        $customerId = $customerid;
        $intentParams = [
            'amount' => $amount,
            'currency' => $currency,
            'customer' => $customerId,
            'payment_method' => $paymentMethodId,
            'confirm' => true, // Set to true to confirm the Payment Intent immediately
            'return_url' => 'https://app.signature1618.com/'
        ];
        $intent = PaymentIntent::create($intentParams);
        $paymentMethodId = $intent->payment_method;
        $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
        //return $paymentMethod;
        $card = $paymentMethod->card;
        $cardLast4 = $card->last4;
        $cardExpiryMonth = $card->exp_month;
        $cardExpiryYear = $card->exp_year;
        $cardBrand = $card->brand;
        
        // Get the status of the Payment Intent
        $intentStatus = $intent->status;
        
        // Check if the Payment Intent is succeeded
        if ($intentStatus === 'succeeded') {
            
        } else {
            
            $error = $intent->last_payment_error;
            echo "Payment Error: " . $error->message;
        }
        
       // return "chakkkaaa";

        // If it's a new card, add the source
        if ($request->is_new_card == 1) {
            //$chargeParams['source'] = $token;
        }
    
        //$charge = Charge::create($chargeParams);
    
        // Store the transaction details
        $transaction = new Transaction();
        $transaction->user_id = Auth::user()->id;
        $transaction->transaction_id = $intent->id; // Use Payment Intent ID as the transaction ID
        $transaction->card_last_4 = $cardLast4;
        $transaction->card_expiry_month = $cardExpiryMonth;
        $transaction->card_expiry_year = $cardExpiryYear;
        $transaction->card_brand = $cardBrand;
        $transaction->amount = $request->input('amount');
    
        // Add additional transaction details
        $transaction->first_name = $request->input('first_name');
        $transaction->last_name = $request->input('last_name');
        $transaction->email = $request->input('email');
        $transaction->phone = $request->input('phone');
        $transaction->organization = $request->input('organization');
        $transaction->address_1 = $request->input('address_1');
        $transaction->address_2 = $request->input('address_2');
        $transaction->address_3 = $request->input('address_3');
        $transaction->city = $request->input('city');
        $transaction->postal = $request->input('postal');
        $transaction->state = $request->input('state');
        $transaction->country = $request->input('country');
        $transaction->vat_number = $request->input('vat_number');
    
        $transaction->save();
    
        // Create subscription if necessary
        $this->createSubscribe($request->input('amount'), $transaction->id, $request->plan_id, $request->payment_cycle);
    
        // Return success response
        return response()->json([
            'message' => 'Payment was successful',
        ], 200);
    }


    public function createSubscribe($amount, $transaction_id, $plan_id, $payment_cycle){

        $userId = Auth::user()->id;

        $data = Subscription::where('user_id',$userId)->first();
        $plan_data = Plan::find($plan_id);
        
        if(!$data){
            $data = new Subscription();
        }
        
        $data->user_id = $userId;
        $data->plan_id = $plan_id;
        $data->price = $amount;
        $data->payment_cycle = $payment_cycle;
        $data->payment_id = $transaction_id;
        $data->status = 1;

        $days = 30;
        if ($payment_cycle == "monthly") {
            $days = 30;
        } elseif ($payment_cycle == "yearly") {
            $days = 365;
        }

        $today = Carbon::now();
        $expirydate = $today->addDays($days)->toDateString();

        $data->expiry_date = $expirydate;

        if(!$data){
            $data->save();
        }else{
            $data->update();
        }

        //update transaction
        $transaction = Transaction::find($transaction_id);
        $transaction->plan_id = $plan_id;
        $transaction->update();

        //send mail 

        $useremail = Auth::user()->email;
        $subject = 'Subscription Confirmed - Signature1618 ';
        $today = Carbon::now();
        $invitation_date = $data->created_at;
        // Format the date
        $formattedDate = $invitation_date->format('m/d/Y');
        $dataUser = [
            'email' => Auth::user()->email,
            'first_name' => Auth::user()->name,
            'last_name' => Auth::user()->last_name,
            'invitation_date' => $formattedDate,
            'plan_name' => $plan_data->plan_name,
            'amount' => $amount,
            'subscription_period' => $payment_cycle,
            'next_billing_date' => $expirydate,
             
         ];

         Mail::to($useremail)->send(new \App\Mail\MemberRefusedTeam($dataUser, $subject));

        //ending send mail

        return true;


    }

    
    public function cancelSubscription(Request $request){

        $userId = getUserId($request);
        $data = Subscription::where('user_id',$userId)->first();

        if(!$data){
            return response()->json([
                'message' => 'No subscription available.'
            ], 400);
        }

        $data->status = 0;
        $data->update();

        $plan = Subscription::with(['plan','plan.planFeatures'])->where('user_id',Auth::user()->id)->first();

        return response()->json([
            'data' => $plan,
            'message' => 'Subscription has been cancelled. You can use your current plan until '.$data->expiry_date
        ],200);


    }

    public function destroy($id){

        $data = Subscription::find($id);
        if(!$data){
            return response()->json([
                'message' => 'No data available.'
            ], 400);
        }

        $data->delete();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);

    }

    //testing Stripe

    public function createPaymentIntent(Request $request)
    {
        try {
            // Create a customer on Stripe
            $customer = $this->stripe->customers->create([
                'email' => Auth::user()->email, // Assuming email is provided in the request
                // You can include additional customer information here
            ]);

            $user = Auth::user();
            $user->stripe_token = $customer->id;
            $user->update();

            // Create a PaymentIntent with customer ID, amount, and currency
            $paymentIntent = $this->stripe->paymentIntents->create([
                'customer' => $customer->id, // Use the ID of the newly created customer
                'amount' => $request->amount * 100,
                'currency' => 'usd',
                // In the latest version of the API, specifying the `automatic_payment_methods` parameter is optional because Stripe enables its functionality by default.
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            $output = [
                'clientSecret' => $paymentIntent->client_secret,
            ];

            return response()->json($output);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    public function completePayment(){

        return "payment done";

    }
    

    public function confirmPayment($clientSecret)
    {

        // Initialize Stripe with your secret key
        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            // Confirm the payment using the client secret
            $paymentIntent = $this->stripe->paymentIntents->retrieve($clientSecret);

            return $paymentIntent->status;
            
            // Check if the payment was successful
            /*
            if ($paymentIntent->status === 'succeeded') {
                // Payment succeeded, handle it here
                return response()->json(['success' => true, 'message' => 'Payment succeeded']);
            } else {
                // Payment failed or is still processing
                return response()->json(['success' => false, 'message' => 'Payment failed or is still processing']);
            } */
        } catch (ApiErrorException $e) {
            // Handle API errors
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    
    //ending testing stripe

    
    public function customCharge()
    {
        try {
            // Set the Stripe API key
            Stripe::setApiKey(env('STRIPE_SECRET'));

            // Amount to be charged (in cents)
            $amount = 3000 * 100; // Example amount in cents

            // Create a Payment Intent and immediately confirm it
            $paymentIntent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'usd',
                'customer' => 'cus_Q4fFMKEFwWmISu',
                'description' => 'Charge for subscription',
                'payment_method' => 'pm_1PJe5IB0Nlv2z5XgE9XPJ4fO', // Payment method ID
                'confirm' => true, // Set to true to confirm the Payment Intent immediately
                'return_url' => 'https://app.signature1618.com/'
            ]);

            return "Payment intent created and confirmed successfully.";
        } catch (\Exception $e) {
            \Log::error('Stripe Payment Intent Error: ' . $e->getMessage());
            return "Error: " . $e->getMessage();
        }
    }

}
