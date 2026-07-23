<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\MicroBg\MicroBgClient;
use Ux2Dev\Microinvest\MicroBg\MicroBgConfig;
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

/**
 * Build a micro.bg client wired to a fake PSR-18 client.
 */
function fakeMicroBg(
    FakeHttpClient $http,
    string $apiId = 'api-id',
    string $secretKey = 'secret',
): MicroBgClient {
    $factory = FakeHttpClient::factory();

    return new MicroBgClient(new MicroBgConfig($apiId, $secretKey), $http, $factory, $factory);
}

/**
 * Decode the signed payload of the last micro.bg request, so tests can assert
 * on functionName / parameters / functionData rather than on a base64 blob.
 *
 * @return array<string, mixed>
 */
function microBgPayload(FakeHttpClient $http): array
{
    parse_str((string) $http->lastRequest()->getBody(), $fields);

    return json_decode(base64_decode(urldecode(substr($fields['Request'], 0, -64))), true);
}
