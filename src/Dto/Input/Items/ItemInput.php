<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Input\Items;

/**
 * Input DTO for creating (POST /Item) or updating (PUT /Item) an item.
 * Only non-null properties are sent on the wire.
 */
final readonly class ItemInput
{
    public function __construct(
        public ?int $id = null,
        public ?string $code = null,
        public ?string $name = null,
        public ?string $name2 = null,
        public ?string $barcode1 = null,
        public ?string $barcode2 = null,
        public ?string $barcode3 = null,
        public ?string $catalog1 = null,
        public ?string $catalog2 = null,
        public ?string $catalog3 = null,
        public ?string $measure1 = null,
        public ?string $measure2 = null,
        public ?float $ratio = null,
        public ?float $priceIn = null,
        public ?float $priceOut1 = null,
        public ?float $priceOut2 = null,
        public ?float $priceOut3 = null,
        public ?float $priceOut4 = null,
        public ?float $priceOut5 = null,
        public ?float $priceOut6 = null,
        public ?float $priceOut7 = null,
        public ?float $priceOut8 = null,
        public ?float $priceOut9 = null,
        public ?float $priceOut10 = null,
        public ?float $minQtty = null,
        public ?float $normalQtty = null,
        public ?string $description = null,
        public ?int $type = null,
        public ?bool $isRecipe = null,
        public ?int $taxGroup = null,
        public ?bool $isVeryUsed = null,
        public ?int $groupId = null,
        public ?bool $deleted = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        $out = [];
        if ($this->id !== null) $out['id'] = $this->id;
        if ($this->code !== null) $out['code'] = $this->code;
        if ($this->name !== null) $out['name'] = $this->name;
        if ($this->name2 !== null) $out['name2'] = $this->name2;
        if ($this->barcode1 !== null) $out['barcode1'] = $this->barcode1;
        if ($this->barcode2 !== null) $out['barcode2'] = $this->barcode2;
        if ($this->barcode3 !== null) $out['barcode3'] = $this->barcode3;
        if ($this->catalog1 !== null) $out['catalog1'] = $this->catalog1;
        if ($this->catalog2 !== null) $out['catalog2'] = $this->catalog2;
        if ($this->catalog3 !== null) $out['catalog3'] = $this->catalog3;
        if ($this->measure1 !== null) $out['measure1'] = $this->measure1;
        if ($this->measure2 !== null) $out['measure2'] = $this->measure2;
        if ($this->ratio !== null) $out['ratio'] = $this->ratio;
        if ($this->priceIn !== null) $out['price_in'] = $this->priceIn;
        if ($this->priceOut1 !== null) $out['price_out1'] = $this->priceOut1;
        if ($this->priceOut2 !== null) $out['price_out2'] = $this->priceOut2;
        if ($this->priceOut3 !== null) $out['price_out3'] = $this->priceOut3;
        if ($this->priceOut4 !== null) $out['price_out4'] = $this->priceOut4;
        if ($this->priceOut5 !== null) $out['price_out5'] = $this->priceOut5;
        if ($this->priceOut6 !== null) $out['price_out6'] = $this->priceOut6;
        if ($this->priceOut7 !== null) $out['price_out7'] = $this->priceOut7;
        if ($this->priceOut8 !== null) $out['price_out8'] = $this->priceOut8;
        if ($this->priceOut9 !== null) $out['price_out9'] = $this->priceOut9;
        if ($this->priceOut10 !== null) $out['price_out10'] = $this->priceOut10;
        if ($this->minQtty !== null) $out['min_qtty'] = $this->minQtty;
        if ($this->normalQtty !== null) $out['normal_qtty'] = $this->normalQtty;
        if ($this->description !== null) $out['description'] = $this->description;
        if ($this->type !== null) $out['type'] = $this->type;
        if ($this->isRecipe !== null) $out['is_recipe'] = $this->isRecipe;
        if ($this->taxGroup !== null) $out['tax_group'] = $this->taxGroup;
        if ($this->isVeryUsed !== null) $out['is_very_used'] = $this->isVeryUsed;
        if ($this->groupId !== null) $out['group_id'] = $this->groupId;
        if ($this->deleted !== null) $out['deleted'] = $this->deleted;
        return $out;
    }
}
