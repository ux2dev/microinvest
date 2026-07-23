<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ux2Dev\Microinvest\WarehousePro\WarehouseProClient;
use Ux2Dev\Microinvest\WarehousePro\WarehouseProConfig;

/**
 * Entry point for the SDK. Each backend has its own client; this class only
 * exists so both are discoverable from one place. Constructing the clients
 * directly is equally supported.
 */
final class Microinvest
{
    public static function warehousePro(
        WarehouseProConfig $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
    ): WarehouseProClient {
        return new WarehouseProClient($config, $httpClient, $requestFactory, $streamFactory);
    }
}
