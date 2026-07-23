<?php

declare(strict_types=1);

it('routes each lookup to its documented function name', function (string $call, array $args, string $function, array $parameters) {
    $http = microBgOk([]);

    [$resource, $method] = explode('.', $call);
    fakeMicroBg($http)->{$resource}()->{$method}(...$args);

    expect(microBgPayload($http)['functionName'])->toBe($function)
        ->and(microBgPayload($http)['parameters'])->toBe($parameters);
})->with([
    ['groups.list', ['Items'], 'getGroups', ['Module' => 'Items']],
    ['groups.list', ['Partners'], 'getGroups', ['Module' => 'Partners']],
    ['taxGroups.list', [], 'getTaxGroups', []],
    ['payments.types', [], 'getPaymentTypes', []],
    ['objects.list', [], 'getObjects', []],
]);

it('hydrates the group tree', function () {
    $http = microBgOk([
        ['id' => 100, 'Name' => 'Служебна група', 'Path' => '-1', 'parentId' => 0],
        ['id' => 1733, 'Name' => 'Вина', 'Path' => 'ААА', 'parentId' => 0],
        ['id' => 1737, 'Name' => 'Бяло вино', 'Path' => 'ААААAA', 'parentId' => 1733],
    ]);

    $groups = fakeMicroBg($http)->listItemGroups();

    expect($groups)->toHaveCount(3)
        ->and($groups->first()->path)->toBe('-1')
        ->and($groups->all()[2]->parentId)->toBe(1733);
});

it('creates a group under a parent', function () {
    $http = microBgOk(['id' => 15310, 'Name' => 'Спортни стоки', 'Path' => 'ААЕ']);

    $group = fakeMicroBg($http)->groups()->create('Items', 'Спортни стоки', parentId: 2345);

    expect($group->id)->toBe(15310)
        ->and($group->path)->toBe('ААЕ')
        ->and(microBgPayload($http))->toBe([
            'functionName' => 'insertGroup',
            'parameters' => ['Module' => 'Items', 'parentId' => 2345],
            'functionData' => ['Name' => 'Спортни стоки'],
        ]);
});

it('creates a top level group when neither parent nor path is given', function () {
    $http = microBgOk(['id' => 1, 'Name' => 'Ново']);

    fakeMicroBg($http)->groups()->create('Partners', 'Ново');

    expect(microBgPayload($http)['parameters'])->toBe(['Module' => 'Partners']);
});

it('renames a group', function () {
    $http = microBgOk(['id' => 1765, 'Name' => 'Спортни стоки']);

    fakeMicroBg($http)->groups()->rename('Items', 1765, 'Спортни стоки');

    expect(microBgPayload($http))->toBe([
        'functionName' => 'renameGroup',
        'parameters' => ['Module' => 'Items', 'Id' => 1765],
        'functionData' => ['Name' => 'Спортни стоки'],
    ]);
});

it('deletes a group by id, by path or wholesale', function (array $args, array $parameters) {
    $http = microBgOk(null);

    fakeMicroBg($http)->groups()->delete(...$args);

    expect(microBgPayload($http)['functionName'])->toBe('deleteGroup')
        ->and(microBgPayload($http)['parameters'])->toBe($parameters);
})->with([
    [['Items', 'ById', 1765], ['Module' => 'Items', 'Mode' => 'ById', 'Id' => 1765]],
    [['Items', 'ByPath', null, 'ААВ'], ['Module' => 'Items', 'Mode' => 'ByPath', 'Path' => 'ААВ']],
    [['Items', 'All'], ['Module' => 'Items', 'Mode' => 'All']],
]);

it('hydrates the tax groups, payment types and objects', function () {
    $tax = fakeMicroBg(microBgOk([['TaxGroup' => 1, 'Name' => 'ДДС(Б) 20%', 'TaxValue' => 20]]))->listTaxGroups();
    $payments = fakeMicroBg(microBgOk([['id' => 1, 'Name' => 'В брой', 'PaymentMethod' => 1, 'FiscalMode' => 3, 'Deleted' => 0]]))->listPaymentTypes();
    $objects = fakeMicroBg(microBgOk([['id' => 2, 'Name' => 'Склад', 'Address' => 'София', 'PriceGroup' => 1, 'Deleted' => 0]]))->listObjects();

    expect($tax->first()->vatValue)->toBe(20.0)
        ->and($payments->first()->fiscalMode)->toBe(3)
        ->and($objects->first()->address)->toBe('София');
});

it('reads quantities through the contract method', function () {
    $http = microBgOk([['ItemId' => 8681, 'Qtty' => 3.0]]);

    fakeMicroBg($http)->listQuantities(4);

    expect(microBgPayload($http))->toBe([
        'functionName' => 'getItemQuantities',
        'parameters' => ['ObjectId' => 4],
        'functionData' => null,
    ]);
});

it('caches each resource instance', function (string $accessor) {
    $client = fakeMicroBg(microBgOk([]));

    expect($client->{$accessor}())->toBe($client->{$accessor}());
})->with(['partners', 'items', 'groups', 'taxGroups', 'payments', 'objects']);
