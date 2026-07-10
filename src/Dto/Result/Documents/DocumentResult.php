<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Documents;

/**
 * A document / invoice row (table documents).
 *
 * `invoiceNumber` and `externalInvoiceNumber` are kept as strings to preserve
 * the zero-padded formatting Microinvest uses for invoice numbers.
 */
final class DocumentResult
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $documentNumber,
        public readonly ?string $invoiceNumber,
        public readonly ?int $operationType,
        public readonly ?string $invoiceDate,
        public readonly ?int $documentType,
        public readonly ?string $externalInvoiceDate,
        public readonly ?string $externalInvoiceNumber,
        public readonly ?int $paymentType,
        public readonly ?string $receivedBy,
        public readonly ?string $identityCard,
        public readonly ?string $issuedBy,
        public readonly ?string $taxDate,
        public readonly ?string $transactionBasis,
        public readonly ?string $description,
        public readonly ?string $transactionPlace,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            documentNumber: isset($data['document_number']) ? (int) $data['document_number'] : null,
            invoiceNumber: isset($data['invoice_number']) ? (string) $data['invoice_number'] : null,
            operationType: isset($data['operation_type']) ? (int) $data['operation_type'] : null,
            invoiceDate: isset($data['invoice_date']) ? (string) $data['invoice_date'] : null,
            documentType: isset($data['document_type']) ? (int) $data['document_type'] : null,
            externalInvoiceDate: isset($data['external_invoice_date']) ? (string) $data['external_invoice_date'] : null,
            externalInvoiceNumber: isset($data['external_invoice_number']) ? (string) $data['external_invoice_number'] : null,
            paymentType: isset($data['payment_type']) ? (int) $data['payment_type'] : null,
            receivedBy: isset($data['received_by']) ? (string) $data['received_by'] : null,
            identityCard: isset($data['identity_card']) ? (string) $data['identity_card'] : null,
            issuedBy: isset($data['issued_by']) ? (string) $data['issued_by'] : null,
            taxDate: isset($data['tax_date']) ? (string) $data['tax_date'] : null,
            transactionBasis: isset($data['transaction_basis']) ? (string) $data['transaction_basis'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            transactionPlace: isset($data['transaction_place']) ? (string) $data['transaction_place'] : null,
        );
    }
}
