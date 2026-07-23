<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Locations;

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;
use Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro;

/**
 * A location row (table objects).
 */
final class LocationResult implements FromWarehousePro, FromMicroBg
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $code,
        public readonly ?string $name,
        public readonly ?string $name2,
        public readonly ?int $priceGroup,
        public readonly ?bool $deleted,
        public readonly ?bool $isVeryUsed,
        public readonly ?int $groupId,
        /** micro.bg only: the physical address of the object. */
        public readonly ?string $address = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            code: null,
            name: isset($data['Name']) ? (string) $data['Name'] : null,
            name2: null,
            priceGroup: isset($data['PriceGroup']) ? (int) $data['PriceGroup'] : null,
            deleted: isset($data['Deleted']) ? (bool) $data['Deleted'] : null,
            isVeryUsed: null,
            groupId: null,
            address: isset($data['Address']) ? (string) $data['Address'] : null,
        );
    }

    /** @param array<string, mixed> $data */
    public static function fromWarehousePro(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            code: isset($data['code']) ? (string) $data['code'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            name2: isset($data['name2']) ? (string) $data['name2'] : null,
            priceGroup: isset($data['price_group']) ? (int) $data['price_group'] : null,
            deleted: isset($data['deleted']) ? (bool) $data['deleted'] : null,
            isVeryUsed: isset($data['is_very_used']) ? (bool) $data['is_very_used'] : null,
            groupId: isset($data['group_id']) ? (int) $data['group_id'] : null,
        );
    }
}
