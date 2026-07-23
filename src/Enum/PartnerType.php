<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Enum;

/**
 * A partner's role. A closed classification shared by both backends.
 */
enum PartnerType: int
{
    case Client = 1;
    case Supplier = 2;
    case Universal = 3;
}
