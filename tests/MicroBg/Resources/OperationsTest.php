<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Enum\OperationType;
use Ux2Dev\Microinvest\Dto\Input\Operations\OperationDocumentInput;
use Ux2Dev\Microinvest\Dto\Input\Operations\OperationLineInput;
use Ux2Dev\Microinvest\Dto\Input\Payments\PaymentEntryInput;
use Ux2Dev\Microinvest\Exception\ConfigurationException;

it('lists operations of one type', function () {
    $http = microBgOk([]);

    fakeMicroBg($http)->operations()->list(operationType: OperationType::Sale, fromDate: '2018-02-01 05:00:00', limit: 50);

    expect(microBgPayload($http))->toBe([
        'functionName' => 'getOperations',
        'parameters' => ['OperType' => 2, 'fromDate' => '2018-02-01 05:00:00', 'limit' => 50],
        'functionData' => null,
    ]);
});

it('hydrates a document with its nested lines', function () {
    // Shortened from Api_v1.4.pdf, section 5.1.
    $http = microBgOk([[
        'id' => 81483, 'Acct' => 616, 'DateIssued' => '2018-03-05 10:28:02', 'OperType' => 2,
        'ObjectId' => 6, 'PartnerId' => 2, 'Amount' => 22.51, 'AmountVat' => 4.50,
        'PricesWithVat' => 0, 'NullVat' => 0, 'IsVat' => 1, 'PaymentTypeId' => 1,
        'DateUpdated' => '2018-03-05 10:28:02',
        'Items' => [
            ['ItemId' => 8814, 'Qtty' => 1.0, 'PriceIn' => 12.0, 'PriceOut' => 4.825, 'VatIn' => 2.4, 'VatOut' => 0.965, 'Discount' => 3.5, 'Sign' => -1, 'TaxValue' => 20],
            ['ItemId' => 7, 'Qtty' => 1.0, 'PriceIn' => 12.57, 'PriceOut' => 0.0, 'Sign' => -1, 'TaxValue' => 20],
        ],
    ]]);

    $operation = fakeMicroBg($http)->operations()->list(operationType: OperationType::Sale)->first();

    expect($operation->id)->toBe(81483)
        ->and($operation->acct)->toBe(616)
        ->and($operation->amount)->toBe(22.51)
        ->and($operation->pricesWithVat)->toBeFalse()
        ->and($operation->isVat)->toBeTrue()
        ->and($operation->lines)->toHaveCount(2)
        ->and($operation->lines[0]->itemId)->toBe(8814)
        ->and($operation->lines[0]->discount)->toBe(3.5)
        ->and($operation->lines[0]->sign)->toBe(-1)
        ->and($operation->lines[1]->priceIn)->toBe(12.57);
});

it('saves a sale with lines and a payment', function () {
    $http = microBgOk(['id' => 82633, 'Acct' => 816, 'Amount' => 8.33, 'ExtAppDocId' => 326573]);

    $result = fakeMicroBg($http)->operations()->save(new OperationDocumentInput(
        operationType: OperationType::Sale,
        objectId: 6,
        lines: [
            new OperationLineInput(itemId: 8814, qtty: 1.0, price: 12.0),
            new OperationLineInput(itemId: 8815, qtty: 1.0, price: 4.0),
        ],
        partnerId: 2,
        pricesWithVat: true,
        paymentTypeId: 12344,
        extAppDocId: 326573,
        payments: [new PaymentEntryInput(amount: 16.00, paymentTypeId: 1345, date: '2018-08-01 12:45:28')],
    ));

    expect($result->extAppDocId)->toBe(326573)
        ->and(microBgPayload($http))->toBe([
            'functionName' => 'saveOperation',
            'parameters' => [],
            'functionData' => [
                'OperType' => 2,
                'ObjectId' => 6,
                'Items' => [
                    ['ItemId' => 8814, 'Qtty' => 1.0, 'Price' => 12.0],
                    ['ItemId' => 8815, 'Qtty' => 1.0, 'Price' => 4.0],
                ],
                'PartnerId' => 2,
                'PricesWithVat' => 1,
                'PaymentTypeId' => 12344,
                'ExtAppDocId' => 326573,
                'Payments' => [
                    ['Amount' => 16.00, 'PaymentTypeId' => 1345, 'Date' => '2018-08-01 12:45:28'],
                ],
            ],
        ]);
});

it('keys the save on the external id when asked', function () {
    $http = microBgOk(['id' => 1]);

    fakeMicroBg($http)->operations()->save(new OperationDocumentInput(
        operationType: OperationType::Sale,
        objectId: 6,
        lines: [new OperationLineInput(itemId: 1, qtty: 1.0)],
        extAppDocId: 999,
    ), byExtAppDocId: true);

    expect(microBgPayload($http)['parameters'])->toBe(['ByExtAppDocId' => 1]);
});

it('refuses an idempotent save without an external id', function () {
    $input = new OperationDocumentInput(
        operationType: OperationType::Sale,
        objectId: 6,
        lines: [new OperationLineInput(itemId: 1, qtty: 1.0)],
    );

    expect(fn () => fakeMicroBg(microBgOk(['id' => 1]))->operations()->save($input, byExtAppDocId: true))
        ->toThrow(ConfigurationException::class, 'byExtAppDocId requires the input to carry an extAppDocId');
});

it('deletes an operation by either id', function (array $args, array $parameters) {
    $http = microBgOk(null);

    fakeMicroBg($http)->operations()->delete(...$args);

    expect(microBgPayload($http)['parameters'])->toBe($parameters);
})->with([
    [['id' => 3456], ['Id' => 3456, 'DeleteRelatedOperations' => 0]],
    [['extAppDocId' => 345667], ['ExtAppDocId' => 345667, 'DeleteRelatedOperations' => 0]],
    [['id' => 3456, 'deleteRelated' => true], ['Id' => 3456, 'DeleteRelatedOperations' => 1]],
]);

it('refuses to delete without any identifier', function () {
    expect(fn () => fakeMicroBg(microBgOk(null))->operations()->delete())
        ->toThrow(ConfigurationException::class, 'either an id or an extAppDocId');
});

it('allocates a cost over a delivery', function () {
    $http = microBgOk(['id' => 234998, 'Acct' => 816, 'Amount' => 134.33]);

    $result = fakeMicroBg($http)->operations()->allocateCost(30.204, acct: 22);

    expect($result->id)->toBe(234998)
        ->and(microBgPayload($http))->toBe([
            'functionName' => 'createCostAllocation',
            // Rounded to two places, as the API requires.
            'parameters' => ['CostAllocationValue' => 30.2, 'Acct' => 22, 'CostAllocationMethod' => 1],
            'functionData' => null,
        ]);
});

it('refuses to allocate a cost without a target delivery', function () {
    expect(fn () => fakeMicroBg(microBgOk([]))->operations()->allocateCost(10.0))
        ->toThrow(ConfigurationException::class, 'either an acct or an id');
});
