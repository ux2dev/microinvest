<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Dto\Input\Items\ItemInput;
use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('lists items and converts the vat flag to an int', function () {
    $http = microBgOk([]);

    fakeMicroBg($http)->items()->list(fromId: 100, limit: 50, pricesWithVat: true);

    expect(microBgPayload($http))->toBe([
        'functionName' => 'getItems',
        'parameters' => ['fromId' => 100, 'limit' => 50, 'PricesWithVat' => 1],
        'functionData' => null,
    ]);
});

it('gets one item by id', function () {
    $http = microBgOk([['id' => 8681, 'Name' => 'Водка']]);

    expect(fakeMicroBg($http)->items()->get(8681)->name)->toBe('Водка')
        ->and(microBgPayload($http)['parameters'])->toBe(['Id' => 8681]);
});

it('throws when an item is not found', function () {
    expect(fn () => fakeMicroBg(microBgOk([]))->items()->get(1))
        ->toThrow(ApiException::class, 'Item 1 was not found');
});

it('creates an item', function () {
    $http = microBgOk(['id' => 436758, 'Name' => 'формуляр']);

    fakeMicroBg($http)->items()->create(new ItemInput(name: 'формуляр', taxGroup: 1, measureId: 1));

    expect(microBgPayload($http))->toBe([
        'functionName' => 'insertItem',
        'parameters' => ['AutoGenerateCode' => 1],
        'functionData' => ['Name' => 'формуляр', 'TaxGroup' => 1, 'MeasureId' => 1],
    ]);
});

it('updates an item', function () {
    $http = microBgOk(['id' => 436758]);

    fakeMicroBg($http)->items()->update(436758, new ItemInput(priceOut2: 3.60));

    expect(microBgPayload($http))->toBe([
        'functionName' => 'editItem',
        'parameters' => [],
        'functionData' => ['PriceOut2' => 3.60, 'id' => 436758],
    ]);
});

it('deletes an item', function () {
    $http = microBgOk(null);

    fakeMicroBg($http)->items()->delete(436758);

    expect(microBgPayload($http)['functionName'])->toBe('deleteItem')
        ->and(microBgPayload($http)['parameters'])->toBe(['Id' => 436758]);
});

it('walks items with a fromId cursor', function () {
    $http = microBgOk([['id' => 7], ['id' => 9]]);

    $ids = [];
    foreach (fakeMicroBg($http)->items()->each() as $item) {
        $ids[] = $item->id;
    }

    expect($ids)->toBe([7, 9]);
});

it('advances the cursor across a full page of items', function () {
    $full = array_map(static fn (int $i): array => ['id' => $i], range(1, 100));

    $http = FakeHttpClient::sequence(
        FakeHttpClient::jsonResponse(['status' => 1, 'errors' => [], 'data' => $full]),
        FakeHttpClient::jsonResponse(['status' => 1, 'errors' => [], 'data' => [['id' => 101]]]),
    );

    $ids = [];
    foreach (fakeMicroBg($http)->items()->each() as $item) {
        $ids[] = $item->id;
    }

    expect($ids)->toHaveCount(101)
        ->and($http->received)->toHaveCount(2)
        ->and(microBgPayload($http)['parameters'])->toBe(['fromId' => 100, 'limit' => 100]);
});

it('reads quantities for one object', function () {
    $http = microBgOk([['ItemId' => 8681, 'Qtty' => -62.0]]);

    $rows = fakeMicroBg($http)->items()->quantities(objectId: 1232, itemIds: [8681]);

    expect($rows->first()->goodId)->toBe('8681')
        ->and($rows->first()->qtty)->toBe(-62.0)
        ->and(microBgPayload($http))->toBe([
            'functionName' => 'getItemQuantities',
            'parameters' => ['ObjectId' => 1232, 'ItemIds' => [8681]],
            'functionData' => null,
        ]);
});

it('reads prices for a price group', function () {
    $http = microBgOk([['id' => 8681, 'Code' => 174, 'TaxValue' => 20, 'PriceOut1' => 12.0]]);

    $rows = fakeMicroBg($http)->items()->prices(priceGroup: 2, pricesWithVat: true, itemIds: [8681, 8682]);

    expect($rows->first()->id)->toBe(8681)
        ->and($rows->first()->taxValue)->toBe(20.0)
        ->and(microBgPayload($http)['parameters'])
        ->toBe(['PriceGroup' => 2, 'PricesWithVat' => 1, 'ItemIds' => [8681, 8682]]);
});
