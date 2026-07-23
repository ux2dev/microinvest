<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Users;

use Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro;

/**
 * A user row (table users).
 */
final class UserResult implements FromWarehousePro
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $code,
        public readonly ?string $name,
        public readonly ?string $name2,
        public readonly ?bool $isVeryUsed,
        public readonly ?int $groupId,
        public readonly ?int $userlevel,
        public readonly ?bool $deleted,
        public readonly ?string $cardNumber,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromWarehousePro(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            code: isset($data['code']) ? (string) $data['code'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            name2: isset($data['name2']) ? (string) $data['name2'] : null,
            isVeryUsed: isset($data['is_very_used']) ? (bool) $data['is_very_used'] : null,
            groupId: isset($data['group_id']) ? (int) $data['group_id'] : null,
            userlevel: isset($data['userlevel']) ? (int) $data['userlevel'] : null,
            deleted: isset($data['deleted']) ? (bool) $data['deleted'] : null,
            cardNumber: isset($data['card_number']) ? (string) $data['card_number'] : null,
        );
    }
}
