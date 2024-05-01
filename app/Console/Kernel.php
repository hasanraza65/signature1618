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

                            \Mail::to($email)->send(new \App\Mail\ReminderEmailApprover($dataUser, $subject));

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

                            \Mail::to($email)->send(new \App\Mail\ReminderEmail($dataUser,$subject));

                        }

                    }

                    

                }

            }
            //ending reminder

        
        })->dailyAt('15:00')->timezone('Europe/Paris'); // Run daily at 3 PM France time
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
