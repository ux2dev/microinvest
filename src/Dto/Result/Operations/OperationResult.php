<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Operations;

use Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro;

/**
 * An operation row (table operations).
 */
final class OperationResult implements FromWarehousePro
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?int $operationType,
        public readonly ?int $documentNumber,
        public readonly ?string $goodId,
        public readonly ?int $partnerId,
        public readonly ?int $objectId,
        public readonly ?int $fromObjectId,
        public readonly ?int $toObjectId,
        public readonly ?int $operatorId,
        public readonly ?float $qtty,
        public readonly ?int $sign,
        public readonly ?float $priceIn,
        public readonly ?float $priceOut,
        public readonly ?float $vatIn,
        public readonly ?float $vatOut,
        public readonly ?float $discount,
        public readonly ?string $date,
        public readonly ?string $note,
        public readonly ?int $userId,
        public readonly ?string $userRealTime,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromWarehousePro(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            operationType: isset($data['operation_type']) ? (int) $data['operation_type'] : null,
            documentNumber: isset($data['document_number']) ? (int) $data['document_number'] : null,
            goodId: isset($data['good_id']) ? (string) $data['good_id'] : null,
            partnerId: isset($data['partner_id']) ? (int) $data['partner_id'] : null,
            objectId: isset($data['object_id']) ? (int) $data['object_id'] : null,
            fromObjectId: isset($data['from_object_id']) ? (int) $data['from_object_id'] : null,
            toObjectId: isset($data['to_object_id']) ? (int) $data['to_object_id'] : null,
            operatorId: isset($data['operator_id']) ? (int) $data['operator_id'] : null,
            qtty: isset($data['qtty']) ? (float) $data['qtty'] : null,
            sign: isset($data['sign']) ? (int) $data['sign'] : null,
            priceIn: isset($data['price_in']) ? (float) $data['price_in'] : null,
            priceOut: isset($data['price_out']) ? (float) $data['price_out'] : null,
            vatIn: isset($data['vat_in']) ? (float) $data['vat_in'] : null,
            vatOut: isset($data['vat_out']) ? (float) $data['vat_out'] : null,
            discount: isset($data['discount']) ? (float) $data['discount'] : null,
            date: isset($data['date']) ? (string) $data['date'] : null,
            note: isset($data['note']) ? (string) $data['note'] : null,
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            userRealTime: isset($data['user_real_time']) ? (string) $data['user_real_time'] : null,
        );
    }
}
