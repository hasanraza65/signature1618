<?php

namespace App\Console;

use App\Models\RequestReminderDate;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Auth;
use DB;
use Carbon\Carbon;
use App\Models\UserRequest;
use App\Models\Signer;
use App\Models\Approver;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\BillingInfo;
use Stripe\Stripe;
use Stripe\Customer;
use Stripe\Charge;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent;


class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {

            //reminder 
            $data = RequestReminderDate::all();
            $subject = "Reminder to sign the document";
            foreach($data as $date){
                $reminderDate = Carbon::parse($date->date);
                \Log::info('reminder date '.$reminderDate);
                \Log::info('today date '.Carbon::today());
                if ($reminderDate->isSameDay(Carbon::today())) {
                    
                    // APPROVER NOTIFICATION
                    $request_obj_approver = UserRequest::where('id',$date->request_id)
                    ->where('approve_status',0)
                    ->where('status','in progress')
                    ->first();

                    if($request_obj_approver){

                        $subject = "Reminder to approve the document";

                        $approver_obj = Approver::where('request_id',$request_obj_approver->id)
                        ->where('status','pending')
                        ->get();

                        foreach($approver_obj as $approver){

                            $user_obj = User::find($approver->recipient_user_id);

                            $email = $user_obj->email;

                            $dataUser = [
                                'email' => $email,
                                'requestUID'=>$request_obj_approver->unique_id,
                                'signerUID'=>$approver->unique_id,
                                'custom_message'=>$request_obj_approver->custom_message,
                            ];

                            //\Mail::to($email)->send(new \App\Mail\ReminderEmailApprover($dataUser, $subject));

                        }
                        
                    }

                    // APPROVER NOTIFICATION ENDING

                    $request_obj = UserRequest::where('id',$date->request_id)
                    ->where('approve_status',1)
                    ->where('status','in progress')
                    ->first();

                    if($request_obj){

                        $signer_obj = Signer::where('request_id',$request_obj->id)
                        ->where('status','pending')
                        ->get();

                        foreach($signer_obj as $signer){

                            $user_obj = User::find($signer->recipient_user_id);

                            $email = $user_obj->email;

                            $dataUser = [
                                'email' => $email,
                                'requestUID'=>$request_obj->unique_id,
                                'signerUID'=>$signer->unique_id,
                                'custom_message'=>$request_obj->custom_message,
                            ];

                            //\Mail::to($email)->send(new \App\Mail\ReminderEmail($dataUser,$subject));

                        }

                    }

                }

            }
            //ending reminder

        
        })->dailyAt('15:00')->timezone('Europe/Paris'); // Run daily at 3 PM France time

        //update subscriptions

        $schedule->call(function () {
            // Logic for the new job
            

            $yesterday = Carbon::yesterday();

            // Update the status of subscriptions that expired yesterday
            $subscriptions = Subscription::whereDate('expiry_date', $yesterday)
                ->update(['status' => 0]);

                $subscriptions = Subscription::whereDate('expiry_date', $yesterday)
                ->get();

                foreach($subscriptions as $sub_data){

                    $userdata = User::find($sub_data->user_id);
                    $email2 = $userdata->email;
                    $subject2 = "Your package has been expired";

                    $dataUser2 = [
                        'email' => $email2,
                    ];

                    //\Mail::to($email2)->send(new \App\Mail\PackageExpiredEmail($dataUser2,$subject2));

                    //charging auto customer
                    try {
                        // Set the Stripe API key
                        Stripe::setApiKey(env('STRIPE_SECRET'));
                        $plan_detail = Plan::where('id',$sub_data->plan_id)->first();
            
                        // Amount to be charged (in cents)
                        if($sub_data->payment_cycle == 'monthly'){
                            $amount = $plan_detail->per_month_charges*100; // Example amount in cents
                        }else{
                            $amount = $plan_detail->per_year_charges*100; // Example amount in cents
                        }

                        //getting payment method
                        $payment_method = PaymentMethod::where('user_id',$sub_data->user_id)->where('is_default',1)->first();
                        //ending getting payment method
                        
                        if($payment_method){

                            //\Log::info('Running the midnight job');

                            // Create a Payment Intent and immediately confirm it
                            $paymentIntent = PaymentIntent::create([
                                'amount' => $amount,
                                'currency' => 'usd',
                                'customer' => $userdata->stripe_token,
                                'description' => 'Charge for subscription',
                                'payment_method' => $payment_method->stripe_pm_id, // Payment method ID
                                'confirm' => true, // Set to true to confirm the Payment Intent immediately
                                'return_url' => 'https://app.signature1618.com/'
                            ]);
                            

                            //\Log::info('payment intent worked');

                            $paymentMethodId = $paymentIntent->payment_method;
                            $paymentMethod = \Stripe\PaymentMethod::retrieve($paymentMethodId);
                            //return $paymentMethod;
                            $card = $paymentMethod->card;
                            $cardLast4 = $card->last4;
                            $cardExpiryMonth = $card->exp_month;
                            $cardExpiryYear = $card->exp_year;
                            $cardBrand = $card->brand;

                            // Store the transaction details
                            $transaction = new Transaction();
                            $transaction->user_id = $sub_data->user_id;
                            $transaction->transaction_id = $paymentIntent->id; // Use Payment Intent ID as the transaction ID
                            $transaction->card_last_4 = $cardLast4;
                            $transaction->card_expiry_month = $cardExpiryMonth;
                            $transaction->card_expiry_year = $cardExpiryYear;
                            $transaction->card_brand = $cardBrand;
                            $transaction->amount = $amount;
                            $transaction->plan_id = $sub_data->plan_id;

                            $billing_info = BillingInfo::where('user_id',$sub_data->user_id)->first();
                        
                            // Add additional transaction details
                            if ($billing_info) {
                                $transaction->first_name = $billing_info->first_name;
                                $transaction->last_name = $billing_info->last_name;
                                $transaction->email = $billing_info->email;
                                $transaction->phone = $billing_info->phone;
                                $transaction->organization = $billing_info->organization;
                                $transaction->address_1 = $billing_info->address_1;
                                $transaction->address_2 = $billing_info->address_2;
                                $transaction->address_3 = $billing_info->address_3;
                                $transaction->city = $billing_info->city;
                                $transaction->postal = $billing_info->postal;
                                $transaction->state = $billing_info->state;
                                $transaction->country = $billing_info->country;
                                $transaction->vat_number = $billing_info->vat_number;
                            }
                        
                            $transaction->save();
                            //ending storing transaction details

                            $update_sub = Subscription::find($sub_data->id);
                            $days = 30;
                            if ($sub_data->payment_cycle == "monthly") {
                                $days = 30;
                            } elseif ($sub_data->payment_cycle == "yearly") {
                                $days = 365;
                            }

                            $today = Carbon::now();
                            $expirydate = $today->addDays($days)->toDateString();

                            $update_sub->expiry_date = $expirydate;
                            $update_sub->status = 1;
                            $update_sub->update();

                            

                        }else{

                            \Mail::to($email2)->send(new \App\Mail\PackageExpiredEmail($dataUser2,$subject2));

                        }
                        
                    } catch (\Exception $e) {

                        \Log::error('Stripe Payment Intent Error: ' . $e->getMessage().' at line '.$e->getLine());
                        //return "Error: " . $e->getMessage();
                    }
                    //ending charging

                }

            // Add your custom logic here
        })->dailyAt('00:00')->timezone('Europe/Paris'); // Run daily at midnight Paris time

        //ending update subscriptions
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
