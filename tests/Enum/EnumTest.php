<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Dto\Result\Operations\OperationDocumentResult;
use Ux2Dev\Microinvest\Enum\CodeType;
use Ux2Dev\Microinvest\Enum\CostAllocationMethod;
use Ux2Dev\Microinvest\Enum\DocumentType;
use Ux2Dev\Microinvest\Enum\FiscalMode;
use Ux2Dev\Microinvest\Enum\GroupDeleteMode;
use Ux2Dev\Microinvest\Enum\GroupModule;
use Ux2Dev\Microinvest\Enum\OperationType;
use Ux2Dev\Microinvest\Enum\PartnerType;
use Ux2Dev\Microinvest\Enum\PaymentMethod;
use Ux2Dev\Microinvest\Enum\StockSign;

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

it('maps every remaining nomenclature to its documented wire value', function () {
    // Int-backed, per Api_v1.4.pdf.
    expect(array_map(fn (PaymentMethod $m): int => $m->value, PaymentMethod::cases()))->toBe([1, 2, 3, 4])
        ->and(array_map(fn (FiscalMode $m): int => $m->value, FiscalMode::cases()))->toBe([1, 2, 3])
        ->and(array_map(fn (PartnerType $t): int => $t->value, PartnerType::cases()))->toBe([1, 2, 3])
        ->and(array_map(fn (CodeType $c): int => $c->value, CodeType::cases()))->toBe([1, 2])
        ->and(array_map(fn (CostAllocationMethod $c): int => $c->value, CostAllocationMethod::cases()))->toBe([1, 2])
        ->and(array_map(fn (StockSign $s): int => $s->value, StockSign::cases()))->toBe([-1, 1])
        // String-backed: the value is the literal the wire carries.
        ->and(array_map(fn (GroupModule $g): string => $g->value, GroupModule::cases()))->toBe(['Items', 'Partners'])
        ->and(array_map(fn (GroupDeleteMode $g): string => $g->value, GroupDeleteMode::cases()))->toBe(['ById', 'ByPath', 'All']);
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
