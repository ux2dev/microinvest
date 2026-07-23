<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Dto\Input\Documents\InvoiceInput;
use Ux2Dev\Microinvest\Dto\Input\Payments\PaymentEntryInput;
use Ux2Dev\Microinvest\Exception\ConfigurationException;

it('reads the company data', function () {
    // Shortened from Api_v1.4.pdf, section 1.1.
    $http = microBgOk([
        'CompanyName' => 'Примерен Доставчик ООД', 'CountryCode' => 'BG', 'City' => 'София, България',
        'currencyCode' => 'BGN', 'Address' => '1006, бул. Цар Борис III, 215',
        'ContactPerson' => 'Стефан Димитров Костов', 'Inn' => '6306211928', 'TaxNo' => 'BG6306211928',
        'PaymentToDate' => '2019-10-28 23:59:59', 'Percision' => 2, 'QntPercision' => 3,
        'PricesWithVat' => 1, 'AllowNegativeQnt' => 1, 'AutoProduction' => 1, 'IsVat' => 1,
    ]);

    $company = fakeMicroBg($http)->company()->get();

    expect($company->companyName)->toBe('Примерен Доставчик ООД')
        ->and($company->currencyCode)->toBe('BGN')
        ->and($company->precision)->toBe(2)
        ->and($company->qntPrecision)->toBe(3)
        ->and($company->pricesWithVat)->toBeTrue()
        ->and($company->allowNegativeQnt)->toBeTrue()
        ->and($company->paymentToDate)->toBe('2019-10-28 23:59:59')
        ->and(microBgPayload($http))->toBe([
            'functionName' => 'getCompanyData',
            'parameters' => [],
            'functionData' => null,
        ]);
});

it('reads the bank accounts', function () {
    $http = microBgOk([
        ['BankName' => 'Първа Източна Международна Банка', 'Bic' => 'PIB6734BGSF', 'Iban' => 'BG08FINV91501016706355', 'CurrencyCode' => 'BGN', 'Deleted' => 0],
        ['BankName' => 'Обединена Българска Банка', 'Bic' => 'OBB76787BGSF', 'Iban' => '6787OBB809909856767690009', 'CurrencyCode' => 'BGN', 'Deleted' => 1],
    ]);

    $accounts = fakeMicroBg($http)->company()->bankAccounts();

    expect($accounts)->toHaveCount(2)
        ->and($accounts->first()->bic)->toBe('PIB6734BGSF')
        ->and($accounts->first()->deleted)->toBeFalse()
        ->and($accounts->all()[1]->deleted)->toBeTrue()
        ->and(microBgPayload($http)['functionName'])->toBe('getBankAccounts');
});

it('issues an invoice for an operation', function () {
    $http = microBgOk([
        'id' => 104369,
        'DocNr' => '0000002356',
        'Date' => '2024-02-28',
        'InvoiceUrl' => 'https://micro.bg/createPDF/8de68fa7/Invoice-0000000029.pdf?forceDownload=1',
    ]);

    $invoice = fakeMicroBg($http)->invoices()->create(new InvoiceInput(
        operationId: 2093045,
        documentType: 1,
        date: '2024-02-28',
        bankAccountId: 195,
        dealPlace: 'Враца',
    ));

    expect($invoice->docNr)->toBe('0000002356')
        ->and($invoice->invoiceUrl)->toContain('.pdf')
        ->and(microBgPayload($http))->toBe([
            'functionName' => 'createInvoice',
            'parameters' => [],
            'functionData' => [
                'OperationId' => 2093045,
                'DocumentType' => 1,
                'Date' => '2024-02-28',
                'BankAccountId' => 195,
                'DealPlace' => 'Враца',
            ],
        ]);
});

it('issues an invoice keyed on the external id', function () {
    $http = microBgOk(['id' => 1, 'DocNr' => '1']);

    fakeMicroBg($http)->invoices()->create(new InvoiceInput(extAppDocId: 1234), byExtAppDocId: true);

    expect(microBgPayload($http)['parameters'])->toBe(['ByExtAppDocId' => 1])
        ->and(microBgPayload($http)['functionData'])->toBe(['ExtAppDocId' => 1234]);
});

it('refuses to issue a document without an operation', function () {
    expect(fn () => fakeMicroBg(microBgOk([]))->invoices()->create(new InvoiceInput(date: '2024-01-01')))
        ->toThrow(ConfigurationException::class, 'either an operationId or an extAppDocId');
});

it('adds a payment to an operation', function () {
    $http = microBgOk([
        'id' => 104369, 'OperationId' => 82654, 'Amount' => 1.20,
        'PaymentTypeId' => 3, 'Date' => '2018-08-01', 'DueDate' => '2018-08-06',
    ]);

    $receipt = fakeMicroBg($http)->payments()->add(
        new PaymentEntryInput(amount: 16.00, paymentTypeId: 1345, date: '2018-08-01 12:45:28'),
        operationId: 3456,
    );

    expect($receipt->operationId)->toBe(82654)
        ->and($receipt->dueDate)->toBe('2018-08-06')
        ->and(microBgPayload($http))->toBe([
            'functionName' => 'addPayment',
            'parameters' => ['OperationId' => 3456],
            'functionData' => ['Amount' => 16.00, 'PaymentTypeId' => 1345, 'Date' => '2018-08-01 12:45:28'],
        ]);
});

it('adds a payment keyed on the external id', function () {
    $http = microBgOk(['id' => 1]);

    fakeMicroBg($http)->payments()->add(new PaymentEntryInput(amount: 1.0, paymentTypeId: 1), extAppDocId: 3454456);

    expect(microBgPayload($http)['parameters'])->toBe(['ExtAppDocId' => 3454456]);
});

it('refuses to add a payment without an operation', function () {
    expect(fn () => fakeMicroBg(microBgOk([]))->payments()->add(new PaymentEntryInput(amount: 1.0, paymentTypeId: 1)))
        ->toThrow(ConfigurationException::class, 'either an operationId or an extAppDocId');
});

it('caches the micro.bg only resources too', function (string $accessor) {
    $client = fakeMicroBg(microBgOk([]));

    expect($client->{$accessor}())->toBe($client->{$accessor}());
})->with(['operations', 'invoices', 'company']);
