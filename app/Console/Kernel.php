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
            foreach($data as $date){
                $reminderDate = Carbon::parse($date->date);
                \Log::info('reminder date '.$reminderDate);
                \Log::info('today date '.Carbon::today());
                if ($reminderDate->isSameDay(Carbon::today())) {

                    $request_obj = UserRequest::find($date->request_id);
                    $signer_obj = Signer::where('request_id',$request_obj->id)->get();

                    foreach($signer_obj as $signer){

                        $user_obj = User::find($signer->recipient_user_id);

                        $email = $user_obj->email;

                        $dataUser = [
                            'email' => $email,
                            'requestUID'=>$request_obj->unique_id,
                            'signerUID'=>$signer->unique_id,
                            'custom_message'=>$request_obj->custom_message,
                        ];

                        \Mail::to($email)->send(new \App\Mail\ReminderEmail($dataUser));

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
