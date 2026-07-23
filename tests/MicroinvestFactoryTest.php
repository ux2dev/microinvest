<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Contracts\Client;
use Ux2Dev\Microinvest\Microinvest;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;
use Ux2Dev\Microinvest\WarehousePro\WarehouseProClient;
use Ux2Dev\Microinvest\WarehousePro\WarehouseProConfig;

it('builds a Warehouse Pro client', function () {
    $factory = FakeHttpClient::factory();

    $client = Microinvest::warehousePro(
        new WarehouseProConfig('http://127.0.0.1:8700', 'k'),
        FakeHttpClient::withJson([]),
        $factory,
        $factory,
    );

    expect($client)->toBeInstanceOf(WarehouseProClient::class)
        ->and($client)->toBeInstanceOf(Client::class);
});

it('builds a micro.bg client', function () {
    $factory = FakeHttpClient::factory();

    $client = Microinvest::microBg(
        new Ux2Dev\Microinvest\MicroBg\MicroBgConfig('api-id', 'secret'),
        FakeHttpClient::withJson([]),
        $factory,
        $factory,
    );

    expect($client)->toBeInstanceOf(Ux2Dev\Microinvest\MicroBg\MicroBgClient::class)
        ->and($client)->toBeInstanceOf(Client::class)
        ->and($client->transport->config->apiId)->toBe('api-id');
});
