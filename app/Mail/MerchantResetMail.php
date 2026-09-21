<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Partners;
use Str;

class MerchantResetMail extends Mailable
{
    use Queueable, SerializesModels;

    public $company_name;
    public $email;
    public $resetURL;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($token_array)
    {
        $this->company_name = Str::of($token_array['company_name'])->ucfirst();
        $this->email = $token_array['email'];
        $this->resetURL = $token_array['resetURL'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.merchantResetMail')
                        ->from('info@pahatud.com')
                        ->subject('Welcome | Pahatud Food Merchant Dashboard');
    }

    // php artisan make:mail MerchantResetMail --markdown=emails.merchantResetMail

}
