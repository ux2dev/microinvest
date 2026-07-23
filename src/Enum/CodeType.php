<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Enum;

/**
 * Whether an additional item code is a plain code or a barcode. micro.bg only.
 */
enum CodeType: int
{
    case Code = 1;
    case Barcode = 2;
}
