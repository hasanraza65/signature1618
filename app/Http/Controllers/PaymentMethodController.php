<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;
use Stripe\StripeClient;
use Auth;

class PaymentMethodController extends Controller
{
    public function index(){

        $data = PaymentMethod::where('user_id',Auth::user()->id)->get();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);

    }

    public function store(Request $request)
    {
        // Set your Stripe API key
        Stripe::setApiKey(config('services.stripe.secret'));

        // Replace with your payment method id
        $paymentMethodId = $request->payment_method_id;
        $user = Auth::user();

        try {
            // Check if the user has a Stripe customer ID
            if (!$user->stripe_token) {
                // Create a new Stripe customer
                $customer = Customer::create([
                    'email' => $user->email,
                    'name' => $user->name,
                ]);

                // Save the Stripe customer ID to the user
                $user->stripe_token = $customer->id;
                $user->save();
            }

            $customerId = $user->stripe_token;

            // Attach the payment method to the customer
            $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->attach(['customer' => $customerId]);

            // Optionally, set this payment method as the default for the customer
            Customer::update($customerId, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethod->id,
                ],
            ]);

            // Update existing payment methods for the user to be non-default
            PaymentMethod::where('user_id', $user->id)->update(['is_default' => 0]);

            // Save the new payment method details to the database
            $data = new PaymentMethod();
            $data->card_last_4 = $paymentMethod->card->last4;
            $data->card_expiry_month = $paymentMethod->card->exp_month;
            $data->card_expiry_year = $paymentMethod->card->exp_year;
            $data->card_brand = $paymentMethod->card->brand;
            $data->is_default = 1;
            $data->user_id = $user->id;
            $data->stripe_pm_id = $paymentMethod->id; // Use $paymentMethod->id instead of $paymentMethod->payment_method
            $data->save();

            return response()->json(['success' => true, 'message' => 'Payment method attached successfully']);
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }


    public function show($id){

        $data = PaymentMethod::find($id);

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


    public function destroy($id)
    {
        // Set your Stripe API key
        Stripe::setApiKey(config('services.stripe.secret'));

        // Get the payment method id to be deleted
        $paymentMethodId = $id;

        try {
            // Delete the payment method from Stripe
            $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
            $paymentMethod->detach();

            // Delete the payment method from your database
            PaymentMethod::where('user_id', Auth::user()->id)
                ->where('stripe_pm_id', $paymentMethodId)
                ->delete();

            return response()->json(['success' => true, 'message' => 'Payment method deleted successfully']);
        } catch (\Exception $e) {
            // Handle any exceptions
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }


    
}
