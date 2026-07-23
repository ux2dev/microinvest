<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Contracts;

use Ux2Dev\Microinvest\Dto\Result\Locations\LocationResult;
use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Dto\Result\Payments\PaymentTypeResult;
use Ux2Dev\Microinvest\Dto\Result\Store\StoreResult;
use Ux2Dev\Microinvest\Dto\Result\VatGroups\VatGroupResult;
use Ux2Dev\Microinvest\Http\ResultList;

/**
 * What both Microinvest backends can do. Anything only one of them supports
 * (users, printable documents, invoices, cost allocation, company data) lives
 * on the concrete client, deliberately outside this interface.
 *
 * Methods prefixed `list` perform a request; `partners()` and `items()` do not.
 */
interface Client
{
    public function partners(): PartnerRepository;

    public function items(): ItemRepository;

    /** @return ResultList<NomenclatureGroupResult> */
    public function listItemGroups(): ResultList;

    /** @return ResultList<NomenclatureGroupResult> */
    public function listPartnerGroups(): ResultList;

    /** @return ResultList<VatGroupResult> */
    public function listTaxGroups(): ResultList;

    /** @return ResultList<PaymentTypeResult> */
    public function listPaymentTypes(): ResultList;

    /** @return ResultList<LocationResult> */
    public function listObjects(): ResultList;

    /** @return ResultList<StoreResult> */
    public function listQuantities(?int $objectId = null): ResultList;
}
