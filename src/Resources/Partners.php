<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Resources;

use Ux2Dev\Microinvest\Dto\Input\Partners\PartnerInput;
use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Dto\Result\Partners\PartnerResult;
use Ux2Dev\Microinvest\Http\ResultList;

final class Partners extends Resource
{
    /** @return ResultList<PartnerResult> */
    public function list(
        ?string $company = null,
        ?string $code = null,
        ?int $groupId = null,
        ?string $vatId = null,
        ?int $type = null,
        ?int $page = null,
        ?int $pageSize = null,
        array $filters = [],
    ): ResultList {
        return $this->transport->requestList('GET', '/Partners', array_merge([
            'company' => $company,
            'code' => $code,
            'group_id' => $groupId,
            'vat_id' => $vatId,
            'type' => $type,
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
        return $this->transport->requestOne('POST', '/Partner', [], $input->toArray(), PartnerResult::class);
    }

    public function update(int $id, PartnerInput $input): PartnerResult
    {
        return $this->transport->requestOne('PUT', '/Partner', ['id' => $id], $input->toArray(), PartnerResult::class);
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
