<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Http;

use ArrayIterator;
use Countable;
use IteratorAggregate;

/**
 * Return type for collection endpoints. Wraps the typed result rows plus the
 * paging metadata Microinvest reports in the `X-CurrentPage` / `X-TotalPages`
 * response headers.
 *
 * @template T
 * @implements IteratorAggregate<int, T>
 */
final class ResultList implements IteratorAggregate, Countable
{
    /** @param list<T> $items */
    public function __construct(
        public readonly array $items,
        public readonly ?int $currentPage = null,
        public readonly ?int $totalPages = null,
    ) {
    }

    /** @return list<T> */
    public function all(): array
    {
        return $this->items;
    }

    /** @return T|null */
    public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /** @return ArrayIterator<int, T> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}
