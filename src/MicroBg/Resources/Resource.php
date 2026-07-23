<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg\Resources;

use Ux2Dev\Microinvest\MicroBg\MicroBgTransport;

abstract class Resource
{
    /** Batch size used by the contract-level each() walkers. */
    protected const EACH_LIMIT = 100;

    public function __construct(protected readonly MicroBgTransport $transport)
    {
    }
}
