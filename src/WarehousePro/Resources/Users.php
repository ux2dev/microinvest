<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\WarehousePro\Resources;

use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Dto\Result\Users\UserResult;
use Ux2Dev\Microinvest\Http\ResultList;

final class Users extends Resource
{
    /** @return ResultList<UserResult> */
    public function list(
        ?string $name = null,
        ?string $code = null,
        ?int $groupId = null,
        ?int $page = null,
        ?int $pageSize = null,
        array $filters = [],
    ): ResultList {
        return $this->transport->requestList('GET', '/Users', array_merge([
            'name' => $name,
            'code' => $code,
            'group_id' => $groupId,
            'page' => $page,
            'page_size' => $pageSize,
        ], $filters), UserResult::class);
    }

    /** @return ResultList<NomenclatureGroupResult> */
    public function groups(?int $page = null, ?int $pageSize = null): ResultList
    {
        return $this->transport->requestList('GET', '/UsersGroups', [
            'page' => $page,
            'page_size' => $pageSize,
        ], NomenclatureGroupResult::class);
    }
}
