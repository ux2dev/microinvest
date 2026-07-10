<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Dto\Input\Documents\DocumentInput;
use Ux2Dev\Microinvest\Dto\Result\Documents\DocumentResult;
use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Tests\Http\FakeHttpClient;

it('lists documents with invoice range filters', function () {
    $http = FakeHttpClient::withJson([['id' => 71542, 'document_number' => 300053, 'invoice_number' => '0000300015']]);

    $list = fakeMicroinvest($http)->documents()->list(
        dateFrom: '2023-04-03',
        invoiceNumberFrom: '0000200100',
        invoiceNumberTo: '0000200108',
    );

    $uri = (string) $http->lastRequest()->getUri();

    expect($uri)->toContain('/Documents?')
        ->and($uri)->toContain('invoice_number_from=0000200100')
        ->and($list->first())->toBeInstanceOf(DocumentResult::class)
        ->and($list->first()->invoiceNumber)->toBe('0000300015');
});

it('gets a single document', function () {
    $http = FakeHttpClient::withJson(['id' => 1, 'document_number' => 300053, 'invoice_number' => '0000300015', 'document_type' => 0]);

    $document = fakeMicroinvest($http)->documents()->get(
        operationType: 2,
        documentNumber: 300053,
        documentType: 0,
        invoiceNumber: 300015,
    );

    $uri = (string) $http->lastRequest()->getUri();

    expect($uri)->toContain('operation_type=2')
        ->and($uri)->toContain('document_number=300053')
        ->and($uri)->toContain('document_type=0')
        ->and($uri)->toContain('invoice_number=300015')
        ->and($document)->toBeInstanceOf(DocumentResult::class)
        ->and($document->invoiceNumber)->toBe('0000300015');
});

it('creates a document', function () {
    $http = FakeHttpClient::withJson([['id' => 1, 'document_number' => 300053, 'invoice_number' => '0000300015']]);

    $list = fakeMicroinvest($http)->documents()->create(new DocumentInput(
        documentNumber: 300053,
        invoiceNumber: '0000300015',
        operationType: 2,
        documentType: 0,
        issuedBy: 'Victor Pavlov',
    ));

    $request = $http->lastRequest();

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toBe('http://127.0.0.1:8700/Document')
        ->and(json_decode((string) $request->getBody(), true))->toBe([
            'document_number' => 300053,
            'invoice_number' => '0000300015',
            'operation_type' => 2,
            'document_type' => 0,
            'issued_by' => 'Victor Pavlov',
        ])
        ->and($list->first())->toBeInstanceOf(DocumentResult::class);
});

it('surfaces a 409 conflict as an ApiException', function () {
    $http = FakeHttpClient::withJson(['code' => 3, 'message' => 'Operation already contains document.'], status: 409);

    fakeMicroinvest($http)->documents()->create(new DocumentInput(documentNumber: 300053));
})->throws(ApiException::class, 'Operation already contains document.');
