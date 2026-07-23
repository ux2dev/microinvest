<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Enum;

/**
 * The kind of an operation. The backing value is the code both Microinvest
 * backends use on the wire (Warehouse Pro's `operation_type`, micro.bg's
 * `OperType`).
 */
enum OperationType: int
{
    case Delivery = 1;
    case Sale = 2;
    case WriteOff = 11;
    case DeliveryRequest = 12;
    case Order = 19;
    case DebitNote = 26;
    case CreditNote = 27;
    case CustomerClaim = 34;
    case SupplierClaim = 39;
}
