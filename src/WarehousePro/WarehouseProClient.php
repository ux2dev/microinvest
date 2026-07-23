<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\WarehousePro;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ux2Dev\Microinvest\WarehousePro\Resources\Documents;
use Ux2Dev\Microinvest\WarehousePro\Resources\Items;
use Ux2Dev\Microinvest\WarehousePro\Resources\Locations;
use Ux2Dev\Microinvest\WarehousePro\Resources\Operations;
use Ux2Dev\Microinvest\WarehousePro\Resources\Partners;
use Ux2Dev\Microinvest\WarehousePro\Resources\Payments;
use Ux2Dev\Microinvest\WarehousePro\Resources\Store;
use Ux2Dev\Microinvest\WarehousePro\Resources\Users;
use Ux2Dev\Microinvest\WarehousePro\Resources\VatGroups;

/**
 * Client for the Microinvest Warehouse Pro REST API, exposed by the Microinvest
 * Utility Center on each on-premise host.
 *
 * Instantiate once per host with a PSR-18 client + PSR-17 factories, then
 * dispatch requests via the resource accessors ($client->items(), etc.).
 */
final class WarehouseProClient
{
    public readonly WarehouseProTransport $transport;

    private ?Items $items = null;
    private ?Partners $partners = null;
    private ?Users $users = null;
    private ?Locations $locations = null;
    private ?Operations $operations = null;
    private ?Store $store = null;
    private ?Payments $payments = null;
    private ?Documents $documents = null;
    private ?VatGroups $vatGroups = null;

    public function __construct(
        WarehouseProConfig $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
    ) {
        $this->transport = new WarehouseProTransport($config, $httpClient, $requestFactory, $streamFactory);
    }

    public function items(): Items
    {
        return $this->items ??= new Items($this->transport);
    }

    public function partners(): Partners
    {
        return $this->partners ??= new Partners($this->transport);
    }

    public function users(): Users
    {
        return $this->users ??= new Users($this->transport);
    }

    public function locations(): Locations
    {
        return $this->locations ??= new Locations($this->transport);
    }

    public function operations(): Operations
    {
        return $this->operations ??= new Operations($this->transport);
    }

    public function store(): Store
    {
        return $this->store ??= new Store($this->transport);
    }

    public function payments(): Payments
    {
        return $this->payments ??= new Payments($this->transport);
    }

    public function documents(): Documents
    {
        return $this->documents ??= new Documents($this->transport);
    }

    public function vatGroups(): VatGroups
    {
        return $this->vatGroups ??= new VatGroups($this->transport);
    }
}
