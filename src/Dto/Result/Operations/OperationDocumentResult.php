<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Operations;

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;

/**
 * A micro.bg operation: a document header plus its item lines.
 *
 * micro.bg only. Operation types: 2 sale, 19 order, 34 customer claim,
 * 1 delivery, 11 write-off, 12 delivery request, 26 debit note,
 * 27 credit note, 39 supplier claim.
 */
final class OperationDocumentResult implements FromMicroBg
{
    /** @param list<OperationLineResult> $lines */
    public function __construct(
        public readonly ?int $id = null,
        /** Running document number. */
        public readonly ?int $acct = null,
        public readonly ?string $dateIssued = null,
        public readonly ?int $operationType = null,
        public readonly ?int $objectId = null,
        public readonly ?int $partnerId = null,
        /** Total excluding VAT. */
        public readonly ?float $amount = null,
        public readonly ?float $amountVat = null,
        public readonly ?bool $pricesWithVat = null,
        /** When true no VAT is charged on this operation. */
        public readonly ?bool $nullVat = null,
        /** Whether the company was VAT registered when the operation was created. */
        public readonly ?bool $isVat = null,
        public readonly ?string $note = null,
        public readonly ?string $dealConditions = null,
        public readonly ?string $opCode = null,
        public readonly ?string $dateUpdated = null,
        public readonly ?int $paymentTypeId = null,
        public readonly ?int $userId = null,
        public readonly ?string $dateCreated = null,
        /** The external application's own id for this operation. */
        public readonly ?int $extAppDocId = null,
        public readonly array $lines = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            acct: isset($data['Acct']) ? (int) $data['Acct'] : null,
            dateIssued: isset($data['DateIssued']) ? (string) $data['DateIssued'] : null,
            operationType: isset($data['OperType']) ? (int) $data['OperType'] : null,
            objectId: isset($data['ObjectId']) ? (int) $data['ObjectId'] : null,
            partnerId: isset($data['PartnerId']) ? (int) $data['PartnerId'] : null,
            amount: isset($data['Amount']) ? (float) $data['Amount'] : null,
            amountVat: isset($data['AmountVat']) ? (float) $data['AmountVat'] : null,
            pricesWithVat: isset($data['PricesWithVat']) ? (bool) $data['PricesWithVat'] : null,
            nullVat: isset($data['NullVat']) ? (bool) $data['NullVat'] : null,
            // PDF v1.4 documents IsVat as "1 - да, 2 -не" here, unlike the
            // 1/0 flags elsewhere; read as a plain boolean pending confirmation.
            isVat: isset($data['IsVat']) ? (bool) $data['IsVat'] : null,
            note: isset($data['Note']) ? (string) $data['Note'] : null,
            dealConditions: isset($data['DealConditions']) ? (string) $data['DealConditions'] : null,
            opCode: isset($data['OpCode']) ? (string) $data['OpCode'] : null,
            dateUpdated: isset($data['DateUpdated']) ? (string) $data['DateUpdated'] : null,
            paymentTypeId: isset($data['PaymentTypeId']) ? (int) $data['PaymentTypeId'] : null,
            userId: isset($data['UserId']) ? (int) $data['UserId'] : null,
            dateCreated: isset($data['DateCreated']) ? (string) $data['DateCreated'] : null,
            extAppDocId: isset($data['ExtAppDocId']) ? (int) $data['ExtAppDocId'] : null,
            lines: array_map(
                static fn (array $row): OperationLineResult => OperationLineResult::fromMicroBg($row),
                array_values(array_filter((array) ($data['Items'] ?? []), 'is_array')),
            ),
        );
    }
}
