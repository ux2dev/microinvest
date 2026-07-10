<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Input\Payments;

/**
 * Input DTO for POST /Payment (add a payment to an existing operation).
 * Only non-null properties are sent on the wire.
 */
final readonly class PaymentInput
{
    public function __construct(
        public ?int $operationType = null,
        public ?int $documentNumber = null,
        public ?int $partnerId = null,
        public ?float $qtty = null,
        public ?int $mode = null,
        public ?int $sign = null,
        public ?string $date = null,
        public ?int $userId = null,
        public ?int $objectId = null,
        public ?int $paymentType = null,
        public ?string $transactionNumber = null,
        public ?string $endDate = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [];
        if ($this->operationType !== null) $out['operation_type'] = $this->operationType;
        if ($this->documentNumber !== null) $out['document_number'] = $this->documentNumber;
        if ($this->partnerId !== null) $out['partner_id'] = $this->partnerId;
        if ($this->qtty !== null) $out['qtty'] = $this->qtty;
        if ($this->mode !== null) $out['mode'] = $this->mode;
        if ($this->sign !== null) $out['sign'] = $this->sign;
        if ($this->date !== null) $out['date'] = $this->date;
        if ($this->userId !== null) $out['user_id'] = $this->userId;
        if ($this->objectId !== null) $out['object_id'] = $this->objectId;
        if ($this->paymentType !== null) $out['payment_type'] = $this->paymentType;
        if ($this->transactionNumber !== null) $out['transaction_number'] = $this->transactionNumber;
        if ($this->endDate !== null) $out['end_date'] = $this->endDate;
        return $out;
    }
}
