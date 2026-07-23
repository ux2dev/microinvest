<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Dto\Result\Operations\OperationDocumentResult;
use Ux2Dev\Microinvest\Enum\DocumentType;
use Ux2Dev\Microinvest\Enum\OperationType;

it('maps every operation type to its documented wire value', function () {
    // The backing values are the codes Api_v1.4.pdf section 5.1 lists.
    expect(array_map(fn (OperationType $t): int => $t->value, OperationType::cases()))
        ->toBe([1, 2, 11, 12, 19, 26, 27, 34, 39]);
});

it('maps every document type to its documented wire value', function () {
    // Api_v1.4.pdf section 7.1: 1 invoice, 5 credit note, 15 debit note.
    expect([
        DocumentType::Invoice->value,
        DocumentType::CreditNote->value,
        DocumentType::DebitNote->value,
    ])->toBe([1, 5, 15]);
});

it('hydrates a known operation type from the wire', function () {
    $operation = OperationDocumentResult::fromMicroBg(['id' => 1, 'OperType' => 2]);

    expect($operation->operationType)->toBe(OperationType::Sale);
});

it('leaves an unrecognised operation type as null', function () {
    // A code outside the documented set must not crash hydration.
    $operation = OperationDocumentResult::fromMicroBg(['id' => 1, 'OperType' => 999]);

    expect($operation->operationType)->toBeNull();
});
