<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Config\MicroinvestConfig;
use Ux2Dev\Microinvest\Microinvest;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

uses(Ux2Dev\Microinvest\Tests\Laravel\TestCase::class)->in('Laravel');

/**
 * Build a Microinvest client wired to a fake PSR-18 client.
 */
function fakeMicroinvest(
    FakeHttpClient $http,
    ?string $apiKey = 'secret-key',
    string $baseUrl = 'http://127.0.0.1:8700',
): Microinvest {
    $factory = FakeHttpClient::factory();

    return new Microinvest(
        new MicroinvestConfig($baseUrl, $apiKey),
        $http,
        $factory,
        $factory,
    );
}
