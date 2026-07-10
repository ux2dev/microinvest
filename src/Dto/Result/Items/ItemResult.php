<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Items;

/**
 * An item row (table goods).
 */
final class ItemResult
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $code,
        public readonly ?string $name,
        public readonly ?string $name2,
        public readonly ?string $barcode1,
        public readonly ?string $barcode2,
        public readonly ?string $barcode3,
        public readonly ?string $catalog1,
        public readonly ?string $catalog2,
        public readonly ?string $catalog3,
        public readonly ?string $measure1,
        public readonly ?string $measure2,
        public readonly ?float $ratio,
        public readonly ?float $priceIn,
        public readonly ?float $priceOut1,
        public readonly ?float $priceOut2,
        public readonly ?float $priceOut3,
        public readonly ?float $priceOut4,
        public readonly ?float $priceOut5,
        public readonly ?float $priceOut6,
        public readonly ?float $priceOut7,
        public readonly ?float $priceOut8,
        public readonly ?float $priceOut9,
        public readonly ?float $priceOut10,
        public readonly ?float $minQtty,
        public readonly ?float $normalQtty,
        public readonly ?string $description,
        public readonly ?int $type,
        public readonly ?bool $isRecipe,
        public readonly ?int $taxGroup,
        public readonly ?bool $isVeryUsed,
        public readonly ?int $groupId,
        public readonly ?bool $deleted,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            code: isset($data['code']) ? (string) $data['code'] : null,
            name: isset($data['name']) ? (string) $data['name'] : null,
            name2: isset($data['name2']) ? (string) $data['name2'] : null,
            barcode1: isset($data['barcode1']) ? (string) $data['barcode1'] : null,
            barcode2: isset($data['barcode2']) ? (string) $data['barcode2'] : null,
            barcode3: isset($data['barcode3']) ? (string) $data['barcode3'] : null,
            catalog1: isset($data['catalog1']) ? (string) $data['catalog1'] : null,
            catalog2: isset($data['catalog2']) ? (string) $data['catalog2'] : null,
            catalog3: isset($data['catalog3']) ? (string) $data['catalog3'] : null,
            measure1: isset($data['measure1']) ? (string) $data['measure1'] : null,
            measure2: isset($data['measure2']) ? (string) $data['measure2'] : null,
            ratio: isset($data['ratio']) ? (float) $data['ratio'] : null,
            priceIn: isset($data['price_in']) ? (float) $data['price_in'] : null,
            priceOut1: isset($data['price_out1']) ? (float) $data['price_out1'] : null,
            priceOut2: isset($data['price_out2']) ? (float) $data['price_out2'] : null,
            priceOut3: isset($data['price_out3']) ? (float) $data['price_out3'] : null,
            priceOut4: isset($data['price_out4']) ? (float) $data['price_out4'] : null,
            priceOut5: isset($data['price_out5']) ? (float) $data['price_out5'] : null,
            priceOut6: isset($data['price_out6']) ? (float) $data['price_out6'] : null,
            priceOut7: isset($data['price_out7']) ? (float) $data['price_out7'] : null,
            priceOut8: isset($data['price_out8']) ? (float) $data['price_out8'] : null,
            priceOut9: isset($data['price_out9']) ? (float) $data['price_out9'] : null,
            priceOut10: isset($data['price_out10']) ? (float) $data['price_out10'] : null,
            minQtty: isset($data['min_qtty']) ? (float) $data['min_qtty'] : null,
            normalQtty: isset($data['normal_qtty']) ? (float) $data['normal_qtty'] : null,
            description: isset($data['description']) ? (string) $data['description'] : null,
            type: isset($data['type']) ? (int) $data['type'] : null,
            isRecipe: isset($data['is_recipe']) ? (bool) $data['is_recipe'] : null,
            taxGroup: isset($data['tax_group']) ? (int) $data['tax_group'] : null,
            isVeryUsed: isset($data['is_very_used']) ? (bool) $data['is_very_used'] : null,
            groupId: isset($data['group_id']) ? (int) $data['group_id'] : null,
            deleted: isset($data['deleted']) ? (bool) $data['deleted'] : null,
        );
    }
}
