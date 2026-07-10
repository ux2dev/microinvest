<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Resources;

use Ux2Dev\Microinvest\Dto\Input\Items\ItemInput;
use Ux2Dev\Microinvest\Dto\Result\Items\ItemResult;
use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Http\ResultList;

final class Items extends Resource
{
    /** @return ResultList<ItemResult> */
    public function list(
        ?string $name = null,
        ?string $code = null,
        ?int $groupId = null,
        ?string $barcode1 = null,
        ?int $type = null,
        ?int $page = null,
        ?int $pageSize = null,
        array $filters = [],
    ): ResultList {
        return $this->transport->requestList('GET', '/Items', array_merge([
            'name' => $name,
            'code' => $code,
            'group_id' => $groupId,
            'barcode1' => $barcode1,
            'type' => $type,
            'page' => $page,
            'page_size' => $pageSize,
        ], $filters), ItemResult::class);
    }

    public function get(int $id): ItemResult
    {
        return $this->transport->requestOne('GET', '/Item', ['id' => $id], null, ItemResult::class);
    }

    public function create(ItemInput $input): ItemResult
    {
        return $this->transport->requestOne('POST', '/Item', [], $input->toArray(), ItemResult::class);
    }

    public function update(int $id, ItemInput $input): ItemResult
    {
        return $this->transport->requestOne('PUT', '/Item', ['id' => $id], $input->toArray(), ItemResult::class);
    }

    /** @return ResultList<NomenclatureGroupResult> */
    public function groups(?int $page = null, ?int $pageSize = null): ResultList
    {
        return $this->transport->requestList('GET', '/ItemsGroups', [
            'page' => $page,
            'page_size' => $pageSize,
        ], NomenclatureGroupResult::class);
    }
}
