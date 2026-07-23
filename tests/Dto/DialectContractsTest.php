<?php

declare(strict_types=1);

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;
use Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro;
use Ux2Dev\Microinvest\Contracts\Dto\ToWarehousePro;
use Ux2Dev\Microinvest\Dto\Input\Partners\PartnerInput;
use Ux2Dev\Microinvest\Dto\Result\Partners\PartnerResult;

it('hydrates a partner from a Warehouse Pro row', function () {
    $p = PartnerResult::fromWarehousePro(['id' => 7, 'company' => 'ACME', 'tax_id' => '831826092']);

    expect($p)->toBeInstanceOf(FromWarehousePro::class)
        ->and($p->id)->toBe(7)
        ->and($p->company)->toBe('ACME')
        ->and($p->taxId)->toBe('831826092');
});

it('serialises a partner to Warehouse Pro wire keys', function () {
    $input = new PartnerInput(company: 'ACME', taxId: '831826092', priceGroup: 2);

    expect($input)->toBeInstanceOf(ToWarehousePro::class)
        ->and($input->toWarehouseProArray())->toBe([
            'company' => 'ACME',
            'tax_id' => '831826092',
            'price_group' => 2,
        ]);
});

it('marks every Result DTO as hydratable from at least one dialect', function (string $file) {
    $class = 'Ux2Dev\\Microinvest\\Dto\\Result\\' . str_replace('/', '\\', substr($file, 0, -4));

    $dialects = array_filter([
        FromWarehousePro::class,
        FromMicroBg::class,
    ], static fn (string $i): bool => is_a($class, $i, allow_string: true));

    // A Result DTO that no transport can build is dead code.
    expect($dialects)->not->toBeEmpty();
})->with(function () {
    $base = __DIR__ . '/../../src/Dto/Result/';
    $files = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $f) {
        if ($f->getExtension() === 'php') {
            $files[] = str_replace($base, '', $f->getPathname());
        }
    }

    sort($files);

    return $files;
});
