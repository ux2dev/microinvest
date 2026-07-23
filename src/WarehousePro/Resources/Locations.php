<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\WarehousePro\Resources;

use Ux2Dev\Microinvest\Dto\Result\Locations\LocationResult;
use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Http\ResultList;

final class Locations extends Resource
{
    /** @return ResultList<LocationResult> */
    public function list(
        ?string $name = null,
        ?string $code = null,
        ?int $groupId = null,
        ?int $page = null,
        ?int $pageSize = null,
        array $filters = [],
    ): ResultList {
        return $this->transport->requestList('GET', '/Locations', array_merge([
            'name' => $name,
            'code' => $code,
            'group_id' => $groupId,
            'page' => $page,
            'page_size' => $pageSize,
        ], $filters), LocationResult::class);
    }

    /** @return ResultList<NomenclatureGroupResult> */
    public function groups(?int $page = null, ?int $pageSize = null): ResultList
    {
        return $this->transport->requestList('GET', '/LocationsGroups', [
            'page' => $page,
            'page_size' => $pageSize,
        ], NomenclatureGroupResult::class);
    }
}
