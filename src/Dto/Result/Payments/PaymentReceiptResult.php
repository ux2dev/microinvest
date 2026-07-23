<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Payments;

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;

/**
 * A payment recorded against a micro.bg operation. micro.bg only: Warehouse
 * Pro's PaymentResult is a different, flatter shape.
 */
final class PaymentReceiptResult implements FromMicroBg
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?int $operationId = null,
        public readonly ?float $amount = null,
        public readonly ?int $paymentTypeId = null,
        public readonly ?string $date = null,
        public readonly ?string $dueDate = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            operationId: isset($data['OperationId']) ? (int) $data['OperationId'] : null,
            amount: isset($data['Amount']) ? (float) $data['Amount'] : null,
            paymentTypeId: isset($data['PaymentTypeId']) ? (int) $data['PaymentTypeId'] : null,
            date: isset($data['Date']) ? (string) $data['Date'] : null,
            dueDate: isset($data['DueDate']) ? (string) $data['DueDate'] : null,
        );
    }
}
