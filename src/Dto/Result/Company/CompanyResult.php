<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Company;

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;

/**
 * The company behind a micro.bg account, plus the settings an external
 * application has to respect when it sends or reads data. micro.bg only.
 */
final class CompanyResult implements FromMicroBg
{
    public function __construct(
        public readonly ?string $companyName = null,
        /** Whose legislation the account runs under: VAT rates, printable docs. */
        public readonly ?string $countryCode = null,
        public readonly ?string $city = null,
        /** Every price in the API is in this currency. */
        public readonly ?string $currencyCode = null,
        public readonly ?string $address = null,
        public readonly ?string $contactPerson = null,
        /** Tax number (Булстат). */
        public readonly ?string $inn = null,
        public readonly ?string $taxNo = null,
        /** Subscription paid until; past this date the API stops returning data. */
        public readonly ?string $paymentToDate = null,
        /** Decimal places prices are rounded to. */
        public readonly ?int $precision = null,
        /** Decimal places quantities are rounded to. */
        public readonly ?int $qntPrecision = null,
        public readonly ?bool $pricesWithVat = null,
        /** Whether selling below zero stock is allowed. */
        public readonly ?bool $allowNegativeQnt = null,
        public readonly ?bool $autoProduction = null,
        public readonly ?bool $isVat = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static
    {
        return new self(
            companyName: isset($data['CompanyName']) ? (string) $data['CompanyName'] : null,
            countryCode: isset($data['CountryCode']) ? (string) $data['CountryCode'] : null,
            city: isset($data['City']) ? (string) $data['City'] : null,
            // PDF v1.4 spells this one lower case while its neighbours are PascalCase.
            currencyCode: isset($data['currencyCode']) ? (string) $data['currencyCode'] : null,
            address: isset($data['Address']) ? (string) $data['Address'] : null,
            contactPerson: isset($data['ContactPerson']) ? (string) $data['ContactPerson'] : null,
            inn: isset($data['Inn']) ? (string) $data['Inn'] : null,
            taxNo: isset($data['TaxNo']) ? (string) $data['TaxNo'] : null,
            paymentToDate: isset($data['PaymentToDate']) ? (string) $data['PaymentToDate'] : null,
            // PDF v1.4 misspells the key as "Percision".
            precision: isset($data['Percision']) ? (int) $data['Percision'] : null,
            qntPrecision: isset($data['QntPercision']) ? (int) $data['QntPercision'] : null,
            pricesWithVat: isset($data['PricesWithVat']) ? (bool) $data['PricesWithVat'] : null,
            allowNegativeQnt: isset($data['AllowNegativeQnt']) ? (bool) $data['AllowNegativeQnt'] : null,
            autoProduction: isset($data['AutoProduction']) ? (bool) $data['AutoProduction'] : null,
            isVat: isset($data['IsVat']) ? (bool) $data['IsVat'] : null,
        );
    }
}
