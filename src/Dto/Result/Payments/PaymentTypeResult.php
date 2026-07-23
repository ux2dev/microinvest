<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Payments;

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;
use Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro;
use Ux2Dev\Microinvest\Enum\FiscalMode;
use Ux2Dev\Microinvest\Enum\PaymentMethod;

/**
 * A payment type row (table paymenttypes).
 */
final class PaymentTypeResult implements FromWarehousePro, FromMicroBg
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly ?PaymentMethod $paymentMethod,
        /** micro.bg only: 1 fiscal receipt, 2 non-fiscal receipt, 3 print nothing. */
        public readonly ?FiscalMode $fiscalMode = null,
        /** micro.bg only. */
        public readonly ?bool $deleted = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            name: isset($data['Name']) ? (string) $data['Name'] : null,
            paymentMethod: isset($data['PaymentMethod']) ? PaymentMethod::tryFrom((int) $data['PaymentMethod']) : null,
            fiscalMode: isset($data['FiscalMode']) ? FiscalMode::tryFrom((int) $data['FiscalMode']) : null,
            deleted: isset($data['Deleted']) ? (bool) $data['Deleted'] : null,
        );
    }

    /** @param array<string, mixed> $data */
    public static function fromWarehousePro(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            paymentMethod: isset($data['payment_method']) ? PaymentMethod::tryFrom((int) $data['payment_method']) : null,
        );
    }
}
