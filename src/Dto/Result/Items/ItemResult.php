<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Items;

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;
use Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro;

/**
 * An item row (table goods), as returned by either backend.
 *
 * The first block of properties is common to both; the rest are dialect
 * specific and stay null when the other backend answered.
 */
final class ItemResult implements FromWarehousePro, FromMicroBg
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
        /** micro.bg only: the item is a service rather than stock. */
        public readonly ?bool $notStorable = null,
        /** micro.bg only: the VAT percentage of the item's tax group. */
        public readonly ?float $taxValue = null,
        /** micro.bg only: id of the base measure, needed by insertItem. */
        public readonly ?int $measureId = null,
        /** micro.bg only. */
        public readonly ?string $groupName = null,
        /** micro.bg only: tree path of the item's group. */
        public readonly ?string $groupPath = null,
        /** micro.bg only. */
        public readonly ?int $warrantyMonths = null,
        /** micro.bg only. */
        public readonly ?int $warrantyDays = null,
        /** micro.bg only: 'Y-m-d H:i:s' of the last create/modify. */
        public readonly ?string $dateUpdated = null,
        /**
         * micro.bg only: extra codes and barcodes for this item.
         *
         * @var list<ItemAddCodeResult>
         */
        public readonly array $addCodes = [],
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromWarehousePro(array $data): static
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

    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            code: isset($data['Code']) ? (string) $data['Code'] : null,
            name: isset($data['Name']) ? (string) $data['Name'] : null,
            name2: null,
            barcode1: isset($data['Barcode']) ? (string) $data['Barcode'] : null,
            barcode2: null,
            barcode3: null,
            catalog1: null,
            catalog2: null,
            catalog3: null,
            measure1: isset($data['MeasureName']) ? (string) $data['MeasureName'] : null,
            measure2: null,
            ratio: null,
            priceIn: isset($data['PriceIn']) ? (float) $data['PriceIn'] : null,
            priceOut1: isset($data['PriceOut1']) ? (float) $data['PriceOut1'] : null,
            priceOut2: isset($data['PriceOut2']) ? (float) $data['PriceOut2'] : null,
            priceOut3: isset($data['PriceOut3']) ? (float) $data['PriceOut3'] : null,
            priceOut4: isset($data['PriceOut4']) ? (float) $data['PriceOut4'] : null,
            priceOut5: isset($data['PriceOut5']) ? (float) $data['PriceOut5'] : null,
            priceOut6: isset($data['PriceOut6']) ? (float) $data['PriceOut6'] : null,
            priceOut7: isset($data['PriceOut7']) ? (float) $data['PriceOut7'] : null,
            priceOut8: isset($data['PriceOut8']) ? (float) $data['PriceOut8'] : null,
            priceOut9: isset($data['PriceOut9']) ? (float) $data['PriceOut9'] : null,
            priceOut10: isset($data['PriceOut10']) ? (float) $data['PriceOut10'] : null,
            minQtty: null,
            normalQtty: null,
            description: isset($data['Description']) ? (string) $data['Description'] : null,
            type: null,
            isRecipe: null,
            taxGroup: isset($data['TaxGroup']) ? (int) $data['TaxGroup'] : null,
            isVeryUsed: null,
            groupId: isset($data['GroupId']) ? (int) $data['GroupId'] : null,
            deleted: isset($data['Deleted']) ? (bool) $data['Deleted'] : null,
            notStorable: isset($data['NotStorable']) ? (bool) $data['NotStorable'] : null,
            taxValue: isset($data['TaxValue']) ? (float) $data['TaxValue'] : null,
            measureId: isset($data['MeasureId']) ? (int) $data['MeasureId'] : null,
            groupName: isset($data['GroupName']) ? (string) $data['GroupName'] : null,
            groupPath: isset($data['GroupPath']) ? (string) $data['GroupPath'] : null,
            warrantyMonths: isset($data['WarrantyMonths']) ? (int) $data['WarrantyMonths'] : null,
            warrantyDays: isset($data['WarrantyDays']) ? (int) $data['WarrantyDays'] : null,
            dateUpdated: isset($data['DateUpdated']) ? (string) $data['DateUpdated'] : null,
            addCodes: array_map(
                static fn (array $row): ItemAddCodeResult => ItemAddCodeResult::fromMicroBg($row),
                array_values(array_filter((array) ($data['AddCodes'] ?? []), 'is_array')),
            ),
        );
    }
}
