<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Input\Documents;

use Ux2Dev\Microinvest\Contracts\Dto\ToWarehousePro;

/**
 * Input DTO for POST /Document (add an invoice/document to an existing
 * operation). `invoiceNumber` / `externalInvoiceNumber` are strings to preserve
 * zero-padded formatting. Only non-null properties are sent on the wire.
 */
final readonly class DocumentInput implements ToWarehousePro
{
    public function __construct(
        public ?int $documentNumber = null,
        public ?string $invoiceNumber = null,
        public ?int $operationType = null,
        public ?string $invoiceDate = null,
        public ?int $documentType = null,
        public ?string $externalInvoiceDate = null,
        public ?string $externalInvoiceNumber = null,
        public ?int $paymentType = null,
        public ?string $receivedBy = null,
        public ?string $identityCard = null,
        public ?string $issuedBy = null,
        public ?string $taxDate = null,
        public ?string $transactionBasis = null,
        public ?string $description = null,
        public ?string $transactionPlace = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toWarehouseProArray(): array
    {
        $out = [];
        if ($this->documentNumber !== null) $out['document_number'] = $this->documentNumber;
        if ($this->invoiceNumber !== null) $out['invoice_number'] = $this->invoiceNumber;
        if ($this->operationType !== null) $out['operation_type'] = $this->operationType;
        if ($this->invoiceDate !== null) $out['invoice_date'] = $this->invoiceDate;
        if ($this->documentType !== null) $out['document_type'] = $this->documentType;
        if ($this->externalInvoiceDate !== null) $out['external_invoice_date'] = $this->externalInvoiceDate;
        if ($this->externalInvoiceNumber !== null) $out['external_invoice_number'] = $this->externalInvoiceNumber;
        if ($this->paymentType !== null) $out['payment_type'] = $this->paymentType;
        if ($this->receivedBy !== null) $out['received_by'] = $this->receivedBy;
        if ($this->identityCard !== null) $out['identity_card'] = $this->identityCard;
        if ($this->issuedBy !== null) $out['issued_by'] = $this->issuedBy;
        if ($this->taxDate !== null) $out['tax_date'] = $this->taxDate;
        if ($this->transactionBasis !== null) $out['transaction_basis'] = $this->transactionBasis;
        if ($this->description !== null) $out['description'] = $this->description;
        if ($this->transactionPlace !== null) $out['transaction_place'] = $this->transactionPlace;
        return $out;
    }
}
