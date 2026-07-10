<?php

declare(strict_types=1);

it('merges the packaged config under the microinvest key', function () {
    expect(config('microinvest.default'))->toBe('local')
        ->and(config('microinvest.connections.local.base_url'))->toBe('http://127.0.0.1:8700');
});

it('registers the config file for publishing', function () {
    $paths = \Illuminate\Support\ServiceProvider::pathsToPublish(
        \Ux2Dev\Microinvest\Laravel\MicroinvestServiceProvider::class,
        'microinvest-config',
    );

    expect($paths)->not->toBeEmpty();

    $target = array_values($paths)[0];
    expect($target)->toEndWith('microinvest.php');
});
