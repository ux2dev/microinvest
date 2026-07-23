<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Contracts\Client;
use Ux2Dev\Microinvest\Contracts\ItemRepository;
use Ux2Dev\Microinvest\Contracts\PartnerRepository;
use Ux2Dev\Microinvest\Dto\Input\Partners\PartnerInput;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

/**
 * Both backends must behave identically through Contracts\Client, even though
 * they share no transport code. Each row is a factory that wraps the same
 * logical rows in that backend's own envelope and dialect.
 */
dataset('backends', [
    'warehouse_pro' => [
        fn (array $rows) => fakeWarehousePro(FakeHttpClient::withJson($rows)),
    ],
    'micro_bg' => [
        fn (array $rows) => fakeMicroBg(FakeHttpClient::withJson(['status' => 1, 'errors' => [], 'data' => $rows])),
    ],
]);

it('satisfies the Client contract', function (callable $make) {
    $client = $make([]);

    expect($client)->toBeInstanceOf(Client::class)
        ->and($client->partners())->toBeInstanceOf(PartnerRepository::class)
        ->and($client->items())->toBeInstanceOf(ItemRepository::class);
})->with('backends');

it('exposes every lookup on the contract and returns a ResultList', function (callable $make) {
    foreach (['listItemGroups', 'listPartnerGroups', 'listTaxGroups', 'listPaymentTypes', 'listObjects'] as $method) {
        expect($make([])->{$method}()->all())->toBe([]);
    }

    expect($make([])->listQuantities()->all())->toBe([])
        ->and($make([])->listQuantities(4)->all())->toBe([]);
})->with('backends');

it('walks an empty nomenclature without a single yield', function (callable $make) {
    expect(iterator_to_array($make([])->partners()->each()))->toBe([])
        ->and(iterator_to_array($make([])->items()->each()))->toBe([]);
})->with('backends');

it('walks a short page and stops', function (callable $make) {
    // Both dialects spell the identifier `id`, so one fixture serves both.
    $partners = iterator_to_array($make([['id' => 1], ['id' => 2]])->partners()->each());

    expect($partners)->toHaveCount(2)
        ->and($partners[0]->id)->toBe(1)
        ->and($partners[1]->id)->toBe(2);
})->with('backends');

it('accepts the same PartnerInput regardless of backend', function (callable $make) {
    $client = $make([['id' => 5]]);

    // Warehouse Pro returns a single object for create; micro.bg does too.
    $input = new PartnerInput(company: 'ACME', taxId: '831826092');

    expect($input->toWarehouseProArray())->not->toBeEmpty()
        ->and($input->toMicroBgArray())->not->toBeEmpty()
        ->and($client->partners())->toBeInstanceOf(PartnerRepository::class);
})->with('backends');

it('reports the same shape from each() for both dialects', function () {
    $warehouse = iterator_to_array(
        fakeWarehousePro(FakeHttpClient::withJson([['id' => 17, 'company' => 'ACME', 'tax_id' => '101744907']]))
            ->partners()->each(),
    );

    $cloud = iterator_to_array(
        fakeMicroBg(FakeHttpClient::withJson([
            'status' => 1,
            'errors' => [],
            'data' => [['id' => 17, 'Name' => 'ACME', 'TaxID' => '101744907']],
        ]))->partners()->each(),
    );

    // Same DTO class, same values, two entirely different wire formats.
    expect($warehouse[0])->toBeInstanceOf($cloud[0]::class)
        ->and($warehouse[0]->id)->toBe($cloud[0]->id)
        ->and($warehouse[0]->company)->toBe($cloud[0]->company)
        ->and($warehouse[0]->taxId)->toBe($cloud[0]->taxId);
});
