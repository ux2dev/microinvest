<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg\Resources;

use Ux2Dev\Microinvest\Dto\Result\VatGroups\VatGroupResult;
use Ux2Dev\Microinvest\Http\ResultList;

final class TaxGroups extends Resource
{
    /** @return ResultList<VatGroupResult> */
    public function list(): ResultList
    {
        return $this->transport->callList('getTaxGroups', [], VatGroupResult::class);
    }
}
