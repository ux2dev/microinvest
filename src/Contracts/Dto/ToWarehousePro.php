<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Contracts\Dto;

/**
 * An Input DTO that can render itself as a Warehouse Pro REST request body,
 * using snake_case wire keys. Null properties are omitted.
 */
interface ToWarehousePro
{
    /** @return array<string, mixed> */
    public function toWarehouseProArray(): array;
}
