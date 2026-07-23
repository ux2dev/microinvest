<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg\Resources;

use Ux2Dev\Microinvest\Dto\Result\Payments\PaymentTypeResult;
use Ux2Dev\Microinvest\Http\ResultList;

final class Payments extends Resource
{
    /** @return ResultList<PaymentTypeResult> */
    public function types(): ResultList
    {
        return $this->transport->callList('getPaymentTypes', [], PaymentTypeResult::class);
    }
}
