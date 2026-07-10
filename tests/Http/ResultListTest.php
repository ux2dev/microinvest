<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Http\ResultList;

it('exposes items, paging metadata and helpers', function () {
    $list = new ResultList(['a', 'b', 'c'], currentPage: 2, totalPages: 7);

    expect($list->all())->toBe(['a', 'b', 'c'])
        ->and($list->first())->toBe('a')
        ->and($list->count())->toBe(3)
        ->and(count($list))->toBe(3)
        ->and($list->currentPage)->toBe(2)
        ->and($list->totalPages)->toBe(7)
        ->and(iterator_to_array($list->getIterator()))->toBe(['a', 'b', 'c']);
});

it('returns null from first() when empty', function () {
    $list = new ResultList([]);

    expect($list->first())->toBeNull()
        ->and($list->count())->toBe(0)
        ->and($list->currentPage)->toBeNull()
        ->and($list->totalPages)->toBeNull();
});

it('is iterable via foreach', function () {
    $seen = [];
    foreach (new ResultList([1, 2]) as $value) {
        $seen[] = $value;
    }

    expect($seen)->toBe([1, 2]);
});
