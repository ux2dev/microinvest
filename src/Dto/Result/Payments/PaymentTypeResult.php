<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Payments;

/**
 * A payment type row (table paymenttypes).
 */
final class PaymentTypeResult
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?int $paymentMethod,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            paymentMethod: isset($data['payment_method']) ? (int) $data['payment_method'] : null,
        );
    }
}
