<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\Plan;
use App\Models\Transaction;
use Auth;
use Mail;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;


class SubscriptionController extends Controller
{

    protected $stripe;

    public function __construct(StripeClient $stripe)
    {
        $this->stripe = new StripeClient(env('STRIPE_SECRET'));
    }

    public function index(){

        if(Auth::user()->user_role == 1){
            $data = Subscription::with(['plan','plan.planFeatures','userDetail'])->orderBy('id','desc')->get();
        }else{
            $data = Subscription::with(['plan','plan.planFeatures'])->where('user_id',Auth::user()->id)->orderBy('id','desc')->get();
        }
       
        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);


    }

    public function store(Request $request){

        $userId = getUserId($request);

        //confirm payment
        $paymentstatus = $this->confirmPayment($request->payment_intent_id);

        if ($paymentstatus != 'succeeded') {
            // Payment succeeded, handle it here
            return response()->json(['success' => false, 'message' => 'Payment failed or is still processing']);
        } 

        //ending confirm payment

        $data = Subscription::where('user_id',$userId)->first();
        
        if(!$data){
            $data = new Subscription();
        }
        
        $data->user_id = $userId;
        $data->plan_id = $request->plan_id;
        $data->price = $request->price;
        $data->payment_cycle = $request->payment_cycle;

        $days = 30;
        if ($request->payment_cycle == "monthly") {
            $days = 30;
        } elseif ($request->payment_cycle == "yearly") {
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

        //adding transaction
        /*
        $transaction = new Transaction();
        $transaction->user_id = Auth::user()->id;
        $transaction->transaction_id = $charge->id;
        $transaction->card_last_4 = $charge->payment_method_details['card']['last4'];
        $transaction->order_id = $request->order_id;
        $transaction->amount = $request->input('amount');
        $transaction->save(); */
        //ending adding transaction

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);


    }

    public function charge(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $token = $request->input('stripeToken');
        $amount = $request->input('amount');
        $amount =  $amount*100;
        $currency = $request->input('currency', 'USD');

        $customer = Customer::create([
            'email' => Auth::user()->email,
            'source' => $token,
        ]);

        $charge = Charge::create([
            'customer' => $customer->id,
            'amount' => $amount,
            'currency' => $currency,
        ]);

        //will create transactions from here

       /*

        $transaction = new Transaction();
        $transaction->user_id = Auth::user()->id;
        $transaction->transaction_id = $charge->id;
        $transaction->card_last_4 = $charge->payment_method_details['card']['last4'];
        $transaction->order_id = $request->order_id;
        $transaction->amount = $request->input('amount');
        $transaction->save(); */
 
        //ending create transactions from here

        

        // Handle successful payment
        //return response()->json($charge);

        return response()->json([
            'message' => 'Payment was successful'
        ],200);

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

        return response()->json([
            'data' => $data,
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
            //$items = $request->input('items', []);

            // Create a PaymentIntent with amount and currency
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $request->amount*100,
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

    public function retreivePayment(Request $request){

        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve('pi_3PE8PLB0Nlv2z5Xg1j3QoUiv');
            
            // Access transaction details
            $transactionId = $paymentIntent->charges->data[0]->id;
            $amount = $paymentIntent->amount;
            $currency = $paymentIntent->currency;
            // Add more properties as needed
        
            // Do something with the transaction details
        } catch (\Stripe\Exception\ApiErrorException $e) {
            // Handle API errors
            echo json_encode(['error' => $e->getMessage()]);
        }

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

}
