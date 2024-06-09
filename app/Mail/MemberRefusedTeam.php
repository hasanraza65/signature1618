<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberRefusedTeam extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $subject;
   

    public function __construct($data, $subject)
    {
        $this->data = $data;
        $this->subject = $subject;
        
    }

    public function build()
    {
        $user_d = $this->data;
        $email = $this->subject($this->subject)
                      ->view('mail_templates.member_refused_sender', compact('user_d'));

        return $email;
    }
}
