<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg\Resources;

use Ux2Dev\Microinvest\Contracts\ItemRepository;
use Ux2Dev\Microinvest\Dto\Input\Items\ItemInput;
use Ux2Dev\Microinvest\Dto\Result\Items\ItemResult;
use Ux2Dev\Microinvest\Dto\Result\Store\StoreResult;
use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Http\ResultList;

final class Items extends Resource implements ItemRepository
{
    /**
     * @param  array<string, mixed>  $filters  raw wire keys, merged over the named arguments
     * @return ResultList<ItemResult>
     */
    public function list(
        ?string $fromDate = null,
        ?int $fromId = null,
        ?int $limit = null,
        ?int $id = null,
        ?string $code = null,
        ?string $barcode = null,
        ?bool $pricesWithVat = null,
        array $filters = [],
    ): ResultList {
        return $this->transport->callList('getItems', array_merge([
            'fromDate' => $fromDate,
            'fromId' => $fromId,
            'limit' => $limit,
            'Id' => $id,
            'Code' => $code,
            'Barcode' => $barcode,
            'PricesWithVat' => $pricesWithVat === null ? null : ($pricesWithVat ? 1 : 0),
        ], $filters), ItemResult::class);
    }

    public function get(int $id): ItemResult
    {
        // micro.bg has no single-record endpoint; Id is a filter on the list.
        $item = $this->list(id: $id)->first();

        if ($item === null) {
            throw new ApiException("Item {$id} was not found", httpStatus: 200);
        }

        return $item;
    }

    /** @see Partners::create() for why AutoGenerateCode defaults to on. */
    public function create(ItemInput $input, bool $autoGenerateCode = true): ItemResult
    {
        return $this->transport->callOne(
            'insertItem',
            ['AutoGenerateCode' => $autoGenerateCode ? 1 : 0],
            $input->toMicroBgArray(),
            ItemResult::class,
        );
    }

    public function update(int $id, ItemInput $input): ItemResult
    {
        $data = $input->toMicroBgArray();
        $data['id'] = $id;

        return $this->transport->callOne('editItem', [], $data, ItemResult::class);
    }

    /**
     * Physical delete when the item is unused, logical otherwise
     * (Deleted becomes 1). micro.bg only.
     */
    public function delete(int $id): void
    {
        $this->transport->call('deleteItem', ['Id' => $id]);
    }

    /**
     * Every item, walking the fromId cursor.
     *
     * @return iterable<ItemResult>
     */
    public function each(): iterable
    {
        $fromId = 0;

        while (true) {
            $batch = $this->list(fromId: $fromId, limit: self::EACH_LIMIT);
            $cursor = $fromId;

            foreach ($batch as $item) {
                yield $item;

                if ($item->id !== null && $item->id > $cursor) {
                    $cursor = $item->id;
                }
            }

            if ($batch->count() < self::EACH_LIMIT || $cursor === $fromId) {
                return;
            }

            $fromId = $cursor;
        }
    }

    /**
     * Stock levels for one object, or across all of them when no object is
     * given. micro.bg only.
     *
     * @param  list<int>|null  $itemIds
     * @return ResultList<StoreResult>
     */
    public function quantities(?int $objectId = null, ?array $itemIds = null): ResultList
    {
        return $this->transport->callList('getItemQuantities', [
            'ObjectId' => $objectId,
            'ItemIds' => $itemIds,
        ], StoreResult::class);
    }

    /**
     * Sale prices for one price group. micro.bg only.
     *
     * @param  list<int>|null  $itemIds
     * @return ResultList<ItemResult>
     */
    public function prices(?int $priceGroup = null, ?bool $pricesWithVat = null, ?array $itemIds = null): ResultList
    {
        return $this->transport->callList('getItemPrices', [
            'PriceGroup' => $priceGroup,
            'PricesWithVat' => $pricesWithVat === null ? null : ($pricesWithVat ? 1 : 0),
            'ItemIds' => $itemIds,
        ], ItemResult::class);
    }
}
