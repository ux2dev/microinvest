<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Dto\Input\Payments\PaymentInput;
use Ux2Dev\Microinvest\Dto\Result\Payments\PaymentResult;
use Ux2Dev\Microinvest\Dto\Result\Payments\PaymentTypeResult;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('lists payments by range filters', function () {
    $http = FakeHttpClient::withJson([['id' => 71542, 'operation_type' => 2, 'qtty' => '124.18']]);

    $list = fakeMicroinvest($http)->payments()->list(operationType: 2, dateFrom: '2023-04-01', documentTo: 3000500);

    $uri = (string) $http->lastRequest()->getUri();

    expect($uri)->toContain('/Payments?')
        ->and($uri)->toContain('operation_type=2')
        ->and($uri)->toContain('date_from=2023-04-01')
        ->and($list->first())->toBeInstanceOf(PaymentResult::class)
        ->and($list->first()->qtty)->toBe(124.18);
});

it('gets payments for one operation', function () {
    $http = FakeHttpClient::withJson([['id' => 1, 'operation_type' => 2, 'document_number' => 3000406]]);

    $list = fakeMicroinvest($http)->payments()->get(operationType: 2, documentNumber: 3000406);

    expect((string) $http->lastRequest()->getUri())
        ->toBe('http://127.0.0.1:8700/Payment?operation_type=2&document_number=3000406')
        ->and($list->first()->documentNumber)->toBe(3000406);
});

it('adds a payment to an existing operation', function () {
    $http = FakeHttpClient::withJson([['id' => 71542, 'operation_type' => 2, 'document_number' => 3000406]]);

    $list = fakeMicroinvest($http)->payments()->create(new PaymentInput(
        operationType: 2,
        documentNumber: 3000406,
        qtty: 124.18,
        mode: 1,
        sign: -1,
        date: '2023-04-13',
        userId: 8,
        paymentType: 3,
    ));

    $request = $http->lastRequest();

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toBe('http://127.0.0.1:8700/Payment')
        ->and(json_decode((string) $request->getBody(), true))->toBe([
            'operation_type' => 2,
            'document_number' => 3000406,
            'qtty' => 124.18,
            'mode' => 1,
            'sign' => -1,
            'date' => '2023-04-13',
            'user_id' => 8,
            'payment_type' => 3,
        ])
        ->and($list->first())->toBeInstanceOf(PaymentResult::class);
});

it('lists payment types', function () {
    $http = FakeHttpClient::withJson([['id' => 1, 'name' => 'Cash', 'payment_method' => 1]]);

    $types = fakeMicroinvest($http)->payments()->types();

    expect((string) $http->lastRequest()->getUri())->toBe('http://127.0.0.1:8700/PaymentTypes')
        ->and($types->first())->toBeInstanceOf(PaymentTypeResult::class)
        ->and($types->first()->paymentMethod)->toBe(1);
});
