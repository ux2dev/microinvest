<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\WarehousePro\Resources;

use Ux2Dev\Microinvest\Contracts\ItemRepository;
use Ux2Dev\Microinvest\Dto\Input\Items\ItemInput;
use Ux2Dev\Microinvest\Dto\Result\Items\ItemResult;
use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Http\ResultList;

final class Items extends Resource implements ItemRepository
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
        return $this->transport->requestOne('POST', '/Item', [], $input->toWarehouseProArray(), ItemResult::class);
    }

    public function update(int $id, ItemInput $input): ItemResult
    {
        return $this->transport->requestOne('PUT', '/Item', ['id' => $id], $input->toWarehouseProArray(), ItemResult::class);
    }

    /**
     * Every item, one page at a time.
     *
     * @return iterable<ItemResult>
     */
    public function each(): iterable
    {
        $page = 1;

        while (true) {
            $result = $this->list(page: $page, pageSize: self::EACH_PAGE_SIZE);

            yield from $result->items;

            $totalPages = $result->totalPages;

            if ($totalPages === null || $page >= $totalPages || $result->count() === 0) {
                return;
            }

            $page++;
        }
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
