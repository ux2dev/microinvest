<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Enum;

/**
 * The direction an item line moves stock.
 */
enum StockSign: int
{
    case Out = -1;
    case In = 1;
}
