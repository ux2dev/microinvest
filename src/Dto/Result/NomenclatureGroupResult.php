<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result;

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;
use Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro;

/**
 * A nomenclature group row, shared by the Items / Partners / Users / Locations
 * group endpoints (tables goodsgroups, partnersgroups, objectsgroups,
 * usersgroups).
 */
final class NomenclatureGroupResult implements FromWarehousePro, FromMicroBg
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $code,
        public readonly ?string $name,
        /** micro.bg only: 3-letter-per-level tree path, or '-1' for the service group. */
        public readonly ?string $path = null,
        /** micro.bg only. */
        public readonly ?int $parentId = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            code: null,
            name: isset($data['Name']) ? (string) $data['Name'] : null,
            path: isset($data['Path']) ? (string) $data['Path'] : null,
            parentId: isset($data['parentId']) ? (int) $data['parentId'] : null,
        );
    }

    /** @param array<string, mixed> $data */
    public static function fromWarehousePro(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            code: isset($data['code']) ? (string) $data['code'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
        );
    }
}
