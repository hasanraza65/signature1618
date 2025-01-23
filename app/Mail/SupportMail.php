<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $subject;
    public $file;

    public function __construct($data, $subject, $filePath = null)
    {
        $this->data = $data;
        $this->subject = $subject;
        $this->filePath = $filePath;
    }
    
    public function build()
    {
        $user_d = $this->data;
        $email = $this->subject('New Support Ticket - New Support Request '.$user_d['email'])
                      ->view('mail_templates.support_mail', compact('user_d'));
    
        // Attach file if path is provided
        if ($this->filePath) {
            $email->attach(public_path($this->filePath), [
                'as' => basename($this->filePath),
                'mime' => mime_content_type(public_path($this->filePath)),
            ]);
        }
    
        return $email;
    }

}
