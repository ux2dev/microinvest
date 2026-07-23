<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\VatGroups;

use Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro;

/**
 * A VAT group row (table vatgroups).
 */
final class VatGroupResult implements FromWarehousePro
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $code,
        public readonly ?string $name,
        public readonly ?float $vatValue,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromWarehousePro(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            code: isset($data['code']) ? (string) $data['code'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            vatValue: isset($data['vat_value']) ? (float) $data['vat_value'] : null,
        );
    }
}
