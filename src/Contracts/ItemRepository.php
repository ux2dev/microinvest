<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Contracts;

use Ux2Dev\Microinvest\Dto\Input\Items\ItemInput;
use Ux2Dev\Microinvest\Dto\Result\Items\ItemResult;

/**
 * The item operations both backends support. Backend-specific filtering and
 * paging stay on the concrete resource classes.
 */
interface ItemRepository
{
    public function get(int $id): ItemResult;

    public function create(ItemInput $input): ItemResult;

    public function update(int $id, ItemInput $input): ItemResult;

    /**
     * Every item, transparently walking whatever paging model the backend
     * uses. Lazy: rows are fetched one page at a time.
     *
     * @return iterable<ItemResult>
     */
    public function each(): iterable;
}
