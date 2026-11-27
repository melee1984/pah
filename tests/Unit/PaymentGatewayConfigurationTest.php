<?php

namespace Tests\Unit;

use App\Contracts\PaymentGateway;
use App\Services\QuickPayGateway;
use LogicException;
use Tests\TestCase;

class PaymentGatewayConfigurationTest extends TestCase
{
    public function test_quickpay_is_the_default_payment_gateway(): void
    {
        $this->assertSame('quickpay', config('payment.default'));
        $this->assertInstanceOf(QuickPayGateway::class, app(PaymentGateway::class));
        $this->assertSame('quickpay', app(PaymentGateway::class)->name());
    }

    public function test_an_unsupported_payment_gateway_fails_clearly(): void
    {
        config(['payment.default' => 'missing']);
        app()->forgetInstance(PaymentGateway::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Unsupported payment gateway [missing].');

        app(PaymentGateway::class);
    }
}
