<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Input\Operations;

use Ux2Dev\Microinvest\Contracts\Dto\ToMicroBg;
use Ux2Dev\Microinvest\Dto\Input\Payments\PaymentEntryInput;
use Ux2Dev\Microinvest\Enum\OperationType;

/**
 * A micro.bg operation to create or edit through saveOperation. micro.bg only.
 *
 * Only operations created by this same external application (same API id) can
 * be edited. Setting extAppDocId and calling saveOperation with
 * byExtAppDocId makes the write idempotent: the first call creates, later ones
 * update the same document.
 */
final readonly class OperationDocumentInput implements ToMicroBg
{
    /**
     * @param list<OperationLineInput>  $lines
     * @param list<PaymentEntryInput>   $payments
     * @param list<int>                 $relatedSourceOperationIds  supplier claims only
     */
    public function __construct(
        /** 2 sale, 19 order, 34 customer claim, 1 delivery, 11 write-off, 12 delivery request, 26 debit note, 27 credit note, 39 supplier claim. */
        public OperationType $operationType,
        public int $objectId,
        public array $lines,
        /** Required except on credit/debit notes, where the source invoice decides. */
        public ?int $partnerId = null,
        /** 0 or null creates, a positive value edits. */
        public ?int $id = null,
        public ?string $dateIssued = null,
        public ?bool $pricesWithVat = null,
        public ?bool $nullVat = null,
        public ?string $note = null,
        public ?string $dealConditions = null,
        public ?string $opCode = null,
        public ?int $paymentTypeId = null,
        public ?int $extAppDocId = null,
        /** Required on credit and debit notes: the invoice number they amend. */
        public ?string $relatedInvoiceNr = null,
        /** Credit and debit notes: true when the note only moves value, not stock. */
        public ?bool $noteByValue = null,
        public array $relatedSourceOperationIds = [],
        public ?string $dueDate = null,
        /** Deliveries only. */
        public ?string $deliveryInvoiceNumber = null,
        /** Deliveries only. */
        public ?string $deliveryInvoiceDate = null,
        public array $payments = [],
    ) {
    }

    /** @return array<string, mixed> */
    public function toMicroBgArray(): array
    {
        $out = [
            'OperType' => $this->operationType->value,
            'ObjectId' => $this->objectId,
            'Items' => array_map(
                static fn (OperationLineInput $line): array => $line->toMicroBgArray(),
                $this->lines,
            ),
        ];

        if ($this->id !== null) $out['id'] = $this->id;
        if ($this->partnerId !== null) $out['PartnerId'] = $this->partnerId;
        if ($this->dateIssued !== null) $out['DateIssued'] = $this->dateIssued;
        if ($this->pricesWithVat !== null) $out['PricesWithVat'] = $this->pricesWithVat ? 1 : 0;
        if ($this->nullVat !== null) $out['NullVat'] = $this->nullVat ? 1 : 0;
        if ($this->note !== null) $out['Note'] = $this->note;
        if ($this->dealConditions !== null) $out['DealConditions'] = $this->dealConditions;
        if ($this->opCode !== null) $out['OpCode'] = $this->opCode;
        if ($this->paymentTypeId !== null) $out['PaymentTypeId'] = $this->paymentTypeId;
        if ($this->extAppDocId !== null) $out['ExtAppDocId'] = $this->extAppDocId;
        if ($this->relatedInvoiceNr !== null) $out['RelatedInvoiceNr'] = $this->relatedInvoiceNr;
        if ($this->noteByValue !== null) $out['NoteByValue'] = $this->noteByValue ? 1 : 0;
        if ($this->relatedSourceOperationIds !== []) $out['RelatedSourceOperationIds'] = $this->relatedSourceOperationIds;
        if ($this->dueDate !== null) $out['DueDate'] = $this->dueDate;
        if ($this->deliveryInvoiceNumber !== null) $out['DeliveryInvoiceNumber'] = $this->deliveryInvoiceNumber;
        if ($this->deliveryInvoiceDate !== null) $out['DeliveryInvoiceDate'] = $this->deliveryInvoiceDate;

        if ($this->payments !== []) {
            $out['Payments'] = array_map(
                static fn (PaymentEntryInput $payment): array => $payment->toMicroBgArray(),
                $this->payments,
            );
        }

        return $out;
    }
}
