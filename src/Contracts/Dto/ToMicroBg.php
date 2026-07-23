<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Contracts\Dto;

/**
 * An Input DTO that can render itself as a micro.bg `functionData` object.
 *
 * Null properties are omitted. Properties this dialect has no field for are
 * dropped silently — the shared Input DTOs are a union of both backends.
 */
interface ToMicroBg
{
    /** @return array<string, mixed> */
    public function toMicroBgArray(): array;
}
