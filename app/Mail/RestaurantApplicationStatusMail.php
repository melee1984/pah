<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RestaurantApplicationStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public string $restaurantName,
        public string $statusMessage,
        public ?string $remarks,
        public string $detailsUrl,
    ) {}

    public function build(): self
    {
        return $this->markdown('emails.restaurantApplicationStatus')
            ->subject('Pahatud restaurant application update: '.$this->restaurantName);
    }
}
