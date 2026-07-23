<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Input\Documents;

use Ux2Dev\Microinvest\Contracts\Dto\ToMicroBg;

/**
 * An invoice, credit note or debit note to issue for an existing micro.bg
 * operation. micro.bg only.
 *
 * Identify the operation either by its micro.bg id or by the external
 * application's own extAppDocId — not both.
 */
final readonly class InvoiceInput implements ToMicroBg
{
    public function __construct(
        public ?int $operationId = null,
        public ?int $extAppDocId = null,
        /** 1 invoice, 5 credit note, 15 debit note. Defaults to an invoice. */
        public ?int $documentType = null,
        public ?string $date = null,
        /** Date of the taxable event. */
        public ?string $dateEvent = null,
        public ?string $dueDate = null,
        /** Falls back to the user configured for the API application. */
        public ?string $compiler = null,
        /** Free text, e.g. the legal ground for charging no VAT. */
        public ?string $noVatReason = null,
        /** Id from getBankAccounts(), when the sale is to be paid by transfer. */
        public ?int $bankAccountId = null,
        /** Falls back to the supplier's city. */
        public ?string $dealPlace = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toMicroBgArray(): array
    {
        $out = [];
        if ($this->operationId !== null) $out['OperationId'] = $this->operationId;
        if ($this->extAppDocId !== null) $out['ExtAppDocId'] = $this->extAppDocId;
        if ($this->documentType !== null) $out['DocumentType'] = $this->documentType;
        if ($this->date !== null) $out['Date'] = $this->date;
        if ($this->dateEvent !== null) $out['DateEvent'] = $this->dateEvent;
        if ($this->dueDate !== null) $out['DueDate'] = $this->dueDate;
        if ($this->compiler !== null) $out['Compiler'] = $this->compiler;
        if ($this->noVatReason !== null) $out['NoVatReason'] = $this->noVatReason;
        if ($this->bankAccountId !== null) $out['BankAccountId'] = $this->bankAccountId;
        if ($this->dealPlace !== null) $out['DealPlace'] = $this->dealPlace;

        return $out;
    }
}
