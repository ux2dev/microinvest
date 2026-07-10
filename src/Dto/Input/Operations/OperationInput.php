<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Input\Operations;

/**
 * Input DTO for a single row of a POST /Operation request. The endpoint takes
 * an array of these. Field usage depends on operation_type (e.g. transfers use
 * fromObjectId/toObjectId instead of objectId). Only non-null properties are
 * sent on the wire.
 */
final readonly class OperationInput
{
    public function __construct(
        public ?int $operationType = null,
        public ?string $goodId = null,
        public ?int $partnerId = null,
        public ?int $objectId = null,
        public ?int $fromObjectId = null,
        public ?int $toObjectId = null,
        public ?int $operatorId = null,
        public ?float $qtty = null,
        public ?float $priceIn = null,
        public ?float $priceOut = null,
        public ?int $currencyId = null,
        public ?float $discount = null,
        public ?string $date = null,
        public ?string $note = null,
        public ?int $userId = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [];
        if ($this->operationType !== null) $out['operation_type'] = $this->operationType;
        if ($this->goodId !== null) $out['good_id'] = $this->goodId;
        if ($this->partnerId !== null) $out['partner_id'] = $this->partnerId;
        if ($this->objectId !== null) $out['object_id'] = $this->objectId;
        if ($this->fromObjectId !== null) $out['from_object_id'] = $this->fromObjectId;
        if ($this->toObjectId !== null) $out['to_object_id'] = $this->toObjectId;
        if ($this->operatorId !== null) $out['operator_id'] = $this->operatorId;
        if ($this->qtty !== null) $out['qtty'] = $this->qtty;
        if ($this->priceIn !== null) $out['price_in'] = $this->priceIn;
        if ($this->priceOut !== null) $out['price_out'] = $this->priceOut;
        if ($this->currencyId !== null) $out['currency_id'] = $this->currencyId;
        if ($this->discount !== null) $out['discount'] = $this->discount;
        if ($this->date !== null) $out['date'] = $this->date;
        if ($this->note !== null) $out['note'] = $this->note;
        if ($this->userId !== null) $out['user_id'] = $this->userId;
        return $out;
    }
}
