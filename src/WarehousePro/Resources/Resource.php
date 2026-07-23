<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\WarehousePro\Resources;

use Ux2Dev\Microinvest\WarehousePro\WarehouseProTransport;

abstract class Resource
{
    /** Page size used by the contract-level each() walkers. */
    protected const EACH_PAGE_SIZE = 100;

    public function __construct(protected readonly WarehouseProTransport $transport)
    {
    }
}
