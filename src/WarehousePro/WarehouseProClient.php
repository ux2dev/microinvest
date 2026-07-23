<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\WarehousePro;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ux2Dev\Microinvest\Contracts\Client;
use Ux2Dev\Microinvest\Dto\Result\Locations\LocationResult;
use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Dto\Result\Payments\PaymentTypeResult;
use Ux2Dev\Microinvest\Dto\Result\Store\StoreResult;
use Ux2Dev\Microinvest\Dto\Result\VatGroups\VatGroupResult;
use Ux2Dev\Microinvest\Http\ResultList;
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
final class WarehouseProClient implements Client
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

    /** @return ResultList<NomenclatureGroupResult> */
    public function listItemGroups(): ResultList
    {
        return $this->items()->groups();
    }

    /** @return ResultList<NomenclatureGroupResult> */
    public function listPartnerGroups(): ResultList
    {
        return $this->partners()->groups();
    }

    /** @return ResultList<VatGroupResult> */
    public function listTaxGroups(): ResultList
    {
        return $this->vatGroups()->list();
    }

    /** @return ResultList<PaymentTypeResult> */
    public function listPaymentTypes(): ResultList
    {
        return $this->payments()->types();
    }

    /** @return ResultList<LocationResult> */
    public function listObjects(): ResultList
    {
        return $this->locations()->list();
    }

    /** @return ResultList<StoreResult> */
    public function listQuantities(?int $objectId = null): ResultList
    {
        return $this->store()->list(objectId: $objectId);
    }
}
