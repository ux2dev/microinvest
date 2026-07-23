<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Exception\ConfigurationException;
use Ux2Dev\Microinvest\Laravel\Facades\Microinvest;
use Ux2Dev\Microinvest\Laravel\MicroinvestManager;
use Ux2Dev\Microinvest\WarehousePro\WarehouseProClient as MicroinvestClient;
use Ux2Dev\Microinvest\WarehousePro\Resources\Items;

it('resolves the manager from the container', function () {
    expect(app(MicroinvestManager::class))->toBeInstanceOf(MicroinvestManager::class)
        ->and(app('microinvest'))->toBeInstanceOf(MicroinvestManager::class);
});

it('builds a client for the default connection', function () {
    $manager = app(MicroinvestManager::class);

    expect($manager->currentConnection())->toBe('local')
        ->and($manager->client())->toBeInstanceOf(MicroinvestClient::class)
        ->and($manager->client()->transport->config->baseUrl)->toBe('http://127.0.0.1:8700')
        ->and($manager->client()->transport->config->getApiKey())->toBe('local-key');
});

it('caches the client per connection', function () {
    $manager = app(MicroinvestManager::class);

    expect($manager->client())->toBe($manager->client());
});

it('switches connection immutably and supports anonymous access', function () {
    $manager = app(MicroinvestManager::class);
    $remote = $manager->connection('remote');

    expect($remote)->not->toBe($manager)
        ->and($manager->currentConnection())->toBe('local')
        ->and($remote->currentConnection())->toBe('remote')
        ->and($remote->client()->transport->config->baseUrl)->toBe('https://192.168.1.10:8701')
        ->and($remote->client()->transport->config->getApiKey())->toBeNull();
});

it('forwards resource accessors through __call', function () {
    expect(app(MicroinvestManager::class)->items())->toBeInstanceOf(Items::class)
        ->and(Microinvest::items())->toBeInstanceOf(Items::class);
});

it('throws for an unknown connection', function () {
    app(MicroinvestManager::class)->connection('does-not-exist')->client();
})->throws(ConfigurationException::class, 'is not configured');
