<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use Str;

class MerchantResetPassword extends Mailable
{
    use Queueable, SerializesModels;

    public $reset_token;
    public $url;
    public $name;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($token_array)
    {
        $this->reset_token = $token_array['token'];
        $this->url = $token_array['url'];
        $this->name = Str::of($token_array['name'])->ucfirst();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
       return $this->markdown('emails.merchantPasswordResetURL')
                    ->from('leslie@pahatud.com')
                    ->subject('Pahatud | Reset Merchant Password');
                    
    }
}
