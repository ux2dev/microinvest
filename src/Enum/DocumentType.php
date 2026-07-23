<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Enum;

/**
 * The kind of a printable document. The backing value is the code both
 * Microinvest backends use on the wire.
 */
enum DocumentType: int
{
    case Invoice = 1;
    case CreditNote = 5;
    case DebitNote = 15;
}
