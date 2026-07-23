<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('returns queued responses in order', function () {
    $http = FakeHttpClient::sequence(
        FakeHttpClient::jsonResponse([['id' => 1]], headers: ['X-TotalPages' => '2']),
        FakeHttpClient::jsonResponse([['id' => 2]], headers: ['X-TotalPages' => '2']),
    );

    $factory = FakeHttpClient::factory();
    $first = $http->sendRequest($factory->createRequest('GET', 'http://x/1'));
    $second = $http->sendRequest($factory->createRequest('GET', 'http://x/2'));

    expect((string) $first->getBody())->toBe('[{"id":1}]')
        ->and((string) $second->getBody())->toBe('[{"id":2}]')
        ->and($second->getHeaderLine('X-TotalPages'))->toBe('2')
        ->and($http->received)->toHaveCount(2);
});

it('falls back to the default empty body once the queue is drained', function () {
    $http = FakeHttpClient::sequence(FakeHttpClient::jsonResponse([['id' => 1]]));

    $factory = FakeHttpClient::factory();
    $http->sendRequest($factory->createRequest('GET', 'http://x/1'));
    $drained = $http->sendRequest($factory->createRequest('GET', 'http://x/2'));

    expect((string) $drained->getBody())->toBe('[]');
});
