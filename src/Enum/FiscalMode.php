<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Enum;

/**
 * What a payment type prints to the fiscal device. micro.bg only.
 */
enum FiscalMode: int
{
    case FiscalReceipt = 1;
    case NonFiscalReceipt = 2;
    case PrintNothing = 3;
}
