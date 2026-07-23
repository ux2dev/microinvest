<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;
use Ux2Dev\Microinvest\WarehousePro\Resources\Resource;

$accessors = ['items', 'partners', 'users', 'locations', 'operations', 'store', 'payments', 'documents', 'vatGroups'];

it('exposes every resource accessor returning a Resource subclass', function (string $accessor) {
    $mi = fakeWarehousePro(FakeHttpClient::withJson([]));

    $resource = $mi->{$accessor}();

    expect($resource)->toBeInstanceOf(Resource::class);
})->with($accessors);

it('caches each resource instance (lazy singletons)', function (string $accessor) {
    $mi = fakeWarehousePro(FakeHttpClient::withJson([]));

    expect($mi->{$accessor}())->toBe($mi->{$accessor}());
})->with($accessors);

it('has a public accessor for each concrete resource class', function () use ($accessors) {
    $files = glob(__DIR__ . '/../../../src/WarehousePro/Resources/*.php');
    $classes = array_map(
        static fn (string $f): string => basename($f, '.php'),
        $files,
    );
    $concrete = array_values(array_filter($classes, static fn (string $c): bool => $c !== 'Resource'));

    // Every concrete resource has a matching lcfirst accessor.
    $expected = array_map(static fn (string $c): string => lcfirst($c), $concrete);
    sort($expected);
    $actual = $accessors;
    sort($actual);

    expect($actual)->toBe($expected);
});
