<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Config\MicroinvestConfig;
use Ux2Dev\Microinvest\Exception\ConfigurationException;

it('normalizes the base url by trimming trailing slashes', function () {
    $config = new MicroinvestConfig('http://127.0.0.1:8700/');

    expect($config->baseUrl)->toBe('http://127.0.0.1:8700');
});

it('defaults to anonymous access and 30s timeout', function () {
    $config = new MicroinvestConfig('http://127.0.0.1:8700');

    expect($config->getApiKey())->toBeNull()
        ->and($config->timeout)->toBe(30);
});

it('keeps the api key when provided', function () {
    $config = new MicroinvestConfig('http://127.0.0.1:8700', 'tok', 15);

    expect($config->getApiKey())->toBe('tok')
        ->and($config->timeout)->toBe(15);
});

it('rejects an empty base url', function () {
    new MicroinvestConfig('');
})->throws(ConfigurationException::class, 'baseUrl must not be empty');

it('rejects a base url without an http scheme', function () {
    new MicroinvestConfig('127.0.0.1:8700');
})->throws(ConfigurationException::class, 'baseUrl must start with http');

it('rejects an empty api key string', function () {
    new MicroinvestConfig('http://127.0.0.1:8700', '');
})->throws(ConfigurationException::class, 'apiKey must not be an empty string');

it('rejects a timeout below one second', function () {
    new MicroinvestConfig('http://127.0.0.1:8700', null, 0);
})->throws(ConfigurationException::class, 'timeout must be at least 1 second');

it('redacts the api key in debug output', function () {
    $withKey = new MicroinvestConfig('http://127.0.0.1:8700', 'secret');
    $anon = new MicroinvestConfig('http://127.0.0.1:8700');

    expect($withKey->__debugInfo()['apiKey'])->toBe('[REDACTED]')
        ->and($anon->__debugInfo()['apiKey'])->toBeNull();
});

it('refuses to serialize', function () {
    serialize(new MicroinvestConfig('http://127.0.0.1:8700', 'secret'));
})->throws(LogicException::class);

it('refuses to unserialize', function () {
    (new MicroinvestConfig('http://127.0.0.1:8700'))->__unserialize([]);
})->throws(LogicException::class);
