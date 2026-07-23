<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Dto\Input\Partners\PartnerInput;
use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

function microBgOk(mixed $data): FakeHttpClient
{
    return FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => $data]);
}

it('lists partners with the documented parameters', function () {
    $http = microBgOk([]);

    fakeMicroBg($http)->partners()->list(fromDate: '2018-06-01 14:00:00', limit: 50);

    expect(microBgPayload($http))->toBe([
        'functionName' => 'getPartners',
        'parameters' => ['fromDate' => '2018-06-01 14:00:00', 'limit' => 50],
        'functionData' => null,
    ]);
});

it('passes the escape-hatch filters through', function () {
    $http = microBgOk([]);

    fakeMicroBg($http)->partners()->list(code: '102', filters: ['SomethingNew' => 'x']);

    expect(microBgPayload($http)['parameters'])->toBe(['Code' => '102', 'SomethingNew' => 'x']);
});

it('gets one partner by id', function () {
    $http = microBgOk([['id' => 17, 'Name' => 'ACME']]);

    $partner = fakeMicroBg($http)->partners()->get(17);

    expect($partner->company)->toBe('ACME')
        ->and(microBgPayload($http)['parameters'])->toBe(['Id' => 17]);
});

it('throws when a partner is not found', function () {
    expect(fn () => fakeMicroBg(microBgOk([]))->partners()->get(999))
        ->toThrow(ApiException::class, 'Partner 999 was not found');
});

it('creates a partner and asks for a generated code by default', function () {
    $http = microBgOk(['id' => 92729, 'Name' => 'ACME']);

    $partner = fakeMicroBg($http)->partners()->create(new PartnerInput(company: 'ACME'));

    expect($partner->id)->toBe(92729)
        ->and(microBgPayload($http))->toBe([
            'functionName' => 'insertPartner',
            'parameters' => ['AutoGenerateCode' => 1],
            'functionData' => ['Name' => 'ACME'],
        ]);
});

it('can opt out of the generated code', function () {
    $http = microBgOk(['id' => 1]);

    fakeMicroBg($http)->partners()->create(new PartnerInput(company: 'ACME'), autoGenerateCode: false);

    expect(microBgPayload($http)['parameters'])->toBe(['AutoGenerateCode' => 0]);
});

it('updates a partner by putting the id inside functionData', function () {
    $http = microBgOk(['id' => 92729]);

    fakeMicroBg($http)->partners()->update(92729, new PartnerInput(address: 'ул. Лале, №21'));

    expect(microBgPayload($http))->toBe([
        'functionName' => 'editPartner',
        'parameters' => [],
        'functionData' => ['Address' => 'ул. Лале, №21', 'id' => 92729],
    ]);
});

it('deletes a partner', function () {
    $http = microBgOk(null);

    fakeMicroBg($http)->partners()->delete(92729);

    expect(microBgPayload($http))->toBe([
        'functionName' => 'deletePartner',
        'parameters' => ['Id' => 92729],
        'functionData' => null,
    ]);
});

it('walks partners with a fromId cursor', function () {
    $http = FakeHttpClient::sequence(
        FakeHttpClient::jsonResponse(['status' => 1, 'errors' => [], 'data' => [['id' => 1], ['id' => 2]]]),
        FakeHttpClient::jsonResponse(['status' => 1, 'errors' => [], 'data' => [['id' => 5]]]),
    );

    $ids = [];
    foreach (fakeMicroBg($http)->partners()->each() as $partner) {
        $ids[] = $partner->id;
    }

    // The first page is short (2 < 100), so the walk stops after one request.
    expect($ids)->toBe([1, 2])
        ->and($http->received)->toHaveCount(1);
});

it('stops walking when a page has no usable cursor', function () {
    $http = microBgOk([['Name' => 'no id']]);

    $ids = [];
    foreach (fakeMicroBg($http)->partners()->each() as $partner) {
        $ids[] = $partner->id;
    }

    expect($ids)->toBe([null])
        ->and($http->received)->toHaveCount(1);
});

it('requests a full batch and advances the cursor when the page is full', function () {
    $full = array_map(static fn (int $i): array => ['id' => $i], range(1, 100));

    $http = FakeHttpClient::sequence(
        FakeHttpClient::jsonResponse(['status' => 1, 'errors' => [], 'data' => $full]),
        FakeHttpClient::jsonResponse(['status' => 1, 'errors' => [], 'data' => [['id' => 101]]]),
    );

    $ids = [];
    foreach (fakeMicroBg($http)->partners()->each() as $partner) {
        $ids[] = $partner->id;
    }

    expect($ids)->toHaveCount(101)
        ->and($http->received)->toHaveCount(2)
        ->and(microBgPayload($http)['parameters'])->toBe(['fromId' => 100, 'limit' => 100]);
});
