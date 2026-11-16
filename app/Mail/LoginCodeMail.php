<?php

namespace App\Mail;

use App\Models\LoginCode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LoginCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $code;
    public $intent;
    public $minutes;
    public $heading;
    public $action;

    public function __construct(string $code, string $intent)
    {
        $this->code = $code;
        $this->intent = $intent;
        $this->minutes = LoginCode::LIFETIME_MINUTES;

        [$this->heading, $this->action] = [
            'register'     => ['Confirm your email', 'finish creating your account'],
            'email_change' => ['Confirm your new email', 'change the email on your account'],
        ][$intent] ?? ['Your login code', 'log in'];
    }

    public function build()
    {
        return $this->subject($this->code . ' is your ' . config('app.name') . ' code')
            ->view('emails.login-code')
            ->text('emails.login-code-text');
    }
}
