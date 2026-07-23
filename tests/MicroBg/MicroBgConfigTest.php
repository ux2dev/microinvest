<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Exception\ConfigurationException;
use Ux2Dev\Microinvest\MicroBg\MicroBgConfig;

it('defaults to the documented entry point and normalises the trailing slash', function () {
    expect((new MicroBgConfig('1821761530712553', 'a6808173988b'))->entryPoint)
        ->toBe('https://micro.bg/ExtApps/ExternalApp/API/')
        ->and((new MicroBgConfig('a', 'b', 'https://staging.micro.bg/api'))->entryPoint)
        ->toBe('https://staging.micro.bg/api/');
});

it('exposes the api id but hides the secret key', function () {
    $config = new MicroBgConfig('1821761530712553', 'a6808173988b');

    expect($config->apiId)->toBe('1821761530712553')
        ->and($config->getSecretKey())->toBe('a6808173988b')
        ->and($config->__debugInfo()['secretKey'])->toBe('[REDACTED]')
        ->and($config->__debugInfo()['apiId'])->toBe('1821761530712553');
});

it('rejects invalid input', function (string $apiId, string $secret, string $entry, int $timeout, string $message) {
    expect(fn () => new MicroBgConfig($apiId, $secret, $entry, $timeout))
        ->toThrow(ConfigurationException::class, $message);
})->with([
    ['', 'k', 'https://micro.bg/', 30, 'apiId must not be empty'],
    ['a', '', 'https://micro.bg/', 30, 'secretKey must not be empty'],
    ['a', 'k', 'micro.bg', 30, 'entryPoint must start with http:// or https://'],
    ['a', 'k', 'https://micro.bg/', 0, 'timeout must be at least 1 second'],
]);

it('refuses to be serialized', function () {
    expect(fn () => serialize(new MicroBgConfig('a', 'k')))->toThrow(ConfigurationException::class);
});

it('refuses to be unserialized', function () {
    expect(fn () => (new MicroBgConfig('a', 'k'))->__unserialize([]))->toThrow(ConfigurationException::class);
});
