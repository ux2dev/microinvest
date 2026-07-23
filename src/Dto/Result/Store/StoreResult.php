<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Store;

use Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro;

/**
 * A stock amount row (table store).
 */
final class StoreResult implements FromWarehousePro
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $objectId,
        public readonly ?string $goodId,
        public readonly ?float $qtty,
        public readonly ?float $price,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromWarehousePro(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            objectId: isset($data['object_id']) ? (int) $data['object_id'] : null,
            goodId: isset($data['good_id']) ? (string) $data['good_id'] : null,
            qtty: isset($data['qtty']) ? (float) $data['qtty'] : null,
            price: isset($data['price']) ? (float) $data['price'] : null,
        );
    }
}
