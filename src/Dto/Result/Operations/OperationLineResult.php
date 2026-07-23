<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Operations;

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;
use Ux2Dev\Microinvest\Enum\StockSign;

/**
 * One item line of a micro.bg operation.
 *
 * micro.bg only: Warehouse Pro models an operation as one flat row per line
 * ({@see OperationResult}) rather than as a document with nested lines.
 */
final class OperationLineResult implements FromMicroBg
{
    public function __construct(
        public readonly ?int $itemId = null,
        public readonly ?float $qtty = null,
        /** Delivery price, excluding VAT. */
        public readonly ?float $priceIn = null,
        /** Sale price, excluding VAT, after the discount. */
        public readonly ?float $priceOut = null,
        public readonly ?float $vatIn = null,
        public readonly ?float $vatOut = null,
        public readonly ?float $discount = null,
        public readonly ?string $note = null,
        /** -1 the item leaves stock, 1 it enters stock. */
        public readonly ?StockSign $sign = null,
        public readonly ?float $taxValue = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static
    {
        return new self(
            itemId: isset($data['ItemId']) ? (int) $data['ItemId'] : null,
            qtty: isset($data['Qtty']) ? (float) $data['Qtty'] : null,
            priceIn: isset($data['PriceIn']) ? (float) $data['PriceIn'] : null,
            priceOut: isset($data['PriceOut']) ? (float) $data['PriceOut'] : null,
            vatIn: isset($data['VatIn']) ? (float) $data['VatIn'] : null,
            vatOut: isset($data['VatOut']) ? (float) $data['VatOut'] : null,
            discount: isset($data['Discount']) ? (float) $data['Discount'] : null,
            note: isset($data['Note']) ? (string) $data['Note'] : null,
            sign: isset($data['Sign']) ? StockSign::tryFrom((int) $data['Sign']) : null,
            taxValue: isset($data['TaxValue']) ? (float) $data['TaxValue'] : null,
        );
    }
}
