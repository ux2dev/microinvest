<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg;

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
use Ux2Dev\Microinvest\MicroBg\Resources\Company;
use Ux2Dev\Microinvest\MicroBg\Resources\Groups;
use Ux2Dev\Microinvest\MicroBg\Resources\Invoices;
use Ux2Dev\Microinvest\MicroBg\Resources\Items;
use Ux2Dev\Microinvest\MicroBg\Resources\Objects;
use Ux2Dev\Microinvest\MicroBg\Resources\Operations;
use Ux2Dev\Microinvest\MicroBg\Resources\Partners;
use Ux2Dev\Microinvest\MicroBg\Resources\Payments;
use Ux2Dev\Microinvest\MicroBg\Resources\TaxGroups;

/**
 * Client for the micro.bg External App API — the hosted Microinvest service.
 *
 * One instance per registered external application. Methods that exist only
 * here (item prices, additional item codes, group writes) hang off the concrete
 * resources rather than off Contracts\Client.
 */
final class MicroBgClient implements Client
{
    public readonly MicroBgTransport $transport;

    private ?Partners $partners = null;
    private ?Items $items = null;
    private ?Groups $groups = null;
    private ?TaxGroups $taxGroups = null;
    private ?Payments $payments = null;
    private ?Objects $objects = null;
    private ?Operations $operations = null;
    private ?Invoices $invoices = null;
    private ?Company $company = null;

    public function __construct(
        MicroBgConfig $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
    ) {
        $this->transport = new MicroBgTransport($config, $httpClient, $requestFactory, $streamFactory);
    }

    public function partners(): Partners
    {
        return $this->partners ??= new Partners($this->transport);
    }

    public function items(): Items
    {
        return $this->items ??= new Items($this->transport);
    }

    public function groups(): Groups
    {
        return $this->groups ??= new Groups($this->transport);
    }

    public function taxGroups(): TaxGroups
    {
        return $this->taxGroups ??= new TaxGroups($this->transport);
    }

    public function payments(): Payments
    {
        return $this->payments ??= new Payments($this->transport);
    }

    public function objects(): Objects
    {
        return $this->objects ??= new Objects($this->transport);
    }

    public function operations(): Operations
    {
        return $this->operations ??= new Operations($this->transport);
    }

    public function invoices(): Invoices
    {
        return $this->invoices ??= new Invoices($this->transport);
    }

    public function company(): Company
    {
        return $this->company ??= new Company($this->transport);
    }

    /** @return ResultList<NomenclatureGroupResult> */
    public function listItemGroups(): ResultList
    {
        return $this->groups()->list('Items');
    }

    /** @return ResultList<NomenclatureGroupResult> */
    public function listPartnerGroups(): ResultList
    {
        return $this->groups()->list('Partners');
    }

    /** @return ResultList<VatGroupResult> */
    public function listTaxGroups(): ResultList
    {
        return $this->taxGroups()->list();
    }

    /** @return ResultList<PaymentTypeResult> */
    public function listPaymentTypes(): ResultList
    {
        return $this->payments()->types();
    }

    /** @return ResultList<LocationResult> */
    public function listObjects(): ResultList
    {
        return $this->objects()->list();
    }

    /** @return ResultList<StoreResult> */
    public function listQuantities(?int $objectId = null): ResultList
    {
        return $this->items()->quantities($objectId);
    }
}
