<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Contracts\Dto;

/**
 * A Result DTO that can hydrate itself from a Warehouse Pro REST row, whose
 * wire keys are snake_case.
 */
interface FromWarehousePro
{
    /** @param array<string, mixed> $data */
    public static function fromWarehousePro(array $data): static;
}
