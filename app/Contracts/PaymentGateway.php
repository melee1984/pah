<?php

namespace App\Contracts;

use App\QuickPayCheckout;

interface PaymentGateway
{
    public function name(): string;

    public function availableFor(int $partnerId): bool;

    public function mode(int $partnerId): bool;

    public function refresh(QuickPayCheckout $checkout): void;
}
