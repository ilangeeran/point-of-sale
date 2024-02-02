<?php

namespace App\Mail;

use App\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;
use Str;


class PasswordResetMail extends Mailable
{
    use Queueable, SerializesModels;

    private $user;
    private $num_token;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $num_token)
    {
        $this->user = $user;
        $this->num_token = $num_token;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $user = $this->user;
        
        // $user->security_code_at = Carbon::now();
        $user->setRememberToken(Str::random(60));
        $user->save();
        
        $hash = $user->getRememberToken();

        return $this->view('emails.password_reset_email')
            ->with('email', $user->email)
            ->with('userid', $user->id)
            ->with('name', $user->name)
            ->with('token', $this->num_token)
            ->with('hash', $hash)
            ->subject("Reset Password")
            ->to($user->email);
    }

}
