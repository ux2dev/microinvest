<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Documents;

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;

/**
 * An invoice, credit note or debit note issued for an operation. micro.bg only.
 */
final class InvoiceResult implements FromMicroBg
{
    public function __construct(
        public readonly ?int $id = null,
        /** Zero-padded document number. */
        public readonly ?string $docNr = null,
        public readonly ?string $date = null,
        /** Where the rendered PDF can be downloaded. */
        public readonly ?string $invoiceUrl = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            docNr: isset($data['DocNr']) ? (string) $data['DocNr'] : null,
            date: isset($data['Date']) ? (string) $data['Date'] : null,
            invoiceUrl: isset($data['InvoiceUrl']) ? (string) $data['InvoiceUrl'] : null,
        );
    }
}
