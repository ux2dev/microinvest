<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Items;

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;

/**
 * One extra code or barcode attached to an item, with the quantity ratio
 * between its measure and the item's base measure. micro.bg only.
 */
final class ItemAddCodeResult implements FromMicroBg
{
    public function __construct(
        public readonly ?int $measureId = null,
        public readonly ?string $code = null,
        public readonly ?int $codeType = null,
        public readonly ?float $ratio = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static
    {
        return new self(
            measureId: isset($data['MeasureId']) ? (int) $data['MeasureId'] : null,
            code: isset($data['Code']) ? (string) $data['Code'] : null,
            codeType: isset($data['CodeType']) ? (int) $data['CodeType'] : null,
            ratio: isset($data['Ratio']) ? (float) $data['Ratio'] : null,
        );
    }
}
