<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Resources;

use Ux2Dev\Microinvest\Dto\Result\Store\StoreResult;
use Ux2Dev\Microinvest\Http\ResultList;

final class Store extends Resource
{
    /** @return ResultList<StoreResult> */
    public function list(
        ?int $objectId = null,
        ?int $page = null,
        ?int $pageSize = null,
        array $filters = [],
    ): ResultList {
        return $this->transport->requestList('GET', '/Store', array_merge([
            'object_id' => $objectId,
            'page' => $page,
            'page_size' => $pageSize,
        ], $filters), StoreResult::class);
    }
}
