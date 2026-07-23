<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Enum;

/**
 * How a group delete selects what to remove. micro.bg only.
 */
enum GroupDeleteMode: string
{
    case ById = 'ById';
    case ByPath = 'ByPath';
    case All = 'All';
}
