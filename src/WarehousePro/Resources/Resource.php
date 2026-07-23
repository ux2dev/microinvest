<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\WarehousePro\Resources;

use Ux2Dev\Microinvest\WarehousePro\WarehouseProTransport;

abstract class Resource
{
    public function __construct(protected readonly WarehouseProTransport $transport)
    {
    }
}
