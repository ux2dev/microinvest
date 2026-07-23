<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Enum;

/**
 * How a cost is spread over a delivery's lines. micro.bg only.
 */
enum CostAllocationMethod: int
{
    case ByValue = 1;
    case ByQuantity = 2;
}
