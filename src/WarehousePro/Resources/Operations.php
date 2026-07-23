<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\WarehousePro\Resources;

use Ux2Dev\Microinvest\Dto\Input\Operations\OperationInput;
use Ux2Dev\Microinvest\Dto\Result\Operations\OperationResult;
use Ux2Dev\Microinvest\Http\ResultList;

final class Operations extends Resource
{
    /** @return ResultList<OperationResult> */
    public function list(
        ?int $operationType = null,
        ?int $objectId = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $documentFrom = null,
        ?int $documentTo = null,
        ?int $page = null,
        ?int $pageSize = null,
        array $filters = [],
    ): ResultList {
        return $this->transport->requestList('GET', '/Operations', array_merge([
            'operation_type' => $operationType,
            'object_id' => $objectId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'document_from' => $documentFrom,
            'document_to' => $documentTo,
            'page' => $page,
            'page_size' => $pageSize,
        ], $filters), OperationResult::class);
    }

    /** @return ResultList<OperationResult> */
    public function get(int $operationType, int $documentNumber): ResultList
    {
        return $this->transport->requestList('GET', '/Operation', [
            'operation_type' => $operationType,
            'document_number' => $documentNumber,
        ], OperationResult::class);
    }

    /**
     * Create a new operation. The endpoint accepts one or more rows and returns
     * the saved rows (with the assigned document_number).
     *
     * @param  list<OperationInput>  $rows
     * @return ResultList<OperationResult>
     */
    public function create(array $rows): ResultList
    {
        $body = array_map(
            static fn (OperationInput $row): array => $row->toWarehouseProArray(),
            array_values($rows),
        );

        return $this->transport->requestList('POST', '/Operation', [], OperationResult::class, $body);
    }
}
