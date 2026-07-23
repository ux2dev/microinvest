<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Dto\Result\Locations\LocationResult;
use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Dto\Result\Store\StoreResult;
use Ux2Dev\Microinvest\Dto\Result\Users\UserResult;
use Ux2Dev\Microinvest\Dto\Result\VatGroups\VatGroupResult;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('lists users and user groups', function () {
    $http = FakeHttpClient::withJson([['id' => 2, 'name' => 'John Smith', 'userlevel' => 3]]);
    $mi = fakeWarehousePro($http);

    $users = $mi->users()->list(name: 'John*');
    expect((string) $http->lastRequest()->getUri())->toContain('/Users?name=John%2A')
        ->and($users->first())->toBeInstanceOf(UserResult::class)
        ->and($users->first()->userlevel)->toBe(3);

    $mi->users()->groups();
    expect((string) $http->lastRequest()->getUri())->toBe('http://127.0.0.1:8700/UsersGroups');
});

it('lists locations and location groups', function () {
    $http = FakeHttpClient::withJson([['id' => 2, 'name' => 'Office Sofia', 'is_very_used' => true]]);
    $mi = fakeWarehousePro($http);

    $locations = $mi->locations()->list(groupId: 3);
    expect((string) $http->lastRequest()->getUri())->toContain('/Locations?group_id=3')
        ->and($locations->first())->toBeInstanceOf(LocationResult::class)
        ->and($locations->first()->isVeryUsed)->toBeTrue();

    $mi->locations()->groups();
    expect((string) $http->lastRequest()->getUri())->toBe('http://127.0.0.1:8700/LocationsGroups');
});

it('lists stock by location', function () {
    $http = FakeHttpClient::withJson([['id' => 72253, 'object_id' => 2, 'good_id' => '5140', 'qtty' => '1.230', 'price' => '103.33']]);

    $store = fakeWarehousePro($http)->store()->list(objectId: 4, page: 1, pageSize: 2000);

    $uri = (string) $http->lastRequest()->getUri();

    expect($uri)->toContain('/Store?object_id=4')
        ->and($uri)->toContain('page_size=2000')
        ->and($store->first())->toBeInstanceOf(StoreResult::class)
        ->and($store->first()->goodId)->toBe('5140')
        ->and($store->first()->qtty)->toBe(1.23);
});

it('lists VAT groups', function () {
    $http = FakeHttpClient::withJson([['id' => 1, 'code' => '1', 'name' => 'Primary VAT', 'vat_value' => '20.00']]);

    $groups = fakeWarehousePro($http)->vatGroups()->list();

    expect((string) $http->lastRequest()->getUri())->toBe('http://127.0.0.1:8700/VATGroups')
        ->and($groups->first())->toBeInstanceOf(VatGroupResult::class)
        ->and($groups->first()->vatValue)->toBe(20.0);
});

it('exposes NomenclatureGroupResult hydration edge cases', function () {
    $group = NomenclatureGroupResult::fromWarehousePro(['id' => 5, 'code' => 'G', 'name' => 'Grp']);

    expect($group->id)->toBe(5)
        ->and(NomenclatureGroupResult::fromWarehousePro([])->id)->toBeNull();
});
