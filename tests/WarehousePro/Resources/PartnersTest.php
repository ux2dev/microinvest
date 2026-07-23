<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Dto\Input\Partners\PartnerInput;
use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Dto\Result\Partners\PartnerResult;
use Ux2Dev\Microinvest\Enum\PartnerType;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('lists partners with filters', function () {
    $http = FakeHttpClient::withJson([['id' => 3108, 'company' => 'Microinvest EOOD', 'type' => 2]]);

    $list = fakeWarehousePro($http)->partners()->list(company: 'Micro*', type: PartnerType::Supplier);

    $uri = (string) $http->lastRequest()->getUri();

    expect($uri)->toContain('/Partners?')
        ->and($uri)->toContain('company=Micro%2A')
        ->and($uri)->toContain('type=2')
        ->and($list->first())->toBeInstanceOf(PartnerResult::class)
        ->and($list->first()->company)->toBe('Microinvest EOOD');
});

it('gets a single partner by id', function () {
    $http = FakeHttpClient::withJson(['id' => 94, 'company' => 'Acme', 'discount' => '2.50']);

    $partner = fakeWarehousePro($http)->partners()->get(94);

    expect((string) $http->lastRequest()->getUri())->toBe('http://127.0.0.1:8700/Partner?id=94')
        ->and($partner->id)->toBe(94)
        ->and($partner->discount)->toBe(2.5);
});

it('creates a partner with snake_case keys', function () {
    $http = FakeHttpClient::withJson(['id' => 1, 'company' => 'Retail']);

    fakeWarehousePro($http)->partners()->create(new PartnerInput(
        company: 'Retail',
        vatId: 'BG123',
        priceGroup: 1,
        paymentDays: 14,
    ));

    $request = $http->lastRequest();

    expect($request->getMethod())->toBe('POST')
        ->and(json_decode((string) $request->getBody(), true))->toBe([
            'company' => 'Retail',
            'vat_id' => 'BG123',
            'price_group' => 1,
            'payment_days' => 14,
        ]);
});

it('updates a partner by id', function () {
    $http = FakeHttpClient::withJson(['id' => 8, 'company' => 'Updated']);

    fakeWarehousePro($http)->partners()->update(8, new PartnerInput(company: 'Updated'));

    expect($http->lastRequest()->getMethod())->toBe('PUT')
        ->and((string) $http->lastRequest()->getUri())->toBe('http://127.0.0.1:8700/Partner?id=8');
});

it('lists partner groups', function () {
    $http = FakeHttpClient::withJson([['id' => 1, 'code' => '-1', 'name' => 'Default']]);

    $groups = fakeWarehousePro($http)->partners()->groups(page: 1, pageSize: 100);

    expect((string) $http->lastRequest()->getUri())->toContain('/PartnersGroups?')
        ->and($groups->first())->toBeInstanceOf(NomenclatureGroupResult::class);
});
