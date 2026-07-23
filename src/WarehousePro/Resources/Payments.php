<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\WarehousePro\Resources;

use Ux2Dev\Microinvest\Dto\Input\Payments\PaymentInput;
use Ux2Dev\Microinvest\Dto\Result\Payments\PaymentResult;
use Ux2Dev\Microinvest\Dto\Result\Payments\PaymentTypeResult;
use Ux2Dev\Microinvest\Http\ResultList;

final class Payments extends Resource
{
    /** @return ResultList<PaymentResult> */
    public function list(
        ?int $operationType = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $documentFrom = null,
        ?int $documentTo = null,
        ?int $page = null,
        ?int $pageSize = null,
        array $filters = [],
    ): ResultList {
        return $this->transport->requestList('GET', '/Payments', array_merge([
            'operation_type' => $operationType,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'document_from' => $documentFrom,
            'document_to' => $documentTo,
            'page' => $page,
            'page_size' => $pageSize,
        ], $filters), PaymentResult::class);
    }

    /** @return ResultList<PaymentResult> */
    public function get(int $operationType, int $documentNumber): ResultList
    {
        return $this->transport->requestList('GET', '/Payment', [
            'operation_type' => $operationType,
            'document_number' => $documentNumber,
        ], PaymentResult::class);
    }

    /**
     * Add a payment to an existing operation. Returns all payments for the
     * document, including the created one.
     *
     * @return ResultList<PaymentResult>
     */
    public function create(PaymentInput $input): ResultList
    {
        return $this->transport->requestList('POST', '/Payment', [], PaymentResult::class, $input->toWarehouseProArray());
    }

    /** @return ResultList<PaymentTypeResult> */
    public function types(?int $page = null, ?int $pageSize = null): ResultList
    {
        return $this->transport->requestList('GET', '/PaymentTypes', [
            'page' => $page,
            'page_size' => $pageSize,
        ], PaymentTypeResult::class);
    }
}
