<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Partners;

use Ux2Dev\Microinvest\Contracts\Dto\FromMicroBg;
use Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro;
use Ux2Dev\Microinvest\Enum\PartnerType;

/**
 * A partner row (table partners), as returned by either backend.
 *
 * The first block of properties is common to both; the rest are dialect
 * specific and stay null when the other backend answered.
 */
final class PartnerResult implements FromWarehousePro, FromMicroBg
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $code,
        public readonly ?string $company,
        public readonly ?string $company2,
        public readonly ?string $mol,
        public readonly ?string $mol2,
        public readonly ?string $city,
        public readonly ?string $city2,
        public readonly ?string $address,
        public readonly ?string $address2,
        public readonly ?string $phone,
        public readonly ?string $phone2,
        public readonly ?string $fax,
        public readonly ?string $email,
        public readonly ?string $taxId,
        public readonly ?string $vatId,
        public readonly ?string $bankName,
        public readonly ?string $bankCode,
        public readonly ?string $bankAcct,
        public readonly ?string $bankVatName,
        public readonly ?string $bankVatCode,
        public readonly ?string $bankVatAcct,
        public readonly ?int $priceGroup,
        public readonly ?float $discount,
        public readonly ?PartnerType $type,
        public readonly ?bool $isVeryUsed,
        public readonly ?int $userId,
        public readonly ?int $groupId,
        public readonly ?string $userRealTime,
        public readonly ?bool $deleted,
        public readonly ?string $cardNumber,
        public readonly ?string $note1,
        public readonly ?string $note2,
        public readonly ?int $paymentDays,
        /** micro.bg only. */
        public readonly ?string $contactPerson = null,
        /** micro.bg only. */
        public readonly ?string $partnerNote = null,
        /** micro.bg only. */
        public readonly ?string $groupName = null,
        /** micro.bg only: tree path of the partner's group, '-1' for the service group. */
        public readonly ?string $groupPath = null,
        /** micro.bg only: 'Y-m-d H:i:s' of the last create/modify. */
        public readonly ?string $dateUpdated = null,
    ) {
    }

    /** @param array<string, mixed> $data */
    public static function fromWarehousePro(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            code: isset($data['code']) ? (string) $data['code'] : null,
            company: isset($data['company']) ? (string) $data['company'] : null,
            company2: isset($data['company2']) ? (string) $data['company2'] : null,
            mol: isset($data['mol']) ? (string) $data['mol'] : null,
            mol2: isset($data['mol2']) ? (string) $data['mol2'] : null,
            city: isset($data['city']) ? (string) $data['city'] : null,
            city2: isset($data['city2']) ? (string) $data['city2'] : null,
            address: isset($data['address']) ? (string) $data['address'] : null,
            address2: isset($data['address2']) ? (string) $data['address2'] : null,
            phone: isset($data['phone']) ? (string) $data['phone'] : null,
            phone2: isset($data['phone2']) ? (string) $data['phone2'] : null,
            fax: isset($data['fax']) ? (string) $data['fax'] : null,
            email: isset($data['email']) ? (string) $data['email'] : null,
            taxId: isset($data['tax_id']) ? (string) $data['tax_id'] : null,
            vatId: isset($data['vat_id']) ? (string) $data['vat_id'] : null,
            bankName: isset($data['bank_name']) ? (string) $data['bank_name'] : null,
            bankCode: isset($data['bank_code']) ? (string) $data['bank_code'] : null,
            bankAcct: isset($data['bank_acct']) ? (string) $data['bank_acct'] : null,
            bankVatName: isset($data['bank_vat_name']) ? (string) $data['bank_vat_name'] : null,
            bankVatCode: isset($data['bank_vat_code']) ? (string) $data['bank_vat_code'] : null,
            bankVatAcct: isset($data['bank_vat_acct']) ? (string) $data['bank_vat_acct'] : null,
            priceGroup: isset($data['price_group']) ? (int) $data['price_group'] : null,
            discount: isset($data['discount']) ? (float) $data['discount'] : null,
            type: isset($data['type']) ? PartnerType::tryFrom((int) $data['type']) : null,
            isVeryUsed: isset($data['is_very_used']) ? (bool) $data['is_very_used'] : null,
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            groupId: isset($data['group_id']) ? (int) $data['group_id'] : null,
            userRealTime: isset($data['user_real_time']) ? (string) $data['user_real_time'] : null,
            deleted: isset($data['deleted']) ? (bool) $data['deleted'] : null,
            cardNumber: isset($data['card_number']) ? (string) $data['card_number'] : null,
            note1: isset($data['note1']) ? (string) $data['note1'] : null,
            note2: isset($data['note2']) ? (string) $data['note2'] : null,
            paymentDays: isset($data['payment_days']) ? (int) $data['payment_days'] : null,
        );
    }

    /** @param array<string, mixed> $data */
    public static function fromMicroBg(array $data): static
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            code: isset($data['Code']) ? (string) $data['Code'] : null,
            company: isset($data['Name']) ? (string) $data['Name'] : null,
            company2: null,
            mol: isset($data['MOL']) ? (string) $data['MOL'] : null,
            mol2: null,
            city: isset($data['City']) ? (string) $data['City'] : null,
            city2: null,
            address: isset($data['Address']) ? (string) $data['Address'] : null,
            address2: null,
            phone: isset($data['Phone']) ? (string) $data['Phone'] : null,
            phone2: null,
            fax: null,
            email: isset($data['eMail']) ? (string) $data['eMail'] : null,
            taxId: isset($data['TaxID']) ? (string) $data['TaxID'] : null,
            vatId: isset($data['VatID']) ? (string) $data['VatID'] : null,
            bankName: null,
            bankCode: null,
            bankAcct: null,
            bankVatName: null,
            bankVatCode: null,
            bankVatAcct: null,
            priceGroup: isset($data['PriceGroup']) ? (int) $data['PriceGroup'] : null,
            discount: isset($data['Discount']) ? (float) $data['Discount'] : null,
            type: isset($data['PartnerType']) ? PartnerType::tryFrom((int) $data['PartnerType']) : null,
            isVeryUsed: null,
            userId: null,
            groupId: isset($data['GroupId']) ? (int) $data['GroupId'] : null,
            userRealTime: null,
            // PDF v1.4 documents Deleted as "1 - да, 2 - не" for partners but
            // "1 - да, 0 - не" for items. Treated as a plain boolean here;
            // a value of 2 would therefore read as deleted. Needs confirming
            // against a live account.
            deleted: isset($data['Deleted']) ? (bool) $data['Deleted'] : null,
            cardNumber: isset($data['CardNumber']) ? (string) $data['CardNumber'] : null,
            note1: null,
            note2: null,
            paymentDays: null,
            contactPerson: isset($data['ContactPerson']) ? (string) $data['ContactPerson'] : null,
            partnerNote: isset($data['PartnerNote']) ? (string) $data['PartnerNote'] : null,
            groupName: isset($data['GroupName']) ? (string) $data['GroupName'] : null,
            groupPath: isset($data['GroupPath']) ? (string) $data['GroupPath'] : null,
            dateUpdated: isset($data['DateUpdated']) ? (string) $data['DateUpdated'] : null,
        );
    }
}
