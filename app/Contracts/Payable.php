<?php

namespace App\Contracts;

use App\Enums\OrderStatus;

/**
 * Something a customer pays for through the payment gateway (vote orders,
 * donations, bookings). Amounts are always read from the server-side record,
 * never from the request.
 */
interface Payable
{
    public function payableAmount(): string;

    public function payableCurrency(): string;

    public function payableEmail(): string;

    public function payableDescription(): string;

    public function payableStatus(): OrderStatus;
}
