<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Enum;

/**
 * Which nomenclature tree a group operation targets. micro.bg only.
 */
enum GroupModule: string
{
    case Items = 'Items';
    case Partners = 'Partners';
}
