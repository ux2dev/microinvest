<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Contracts\Dto;

/**
 * A Result DTO that can hydrate itself from a micro.bg row, whose wire keys are
 * PascalCase with three documented exceptions: `id`, `eMail` and `parentId`.
 */
interface FromMicroBg
{
    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static;
}
