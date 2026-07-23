<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;
use Ux2Dev\Microinvest\Contracts\Dto\ToMicroBg;
use Ux2Dev\Microinvest\Dto\Input\Items\ItemInput;
use Ux2Dev\Microinvest\Dto\Input\Partners\PartnerInput;
use Ux2Dev\Microinvest\Dto\Result\Items\ItemAddCodeResult;
use Ux2Dev\Microinvest\Dto\Result\Items\ItemResult;
use Ux2Dev\Microinvest\Dto\Result\Locations\LocationResult;
use Ux2Dev\Microinvest\Dto\Result\NomenclatureGroupResult;
use Ux2Dev\Microinvest\Dto\Result\Partners\PartnerResult;
use Ux2Dev\Microinvest\Dto\Result\Payments\PaymentTypeResult;
use Ux2Dev\Microinvest\Dto\Result\Store\StoreResult;
use Ux2Dev\Microinvest\Dto\Result\VatGroups\VatGroupResult;

it('hydrates a partner from the micro.bg dialect', function () {
    // Verbatim from Api_v1.4.pdf, section 3.1.
    $p = PartnerResult::fromMicroBg([
        'id' => 17, 'Code' => 102, 'Name' => 'АКЦЕНТ-2006 - ЕООД', 'MOL' => 'Иван Стоименов Тошев',
        'City' => 'САНДАНСКИ', 'Address' => 'ул. СТАНКЕ ДИМИТРОВ №7', 'Phone' => '+359 883115782',
        'eMail' => '', 'TaxID' => '101744907', 'VatID' => 'BG101744907', 'PriceGroup' => 2,
        'CardNumber' => '9690017239039', 'Discount' => 0.0, 'PartnerType' => 1,
        'GroupName' => 'Складове', 'GroupPath' => '-1', 'ContactPerson' => '', 'PartnerNote' => '',
        'GroupId' => 38, 'Deleted' => 0, 'DateUpdated' => '2016-07-29 12:09:15',
    ]);

    expect($p)->toBeInstanceOf(FromMicroBg::class)
        ->and($p->id)->toBe(17)
        ->and($p->code)->toBe('102')
        ->and($p->company)->toBe('АКЦЕНТ-2006 - ЕООД')
        ->and($p->mol)->toBe('Иван Стоименов Тошев')
        ->and($p->email)->toBe('')
        ->and($p->taxId)->toBe('101744907')
        ->and($p->vatId)->toBe('BG101744907')
        ->and($p->priceGroup)->toBe(2)
        ->and($p->type)->toBe(1)
        ->and($p->groupId)->toBe(38)
        ->and($p->groupPath)->toBe('-1')
        ->and($p->dateUpdated)->toBe('2016-07-29 12:09:15')
        ->and($p->deleted)->toBeFalse()
        // Warehouse Pro only - absent from this dialect.
        ->and($p->bankName)->toBeNull()
        ->and($p->paymentDays)->toBeNull()
        ->and($p->company2)->toBeNull();
});

it('hydrates an item including its additional codes', function () {
    // Verbatim from Api_v1.4.pdf, section 4.1.
    $i = ItemResult::fromMicroBg([
        'id' => 2, 'GroupName' => 'Алкохол', 'GroupPath' => 'ААНААВ', 'GroupId' => 1726,
        'Code' => 56, 'Barcode' => '54491472', 'Name' => "Водка Мери Джейн's 0.5",
        'NotStorable' => 0, 'TaxGroup' => 1, 'TaxValue' => 20, 'MeasureName' => 'Бр.', 'MeasureId' => 6,
        'PriceIn' => 13.0195308, 'PriceOut1' => 19.00, 'PriceOut2' => 35.00, 'PriceOut10' => 0.0,
        'Deleted' => 0, 'Description' => '', 'WarrantyMonths' => 0, 'WarrantyDays' => 0,
        'DateUpdated' => '2018-06-28 11:08:54',
        'AddCodes' => [['MeasureId' => 108, 'Code' => 7777, 'CodeType' => 1, 'Ratio' => 6]],
    ]);

    expect($i->id)->toBe(2)
        ->and($i->code)->toBe('56')
        ->and($i->barcode1)->toBe('54491472')
        ->and($i->measure1)->toBe('Бр.')
        ->and($i->measureId)->toBe(6)
        ->and($i->taxGroup)->toBe(1)
        ->and($i->taxValue)->toBe(20.0)
        ->and($i->priceIn)->toBe(13.0195308)
        ->and($i->priceOut2)->toBe(35.0)
        ->and($i->notStorable)->toBeFalse()
        ->and($i->groupPath)->toBe('ААНААВ')
        ->and($i->addCodes)->toHaveCount(1)
        ->and($i->addCodes[0])->toBeInstanceOf(ItemAddCodeResult::class)
        ->and($i->addCodes[0]->measureId)->toBe(108)
        ->and($i->addCodes[0]->code)->toBe('7777')
        ->and($i->addCodes[0]->codeType)->toBe(1)
        ->and($i->addCodes[0]->ratio)->toBe(6.0)
        // Warehouse Pro only.
        ->and($i->catalog1)->toBeNull()
        ->and($i->minQtty)->toBeNull();
});

