<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\WarehousePro\Resources;

use Ux2Dev\Microinvest\Dto\Result\VatGroups\VatGroupResult;
use Ux2Dev\Microinvest\Http\ResultList;

final class VatGroups extends Resource
{
    /** @return ResultList<VatGroupResult> */
    public function list(?int $page = null, ?int $pageSize = null, array $filters = []): ResultList
    {
        return $this->transport->requestList('GET', '/VATGroups', array_merge([
            'page' => $page,
            'page_size' => $pageSize,
        ], $filters), VatGroupResult::class);
    }
}
