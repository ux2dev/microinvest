<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Input\Operations;

use Ux2Dev\Microinvest\Contracts\Dto\ToMicroBg;

/**
 * One item line of a micro.bg operation. micro.bg only.
 */
final readonly class OperationLineInput implements ToMicroBg
{
    public function __construct(
        public int $itemId,
        /** Always in the item's base measure. */
        public float $qtty,
        /** With or without VAT depending on the document's pricesWithVat, before discount. */
        public ?float $price = null,
        /** Percentage. */
        public ?float $discount = null,
        public ?string $note = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toMicroBgArray(): array
    {
        $out = ['ItemId' => $this->itemId, 'Qtty' => $this->qtty];
        if ($this->price !== null) $out['Price'] = $this->price;
        if ($this->discount !== null) $out['Discount'] = $this->discount;
        if ($this->note !== null) $out['Note'] = $this->note;

        return $out;
    }
}