it('defaults addCodes to an empty list', function () {
    expect(ItemResult::fromMicroBg(['id' => 3])->addCodes)->toBe([])
        ->and(ItemResult::fromMicroBg(['id' => 3, 'AddCodes' => ['not an object']])->addCodes)->toBe([]);
});

it('hydrates the group tree', function () {
    $g = NomenclatureGroupResult::fromMicroBg(['id' => 1733, 'Name' => 'Вина', 'Path' => 'ААА', 'parentId' => 0]);

    expect($g->id)->toBe(1733)
        ->and($g->name)->toBe('Вина')
        ->and($g->path)->toBe('ААА')
        ->and($g->parentId)->toBe(0)
        ->and($g->code)->toBeNull();
});

it('hydrates the lookups', function () {
    $vat = VatGroupResult::fromMicroBg(['TaxGroup' => 2, 'Name' => 'ДДС(Г) 9%', 'TaxValue' => 9]);
    $payment = PaymentTypeResult::fromMicroBg(['id' => 3, 'Name' => 'С карта', 'PaymentMethod' => 3, 'FiscalMode' => 2, 'Deleted' => 0]);
    $object = LocationResult::fromMicroBg(['id' => 2, 'Name' => 'Склад', 'Address' => 'София', 'PriceGroup' => 1, 'Deleted' => 0]);
    $qty = StoreResult::fromMicroBg(['ItemId' => 8681, 'Qtty' => -62.0]);

    expect($vat->id)->toBe(2)
        ->and($vat->name)->toBe('ДДС(Г) 9%')
        ->and($vat->vatValue)->toBe(9.0)
        ->and($payment->id)->toBe(3)
        ->and($payment->paymentMethod)->toBe(3)
        ->and($payment->fiscalMode)->toBe(2)
        ->and($payment->deleted)->toBeFalse()
        ->and($object->id)->toBe(2)
        ->and($object->name)->toBe('Склад')
        ->and($object->address)->toBe('София')
        ->and($object->priceGroup)->toBe(1)
        ->and($qty->goodId)->toBe('8681')
        ->and($qty->qtty)->toBe(-62.0);
});

it('serialises a partner to the micro.bg dialect', function () {
    $input = new PartnerInput(
        company: 'МИКРОСИСТЕМИ ООД',
        city: 'СОФИЯ',
        taxId: '831826092',
        priceGroup: 2,
        type: 1,
        contactPerson: 'Иван',
    );

    expect($input)->toBeInstanceOf(ToMicroBg::class)
        ->and($input->toMicroBgArray())->toBe([
            'Name' => 'МИКРОСИСТЕМИ ООД',
            'City' => 'СОФИЯ',
            'TaxID' => '831826092',
            'PriceGroup' => 2,
            'PartnerType' => 1,
            'ContactPerson' => 'Иван',
        ]);
});

it('drops Warehouse Pro only fields when serialising for micro.bg', function () {
    $input = new PartnerInput(company: 'ACME', bankName: 'ПИБ', fax: '02', paymentDays: 14);

    expect($input->toMicroBgArray())->toBe(['Name' => 'ACME'])
        ->and($input->toWarehouseProArray())
        ->toBe(['company' => 'ACME', 'fax' => '02', 'bank_name' => 'ПИБ', 'payment_days' => 14]);
});

it('serialises an item to the micro.bg dialect', function () {
    $input = new ItemInput(
        code: '66880',
        name: 'формуляр Стокова разписка',
        barcode1: '5204533130138',
        priceOut1: 2.80,
        taxGroup: 1,
        groupId: 38,
        measureId: 1,
    );

    expect($input->toMicroBgArray())->toBe([
        'Code' => '66880',
        'Name' => 'формуляр Стокова разписка',
        'Barcode' => '5204533130138',
        'TaxGroup' => 1,
        'GroupId' => 38,
        'MeasureId' => 1,
        'PriceOut1' => 2.80,
    ]);
});

it('keeps the Warehouse Pro dialect working', function () {
    expect(PartnerResult::fromWarehousePro(['id' => 1, 'company' => 'X'])->company)->toBe('X')
        ->and(ItemResult::fromWarehousePro(['id' => 1, 'name' => 'X'])->name)->toBe('X')
        ->and((new PartnerInput(company: 'X'))->toWarehouseProArray())->toBe(['company' => 'X'])
        ->and((new ItemInput(name: 'X'))->toWarehouseProArray())->toBe(['name' => 'X']);
});

it('marks every dialect-aware Result DTO with both interfaces', function (string $class) {
    expect(is_a($class, FromMicroBg::class, allow_string: true))->toBeTrue();
})->with([
    PartnerResult::class,
    ItemResult::class,
    ItemAddCodeResult::class,
    NomenclatureGroupResult::class,
    VatGroupResult::class,
    PaymentTypeResult::class,
    LocationResult::class,
    StoreResult::class,
]);
