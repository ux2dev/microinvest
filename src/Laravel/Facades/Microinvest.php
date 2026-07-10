<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Ux2Dev\Microinvest\Laravel\MicroinvestManager;

/**
 * @method static MicroinvestManager connection(string $name)
 * @method static string currentConnection()
 * @method static \Ux2Dev\Microinvest\Microinvest client()
 * @method static \Ux2Dev\Microinvest\Resources\Items items()
 * @method static \Ux2Dev\Microinvest\Resources\Partners partners()
 * @method static \Ux2Dev\Microinvest\Resources\Users users()
 * @method static \Ux2Dev\Microinvest\Resources\Locations locations()
 * @method static \Ux2Dev\Microinvest\Resources\Operations operations()
 * @method static \Ux2Dev\Microinvest\Resources\Store store()
 * @method static \Ux2Dev\Microinvest\Resources\Payments payments()
 * @method static \Ux2Dev\Microinvest\Resources\Documents documents()
 * @method static \Ux2Dev\Microinvest\Resources\VatGroups vatGroups()
 */
final class Microinvest extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MicroinvestManager::class;
    }
}
