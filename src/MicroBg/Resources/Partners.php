<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg\Resources;

use Ux2Dev\Microinvest\Contracts\PartnerRepository;
use Ux2Dev\Microinvest\Dto\Input\Partners\PartnerInput;
use Ux2Dev\Microinvest\Dto\Result\Partners\PartnerResult;
use Ux2Dev\Microinvest\Exception\ApiException;
use Ux2Dev\Microinvest\Http\ResultList;

final class Partners extends Resource implements PartnerRepository
{
    /**
     * @param  array<string, mixed>  $filters  raw wire keys, merged over the named arguments
     * @return ResultList<PartnerResult>
     */
    public function list(
        ?string $fromDate = null,
        ?int $fromId = null,
        ?int $limit = null,
        ?int $id = null,
        ?string $code = null,
        ?string $taxNo = null,
        ?string $email = null,
        ?string $phone = null,
        array $filters = [],
    ): ResultList {
        return $this->transport->callList('getPartners', array_merge([
            'fromDate' => $fromDate,
            'fromId' => $fromId,
            'limit' => $limit,
            'Id' => $id,
            'Code' => $code,
            'TaxNo' => $taxNo,
            'Email' => $email,
            'Phone' => $phone,
        ], $filters), PartnerResult::class);
    }

    public function get(int $id): PartnerResult
    {
        // micro.bg has no single-record endpoint; Id is a filter on the list.
        $partner = $this->list(id: $id)->first();

        if ($partner === null) {
            throw new ApiException("Partner {$id} was not found", httpStatus: 200);
        }

        return $partner;
    }

    /**
     * PDF v1.4 defaults AutoGenerateCode to 0, which makes the insert fail when
     * the code already exists. The SDK asks for a generated code instead; pass
     * false to get the documented behaviour.
     */
    public function create(PartnerInput $input, bool $autoGenerateCode = true): PartnerResult
    {
        return $this->transport->callOne(
            'insertPartner',
            ['AutoGenerateCode' => $autoGenerateCode ? 1 : 0],
            $input->toMicroBgArray(),
            PartnerResult::class,
        );
    }

    public function update(int $id, PartnerInput $input): PartnerResult
    {
        $data = $input->toMicroBgArray();
        $data['id'] = $id;

        return $this->transport->callOne('editPartner', [], $data, PartnerResult::class);
    }

    /**
     * Physical delete when the partner is unused, logical otherwise
     * (Deleted becomes 1). micro.bg only — Warehouse Pro exposes no delete.
     */
    public function delete(int $id): void
    {
        $this->transport->call('deletePartner', ['Id' => $id]);
    }

    /**
     * Every partner, walking the fromId cursor.
     *
     * @return iterable<PartnerResult>
     */
    public function each(): iterable
    {
        $fromId = 0;

        while (true) {
            $batch = $this->list(fromId: $fromId, limit: self::EACH_LIMIT);
            $cursor = $fromId;

            foreach ($batch as $partner) {
                yield $partner;

                if ($partner->id !== null && $partner->id > $cursor) {
                    $cursor = $partner->id;
                }
            }

            // A short page means the end; an unmoved cursor means the rows
            // carry no id, so advancing would loop forever.
            if ($batch->count() < self::EACH_LIMIT || $cursor === $fromId) {
                return;
            }

            $fromId = $cursor;
        }
    }
}
