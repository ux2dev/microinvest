<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Ux2Dev\Microinvest\Laravel\MicroinvestManager;

/**
 * @method static MicroinvestManager connection(string $name)
 * @method static string currentConnection()
 * @method static \Ux2Dev\Microinvest\Contracts\Client client()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Items items()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Partners partners()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Users users()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Locations locations()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Operations operations()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Store store()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Payments payments()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\Documents documents()
 * @method static \Ux2Dev\Microinvest\WarehousePro\Resources\VatGroups vatGroups()
 */
final class Microinvest extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MicroinvestManager::class;
    }
}
