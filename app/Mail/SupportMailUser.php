<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SupportMailUser extends Mailable
{
    use Queueable, SerializesModels;

    public $data;
    public $subject;
    public $filePath;

    public function __construct($data, $subject, $filePath = null)
    {
        $this->data = $data;
        $this->subject = $subject;
        $this->filePath = $filePath;
    }

    public function build()
    {
        $user_d = $this->data;
        $email = $this->subject('Support Ticket Open : '.$this->subject)
                      ->view('mail_templates.support_mail_user', compact('user_d'));

        // Attach file if path is provided and file exists
        if ($this->filePath && file_exists(public_path($this->filePath))) {
            $email->attach(public_path($this->filePath), [
                'as' => basename($this->filePath),
                'mime' => mime_content_type(public_path($this->filePath)),
            ]);
        } else {
            \Log::error('File does not exist or is not readable: ' . public_path($this->filePath));
        }

        return $email;
    }
}
