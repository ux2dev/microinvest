<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Contracts\Client;
use Ux2Dev\Microinvest\Contracts\ItemRepository;
use Ux2Dev\Microinvest\Contracts\PartnerRepository;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('satisfies the shared Client contract', function () {
    $client = fakeWarehousePro(FakeHttpClient::withJson([]));

    expect($client)->toBeInstanceOf(Client::class)
        ->and($client->partners())->toBeInstanceOf(PartnerRepository::class)
        ->and($client->items())->toBeInstanceOf(ItemRepository::class);
});

it('routes each lookup to its Warehouse Pro endpoint', function (string $method, string $path) {
    $http = FakeHttpClient::withJson([]);

    fakeWarehousePro($http)->{$method}();

    expect((string) $http->lastRequest()->getUri())->toContain($path);
})->with([
    ['listItemGroups', '/ItemsGroups'],
    ['listPartnerGroups', '/PartnersGroups'],
    ['listTaxGroups', '/VATGroups'],
    ['listPaymentTypes', '/PaymentTypes'],
    ['listObjects', '/Locations'],
    ['listQuantities', '/Store'],
]);

it('passes the object id through to the store endpoint', function () {
    $http = FakeHttpClient::withJson([]);

    fakeWarehousePro($http)->listQuantities(1232);

    expect((string) $http->lastRequest()->getUri())->toContain('object_id=1232');
});
