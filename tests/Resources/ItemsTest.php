<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Dto\Input\Items\ItemInput;
use Ux2Dev\Microinvest\Dto\Result\Items\ItemResult;
use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('lists items with filters and paging', function () {
    $http = FakeHttpClient::withJson(
        [['id' => 1, 'name' => 'Cola', 'price_out1' => '1.80', 'deleted' => false]],
        headers: ['X-CurrentPage' => '1', 'X-TotalPages' => '5'],
    );

    $list = fakeMicroinvest($http)->items()->list(name: 'Cola*', groupId: 8, pageSize: 200);

    $uri = (string) $http->lastRequest()->getUri();

    expect($http->lastRequest()->getMethod())->toBe('GET')
        ->and($uri)->toContain('/Items?')
        ->and($uri)->toContain('name=Cola%2A')
        ->and($uri)->toContain('group_id=8')
        ->and($uri)->toContain('page_size=200')
        ->and($list->totalPages)->toBe(5)
        ->and($list->first())->toBeInstanceOf(ItemResult::class)
        ->and($list->first()->priceOut1)->toBe(1.8)
        ->and($list->first()->deleted)->toBeFalse();
});

it('passes arbitrary wire filters through the escape hatch', function () {
    $http = FakeHttpClient::withJson([]);

    fakeMicroinvest($http)->items()->list(
        name: 'Cola*',
        filters: ['barcode2' => '3800', 'is_very_used' => 'true'],
    );

    $uri = (string) $http->lastRequest()->getUri();

    expect($uri)->toContain('name=Cola%2A')
        ->and($uri)->toContain('barcode2=3800')
        ->and($uri)->toContain('is_very_used=true');
});

it('lets an escape-hatch filter override a named argument', function () {
    $http = FakeHttpClient::withJson([]);

    fakeMicroinvest($http)->items()->list(type: 0, filters: ['type' => 3]);

    expect((string) $http->lastRequest()->getUri())->toContain('type=3')
        ->and((string) $http->lastRequest()->getUri())->not->toContain('type=0');
});

it('gets a single item by id', function () {
    $http = FakeHttpClient::withJson(['id' => 2518, 'name' => 'Item', 'type' => 0]);

    $item = fakeMicroinvest($http)->items()->get(2518);

    expect((string) $http->lastRequest()->getUri())->toBe('http://127.0.0.1:8700/Item?id=2518')
        ->and($item->id)->toBe(2518)
        ->and($item->type)->toBe(0);
});

it('creates an item with a snake_case body', function () {
    $http = FakeHttpClient::withJson(['id' => 99, 'name' => 'New']);

    $item = fakeMicroinvest($http)->items()->create(new ItemInput(
        name: 'New',
        priceOut1: 5.0,
        taxGroup: 1,
        isVeryUsed: true,
    ));

    $request = $http->lastRequest();

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toBe('http://127.0.0.1:8700/Item')
        ->and(json_decode((string) $request->getBody(), true))->toBe([
            'name' => 'New',
            'price_out1' => 5.0,
            'tax_group' => 1,
            'is_very_used' => true,
        ])
        ->and($item->id)->toBe(99);
});

it('updates an item by id', function () {
    $http = FakeHttpClient::withJson(['id' => 5, 'name' => 'Renamed']);

    $item = fakeMicroinvest($http)->items()->update(5, new ItemInput(name: 'Renamed'));

    $request = $http->lastRequest();

    expect($request->getMethod())->toBe('PUT')
        ->and((string) $request->getUri())->toBe('http://127.0.0.1:8700/Item?id=5')
        ->and(json_decode((string) $request->getBody(), true))->toBe(['name' => 'Renamed'])
        ->and($item->name)->toBe('Renamed');
});

it('lists item groups', function () {
    $http = FakeHttpClient::withJson([['id' => 1, 'code' => '-1', 'name' => 'Default Group']]);

    $groups = fakeMicroinvest($http)->items()->groups();

    expect((string) $http->lastRequest()->getUri())->toBe('http://127.0.0.1:8700/ItemsGroups')
        ->and($groups->first())->toBeInstanceOf(NomenclatureGroupResult::class)
        ->and($groups->first()->name)->toBe('Default Group');
});
