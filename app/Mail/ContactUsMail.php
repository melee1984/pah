<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Str;

class ContactUsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $email;
    public $subject;
    public $message;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($token_array)
    {
        $this->name = Str::of($token_array['name'])->ucfirst();
        $this->email = $token_array['email'];
        $this->subject = $token_array['subject'];
        $this->message = $token_array['message'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.contactMail')
                        ->from('info@pahatud.com')
                        ->subject('Contact Us | Pahatud Food Delivery Services');
    }

    // php artisan make:mail MerchantResetMail --markdown=emails.merchantResetMail
}
