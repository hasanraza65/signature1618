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

    public function __construct($data, $subject, $file = null)
    {
        $this->data = $data;
        $this->subject = $subject;
        $this->file = $file;
    }

    public function build()
    {
        $user_d = $this->data;
        $email = $this->subject($this->subject)
                      ->view('mail_templates.support_mail', compact('user_d'));

        // Attach file if present
        if ($this->file) {
            $email->attach($this->file->getRealPath(), [
                'as' => $this->file->getClientOriginalName(),
                'mime' => $this->file->getMimeType(),
            ]);
        }

        return $email;
    }
}
