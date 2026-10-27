<?php

namespace Tests\Feature;

use App\Mail\RiderVerificationCodeMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailBrandingTest extends TestCase
{
    public function test_rider_code_emails_use_the_shared_logo_only_header(): void
    {
        Mail::mailer('array')->to('rider@example.com')
            ->send(new RiderVerificationCodeMail('123456', 'activation'));

        $message = Mail::mailer('array')->getSymfonyTransport()->messages()[0]->getOriginalMessage();
        $html = $message->getHtmlBody();

        $this->assertStringContainsString('123456', $html);
        $this->assertStringContainsString('cid:pahatud-logo@pahatud', $html);
        $this->assertMatchesRegularExpression(
            '/<td class="header"[^>]*>\s*<a[^>]*><img[^>]+><\/a>\s*<\/td>/s',
            $html,
        );
        $this->assertCount(1, $message->getAttachments());
        $this->assertSame('pahatud-logo@pahatud', $message->getAttachments()[0]->getContentId());
    }
}
