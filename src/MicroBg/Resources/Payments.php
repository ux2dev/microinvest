<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg\Resources;

use Ux2Dev\Microinvest\Dto\Input\Payments\PaymentEntryInput;
use Ux2Dev\Microinvest\Dto\Result\Payments\PaymentReceiptResult;
use Ux2Dev\Microinvest\Dto\Result\Payments\PaymentTypeResult;
use Ux2Dev\Microinvest\Exception\ConfigurationException;
use Ux2Dev\Microinvest\Http\ResultList;

final class Payments extends Resource
{
    /** @return ResultList<PaymentTypeResult> */
    public function types(): ResultList
    {
        return $this->transport->callList('getPaymentTypes', [], PaymentTypeResult::class);
    }

    /**
     * Record a payment against an operation, identified either by its micro.bg
     * id or by the external application's own id. ExtAppDocId wins when both
     * are given. micro.bg only.
     */
    public function add(
        PaymentEntryInput $payment,
        ?int $operationId = null,
        ?int $extAppDocId = null,
    ): PaymentReceiptResult {
        if ($operationId === null && $extAppDocId === null) {
            throw new ConfigurationException('adding a payment needs either an operationId or an extAppDocId');
        }

        return $this->transport->callOne(
            'addPayment',
            ['OperationId' => $operationId, 'ExtAppDocId' => $extAppDocId],
            $payment->toMicroBgArray(),
            PaymentReceiptResult::class,
        );
    }
}
