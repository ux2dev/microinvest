<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;
use Ux2Dev\Microinvest\WarehousePro\WarehouseProClient;
use Ux2Dev\Microinvest\WarehousePro\WarehouseProConfig;

uses(Ux2Dev\Microinvest\Tests\Laravel\TestCase::class)->in('Laravel');

/**
 * Build a Warehouse Pro client wired to a fake PSR-18 client.
 */
function fakeWarehousePro(
    FakeHttpClient $http,
    ?string $apiKey = 'secret-key',
    string $baseUrl = 'http://127.0.0.1:8700',
): WarehouseProClient {
    $factory = FakeHttpClient::factory();

    return new WarehouseProClient(
        new WarehouseProConfig($baseUrl, $apiKey),
        $http,
        $factory,
        $factory,
    );
}
