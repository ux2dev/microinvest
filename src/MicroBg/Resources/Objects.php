<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg\Resources;

use Ux2Dev\Microinvest\Dto\Result\Locations\LocationResult;
use Ux2Dev\Microinvest\Http\ResultList;

/**
 * Objects are the physical places stock lives — warehouses, shops, offices.
 * Warehouse Pro calls the same thing a location.
 */
final class Objects extends Resource
{
    /** @return ResultList<LocationResult> */
    public function list(): ResultList
    {
        return $this->transport->callList('getObjects', [], LocationResult::class);
    }
}
