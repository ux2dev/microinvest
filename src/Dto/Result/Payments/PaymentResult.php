<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Payments;

/**
 * A payment row (table payments).
 */
final class PaymentResult
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $operationType,
        public readonly ?int $documentNumber,
        public readonly ?int $partnerId,
        public readonly ?float $qtty,
        public readonly ?int $mode,
        public readonly ?int $sign,
        public readonly ?string $date,
        public readonly ?int $userId,
        public readonly ?int $objectId,
        public readonly ?string $userRealTime,
        public readonly ?int $paymentType,
        public readonly ?string $transactionNumber,
        public readonly ?string $endDate,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            operationType: isset($data['operation_type']) ? (int) $data['operation_type'] : null,
            documentNumber: isset($data['document_number']) ? (int) $data['document_number'] : null,
            partnerId: isset($data['partner_id']) ? (int) $data['partner_id'] : null,
            qtty: isset($data['qtty']) ? (float) $data['qtty'] : null,
            mode: isset($data['mode']) ? (int) $data['mode'] : null,
            sign: isset($data['sign']) ? (int) $data['sign'] : null,
            date: isset($data['date']) ? (string) $data['date'] : null,
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            objectId: isset($data['object_id']) ? (int) $data['object_id'] : null,
            userRealTime: isset($data['user_real_time']) ? (string) $data['user_real_time'] : null,
            paymentType: isset($data['payment_type']) ? (int) $data['payment_type'] : null,
            transactionNumber: isset($data['transaction_number']) ? (string) $data['transaction_number'] : null,
            endDate: isset($data['end_date']) ? (string) $data['end_date'] : null,
        );
    }
}
