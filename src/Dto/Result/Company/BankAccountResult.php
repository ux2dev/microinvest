<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Company;

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;

/**
 * One bank account configured on the micro.bg account. Its id is what an
 * invoice references through BankAccountId. micro.bg only.
 */
final class BankAccountResult implements FromMicroBg
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $bankName = null,
        public readonly ?string $bic = null,
        public readonly ?string $iban = null,
        public readonly ?string $currencyCode = null,
        public readonly ?bool $deleted = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static
    {
        return new self(
            // PDF v1.4's example omits an id, but createInvoice's BankAccountId
            // has to come from somewhere; read it when present.
            id: isset($data['id']) ? (int) $data['id'] : null,
            bankName: isset($data['BankName']) ? (string) $data['BankName'] : null,
            bic: isset($data['Bic']) ? (string) $data['Bic'] : null,
            iban: isset($data['Iban']) ? (string) $data['Iban'] : null,
            currencyCode: isset($data['CurrencyCode']) ? (string) $data['CurrencyCode'] : null,
            deleted: isset($data['Deleted']) ? (bool) $data['Deleted'] : null,
        );
    }
}
