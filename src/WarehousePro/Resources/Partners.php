<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\WarehousePro\Resources;

use Ux2Dev\Microinvest\Contracts\PartnerRepository;
use Ux2Dev\Microinvest\Dto\Input\Partners\PartnerInput;
use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Dto\Result\Partners\PartnerResult;
use Ux2Dev\Microinvest\Enum\PartnerType;
use Ux2Dev\Microinvest\Http\ResultList;

final class Partners extends Resource implements PartnerRepository
{
    /** @return ResultList<PartnerResult> */
    public function list(
        ?string $company = null,
        ?string $code = null,
        ?int $groupId = null,
        ?string $vatId = null,
        ?PartnerType $type = null,
        ?int $page = null,
        ?int $pageSize = null,
        array $filters = [],
    ): ResultList {
        return $this->transport->requestList('GET', '/Partners', array_merge([
            'company' => $company,
            'code' => $code,
            'group_id' => $groupId,
            'vat_id' => $vatId,
            'type' => $type?->value,
            'page' => $page,
            'page_size' => $pageSize,
        ], $filters), PartnerResult::class);
    }

    public function get(int $id): PartnerResult
    {
        return $this->transport->requestOne('GET', '/Partner', ['id' => $id], null, PartnerResult::class);
    }

    public function create(PartnerInput $input): PartnerResult
    {
        return $this->transport->requestOne('POST', '/Partner', [], $input->toWarehouseProArray(), PartnerResult::class);
    }

    public function update(int $id, PartnerInput $input): PartnerResult
    {
        return $this->transport->requestOne('PUT', '/Partner', ['id' => $id], $input->toWarehouseProArray(), PartnerResult::class);
    }

    /**
     * Every partner, one page at a time.
     *
     * @return iterable<PartnerResult>
     */
    public function each(): iterable
    {
        $page = 1;

        while (true) {
            $result = $this->list(page: $page, pageSize: self::EACH_PAGE_SIZE);

            yield from $result->items;

            $totalPages = $result->totalPages;

            if ($totalPages === null || $page >= $totalPages || $result->count() === 0) {
                return;
            }

            $page++;
        }
    }

    /** @return ResultList<NomenclatureGroupResult> */
    public function groups(?int $page = null, ?int $pageSize = null): ResultList
    {
        return $this->transport->requestList('GET', '/PartnersGroups', [
            'page' => $page,
            'page_size' => $pageSize,
        ], NomenclatureGroupResult::class);
    }
}
