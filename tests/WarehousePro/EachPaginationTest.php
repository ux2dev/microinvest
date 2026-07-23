<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('walks every page of partners and flattens them', function () {
    $http = FakeHttpClient::sequence(
        FakeHttpClient::jsonResponse(
            [['id' => 1], ['id' => 2]],
            headers: ['X-CurrentPage' => '1', 'X-TotalPages' => '3'],
        ),
        FakeHttpClient::jsonResponse(
            [['id' => 3]],
            headers: ['X-CurrentPage' => '2', 'X-TotalPages' => '3'],
        ),
        FakeHttpClient::jsonResponse(
            [['id' => 4]],
            headers: ['X-CurrentPage' => '3', 'X-TotalPages' => '3'],
        ),
    );

    $ids = [];
    foreach (fakeWarehousePro($http)->partners()->each() as $partner) {
        $ids[] = $partner->id;
    }

    expect($ids)->toBe([1, 2, 3, 4])
        ->and($http->received)->toHaveCount(3)
        ->and((string) $http->received[0]->getUri())->toContain('page=1')
        ->and((string) $http->received[2]->getUri())->toContain('page=3');
});

it('walks every page of items too', function () {
    $http = FakeHttpClient::sequence(
        FakeHttpClient::jsonResponse(
            [['id' => 10]],
            headers: ['X-CurrentPage' => '1', 'X-TotalPages' => '2'],
        ),
        FakeHttpClient::jsonResponse(
            [['id' => 11]],
            headers: ['X-CurrentPage' => '2', 'X-TotalPages' => '2'],
        ),
    );

    $ids = [];
    foreach (fakeWarehousePro($http)->items()->each() as $item) {
        $ids[] = $item->id;
    }

    expect($ids)->toBe([10, 11])
        ->and($http->received)->toHaveCount(2)
        ->and((string) $http->received[1]->getUri())->toContain('page=2');
});

it('stops after one page when the API reports no paging headers', function () {
    $http = FakeHttpClient::sequence(FakeHttpClient::jsonResponse([['id' => 1]]));

    $ids = [];
    foreach (fakeWarehousePro($http)->items()->each() as $item) {
        $ids[] = $item->id;
    }

    expect($ids)->toBe([1])
        ->and($http->received)->toHaveCount(1);
});

it('stops on an empty page even if the header claims more', function () {
    $http = FakeHttpClient::sequence(
        FakeHttpClient::jsonResponse([], headers: ['X-CurrentPage' => '1', 'X-TotalPages' => '9']),
    );

    $ids = [];
    foreach (fakeWarehousePro($http)->partners()->each() as $partner) {
        $ids[] = $partner->id;
    }

    expect($ids)->toBe([])
        ->and($http->received)->toHaveCount(1);
});
