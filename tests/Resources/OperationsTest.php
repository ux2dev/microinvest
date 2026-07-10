<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Dto\Input\Operations\OperationInput;
use Ux2Dev\Microinvest\Dto\Result\Operations\OperationResult;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('lists operations by type and period', function () {
    $http = FakeHttpClient::withJson([['id' => 80306, 'operation_type' => 2, 'document_number' => 3000106]]);

    $list = fakeMicroinvest($http)->operations()->list(
        operationType: 2,
        objectId: 4,
        dateFrom: '2023-04-03',
        dateTo: '2023-04-19',
        documentFrom: 3000081,
        documentTo: 3000091,
    );

    $uri = (string) $http->lastRequest()->getUri();

    expect($uri)->toContain('/Operations?')
        ->and($uri)->toContain('operation_type=2')
        ->and($uri)->toContain('object_id=4')
        ->and($uri)->toContain('date_from=2023-04-03')
        ->and($uri)->toContain('document_to=3000091')
        ->and($list->first())->toBeInstanceOf(OperationResult::class)
        ->and($list->first()->documentNumber)->toBe(3000106);
});

it('gets an operation by type and document number', function () {
    $http = FakeHttpClient::withJson([['id' => 1, 'operation_type' => 2, 'document_number' => 3000106, 'sign' => -1]]);

    $list = fakeMicroinvest($http)->operations()->get(operationType: 2, documentNumber: 3000106);

    expect((string) $http->lastRequest()->getUri())
        ->toBe('http://127.0.0.1:8700/Operation?operation_type=2&document_number=3000106')
        ->and($list->first()->sign)->toBe(-1);
});

it('creates an operation from an array of input rows', function () {
    $http = FakeHttpClient::withJson([
        ['id' => 80306, 'operation_type' => 2, 'document_number' => 3000106, 'good_id' => '66'],
    ]);

    $list = fakeMicroinvest($http)->operations()->create([
        new OperationInput(operationType: 2, goodId: '66', objectId: 2, qtty: 1.0, priceOut: 7.29, date: '2023-04-07', userId: 2),
        new OperationInput(goodId: '66', qtty: 0.745, priceOut: 12.5906),
    ]);

    $request = $http->lastRequest();
    $body = json_decode((string) $request->getBody(), true);

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toBe('http://127.0.0.1:8700/Operation')
        ->and($body)->toBeArray()
        ->and($body)->toHaveCount(2)
        ->and($body[0])->toBe([
            'operation_type' => 2,
            'good_id' => '66',
            'object_id' => 2,
            'qtty' => 1.0,
            'price_out' => 7.29,
            'date' => '2023-04-07',
            'user_id' => 2,
        ])
        ->and($body[1])->toBe(['good_id' => '66', 'qtty' => 0.745, 'price_out' => 12.5906])
        ->and($list->first())->toBeInstanceOf(OperationResult::class)
        ->and($list->first()->goodId)->toBe('66');
});
