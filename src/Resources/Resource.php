<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Resources;

use Ux2Dev\Microinvest\Http\MicroinvestTransport;

abstract class Resource
{
    public function __construct(protected readonly MicroinvestTransport $transport)
    {
    }
}
