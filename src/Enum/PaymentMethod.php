<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Enum;

/**
 * How a payment settles. PDF v1.4 (1.5 getPaymentTypes) states every payment
 * type reduces to exactly one of these four.
 */
enum PaymentMethod: int
{
    case Cash = 1;
    case Bank = 2;
    case Card = 3;
    case Voucher = 4;
}
