<?php

namespace App\Mail;

use App\MerchantApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MerchantApplicationStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MerchantApplication $application,
        public ?string $adminMessage = null,
    ) {}

    public function build(): self
    {
        return $this->markdown('emails.merchantApplicationStatus')
            ->subject($this->application->status === MerchantApplication::STATUS_APPROVED
                ? 'Your PahatudFood merchant application is approved'
                : 'Update on your PahatudFood merchant application');
    }
}
