<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg\Resources;

use Ux2Dev\Microinvest\Dto\Input\Operations\OperationDocumentInput;
use Ux2Dev\Microinvest\Dto\Result\Operations\OperationDocumentResult;
use Ux2Dev\Microinvest\Enum\OperationType;
use Ux2Dev\Microinvest\Exception\ConfigurationException;
use Ux2Dev\Microinvest\Http\ResultList;

/**
 * Sales, orders, deliveries, claims and notes. micro.bg only - Warehouse Pro
 * models operations as flat rows and is not interchangeable here.
 */
final class Operations extends Resource
{
    /**
     * @param  OperationType  $operationType  required by the API; see OperationDocumentResult
     * @param  array<string, mixed>  $filters
     * @return ResultList<OperationDocumentResult>
     */
    public function list(
        OperationType $operationType,
        ?string $fromDate = null,
        ?int $fromId = null,
        ?int $fromExtApiDocId = null,
        ?int $limit = null,
        ?int $id = null,
        ?int $extAppDocId = null,
        array $filters = [],
    ): ResultList {
        return $this->transport->callList('getOperations', array_merge([
            'OperType' => $operationType->value,
            'fromDate' => $fromDate,
            'fromId' => $fromId,
            'fromExtApiDocId' => $fromExtApiDocId,
            'limit' => $limit,
            'Id' => $id,
            'ExtAppDocId' => $extAppDocId,
        ], $filters), OperationDocumentResult::class);
    }

    /**
     * Create or edit an operation.
     *
     * With $byExtAppDocId the record is keyed on the input's extAppDocId, which
     * makes re-sending the same document safe: the first call creates it, later
     * ones update it. Only operations created by this same API id can be edited.
     */
    public function save(OperationDocumentInput $input, bool $byExtAppDocId = false): OperationDocumentResult
    {
        if ($byExtAppDocId && $input->extAppDocId === null) {
            throw new ConfigurationException('byExtAppDocId requires the input to carry an extAppDocId');
        }

        return $this->transport->callOne(
            'saveOperation',
            $byExtAppDocId ? ['ByExtAppDocId' => 1] : [],
            $input->toMicroBgArray(),
            OperationDocumentResult::class,
        );
    }

    /**
     * Delete by micro.bg id or by the external application's own id. Only
     * operations this application created can be deleted; ExtAppDocId wins when
     * both are given.
     */
    public function delete(?int $id = null, ?int $extAppDocId = null, bool $deleteRelated = false): void
    {
        if ($id === null && $extAppDocId === null) {
            throw new ConfigurationException('deleting an operation needs either an id or an extAppDocId');
        }

        $this->transport->call('deleteOperation', [
            'Id' => $id,
            'ExtAppDocId' => $extAppDocId,
            'DeleteRelatedOperations' => $deleteRelated ? 1 : 0,
        ]);
    }

    /**
     * Spread a cost over a delivery. Identify the delivery by its running
     * number (acct) or by its id; acct wins when both are given. The value is
     * always excluding VAT.
     *
     * @param  1|2  $method  1 by value, 2 by quantity
     */
    public function allocateCost(
        float $value,
        ?int $acct = null,
        ?int $id = null,
        int $method = 1,
        ?int $costAllocationId = null,
    ): OperationDocumentResult {
        if ($acct === null && $id === null) {
            throw new ConfigurationException('allocating a cost needs either an acct or an id');
        }

        return $this->transport->callOne('createCostAllocation', [
            'CostAllocationValue' => round($value, 2),
            'Acct' => $acct,
            'Id' => $id,
            'CostAllocationMethod' => $method,
            'CostAllocationId' => $costAllocationId,
        ], null, OperationDocumentResult::class);
    }
}
