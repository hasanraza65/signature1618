<?php 

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    public function sendSMS($to, $message)
    {
        \Log::Info('send sms service hit');
        $this->client->messages->create(
            
            $to,
            ['from' => config('services.twilio.phone_number'), 'body' => $message]
        );
    }

}