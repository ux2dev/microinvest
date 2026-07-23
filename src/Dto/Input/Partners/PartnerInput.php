<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Input\Partners;

use Ux2Dev\Microinvest\Contracts\Dto\ToWarehousePro;

/**
 * Input DTO for creating (POST /Partner) or updating (PUT /Partner) a partner.
 * Only non-null properties are sent on the wire.
 */
final readonly class PartnerInput implements ToWarehousePro
{
    public function __construct(
        public ?int $id = null,
        public ?string $code = null,
        public ?string $company = null,
        public ?string $company2 = null,
        public ?string $mol = null,
        public ?string $mol2 = null,
        public ?string $city = null,
        public ?string $city2 = null,
        public ?string $address = null,
        public ?string $address2 = null,
        public ?string $phone = null,
        public ?string $phone2 = null,
        public ?string $fax = null,
        public ?string $email = null,
        public ?string $taxId = null,
        public ?string $vatId = null,
        public ?string $bankName = null,
        public ?string $bankCode = null,
        public ?string $bankAcct = null,
        public ?string $bankVatName = null,
        public ?string $bankVatCode = null,
        public ?string $bankVatAcct = null,
        public ?int $priceGroup = null,
        public ?float $discount = null,
        public ?int $type = null,
        public ?bool $isVeryUsed = null,
        public ?int $userId = null,
        public ?int $groupId = null,
        public ?bool $deleted = null,
        public ?string $cardNumber = null,
        public ?string $note1 = null,
        public ?string $note2 = null,
        public ?int $paymentDays = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toWarehouseProArray(): array
    {
        $out = [];
        if ($this->id !== null) $out['id'] = $this->id;
        if ($this->code !== null) $out['code'] = $this->code;
        if ($this->company !== null) $out['company'] = $this->company;
        if ($this->company2 !== null) $out['company2'] = $this->company2;
        if ($this->mol !== null) $out['mol'] = $this->mol;
        if ($this->mol2 !== null) $out['mol2'] = $this->mol2;
        if ($this->city !== null) $out['city'] = $this->city;
        if ($this->city2 !== null) $out['city2'] = $this->city2;
        if ($this->address !== null) $out['address'] = $this->address;
        if ($this->address2 !== null) $out['address2'] = $this->address2;
        if ($this->phone !== null) $out['phone'] = $this->phone;
        if ($this->phone2 !== null) $out['phone2'] = $this->phone2;
        if ($this->fax !== null) $out['fax'] = $this->fax;
        if ($this->email !== null) $out['email'] = $this->email;
        if ($this->taxId !== null) $out['tax_id'] = $this->taxId;
        if ($this->vatId !== null) $out['vat_id'] = $this->vatId;
        if ($this->bankName !== null) $out['bank_name'] = $this->bankName;
        if ($this->bankCode !== null) $out['bank_code'] = $this->bankCode;
        if ($this->bankAcct !== null) $out['bank_acct'] = $this->bankAcct;
        if ($this->bankVatName !== null) $out['bank_vat_name'] = $this->bankVatName;
        if ($this->bankVatCode !== null) $out['bank_vat_code'] = $this->bankVatCode;
        if ($this->bankVatAcct !== null) $out['bank_vat_acct'] = $this->bankVatAcct;
        if ($this->priceGroup !== null) $out['price_group'] = $this->priceGroup;
        if ($this->discount !== null) $out['discount'] = $this->discount;
        if ($this->type !== null) $out['type'] = $this->type;
        if ($this->isVeryUsed !== null) $out['is_very_used'] = $this->isVeryUsed;
        if ($this->userId !== null) $out['user_id'] = $this->userId;
        if ($this->groupId !== null) $out['group_id'] = $this->groupId;
        if ($this->deleted !== null) $out['deleted'] = $this->deleted;
        if ($this->cardNumber !== null) $out['card_number'] = $this->cardNumber;
        if ($this->note1 !== null) $out['note1'] = $this->note1;
        if ($this->note2 !== null) $out['note2'] = $this->note2;
        if ($this->paymentDays !== null) $out['payment_days'] = $this->paymentDays;
        return $out;
    }
}
