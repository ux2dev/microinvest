<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result;

/**
 * A nomenclature group row, shared by the Items / Partners / Users / Locations
 * group endpoints (tables goodsgroups, partnersgroups, objectsgroups,
 * usersgroups).
 */
final class NomenclatureGroupResult
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $code,
        public readonly ?string $name,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            code: isset($data['code']) ? (string) $data['code'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
        );
    }
}
