<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\PartnerInquiry;

class PartnerMail extends Mailable
{
    use Queueable, SerializesModels;

     public $_partner;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(PartnerInquiry $partner)
    {
        $this->_partner = $partner;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.partnerInquiry')
            ->bcc('lparba@gmail.com')
            ->subject('Pahatud - Partner Merchant Inquiry');
    }
}
