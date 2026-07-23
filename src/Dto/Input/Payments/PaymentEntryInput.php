<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Input\Payments;

use Ux2Dev\Microinvest\Contracts\Dto\ToMicroBg;

/**
 * A payment made against a micro.bg operation, either inline on saveOperation
 * or on its own through addPayment. micro.bg only.
 */
final readonly class PaymentEntryInput implements ToMicroBg
{
    public function __construct(
        /** Always in the account's base currency. */
        public float $amount,
        /** Id from getPaymentTypes(). */
        public int $paymentTypeId,
        /** 'Y-m-d H:i:s'. */
        public ?string $date = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toMicroBgArray(): array
    {
        $out = ['Amount' => $this->amount, 'PaymentTypeId' => $this->paymentTypeId];
        if ($this->date !== null) $out['Date'] = $this->date;

        return $out;
    }
}
