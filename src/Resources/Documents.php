<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Resources;

use Ux2Dev\Microinvest\Dto\Input\Documents\DocumentInput;
use Ux2Dev\Microinvest\Dto\Result\Documents\DocumentResult;
use Ux2Dev\Microinvest\Http\ResultList;

final class Documents extends Resource
{
    /** @return ResultList<DocumentResult> */
    public function list(
        ?string $dateFrom = null,
        ?string $dateTo = null,
        ?int $documentFrom = null,
        ?int $documentTo = null,
        ?string $invoiceDateFrom = null,
        ?string $invoiceDateTo = null,
        ?string $invoiceNumberFrom = null,
        ?string $invoiceNumberTo = null,
        ?int $page = null,
        ?int $pageSize = null,
        array $filters = [],
    ): ResultList {
        return $this->transport->requestList('GET', '/Documents', array_merge([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'document_from' => $documentFrom,
            'document_to' => $documentTo,
            'invoice_date_from' => $invoiceDateFrom,
            'invoice_date_to' => $invoiceDateTo,
            'invoice_number_from' => $invoiceNumberFrom,
            'invoice_number_to' => $invoiceNumberTo,
            'page' => $page,
            'page_size' => $pageSize,
        ], $filters), DocumentResult::class);
    }

    public function get(
        int $operationType,
        int $documentNumber,
        int $documentType,
        ?int $invoiceNumber = null,
    ): DocumentResult {
        return $this->transport->requestOne('GET', '/Document', [
            'operation_type' => $operationType,
            'document_number' => $documentNumber,
            'document_type' => $documentType,
            'invoice_number' => $invoiceNumber,
        ], null, DocumentResult::class);
    }

    /**
     * Add a document/invoice to an existing operation. Returns the full document
     * information. Throws {@see \Ux2Dev\Microinvest\Exception\ApiException} with
     * HTTP 409 if the operation already has a document.
     *
     * @return ResultList<DocumentResult>
     */
    public function create(DocumentInput $input): ResultList
    {
        return $this->transport->requestList('POST', '/Document', [], DocumentResult::class, $input->toWarehouseProArray());
    }
}
