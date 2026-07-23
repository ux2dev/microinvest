<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Dto\Result\Partners;

use Ux2Dev\Microinvest\Contracts\Dto\FromWarehousePro;

/**
 * A partner row (table partners).
 */
final class PartnerResult implements FromWarehousePro
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
        public readonly ?int $type,
        public readonly ?bool $isVeryUsed,
        public readonly ?int $userId,
        public readonly ?int $groupId,
        public readonly ?string $userRealTime,
        public readonly ?bool $deleted,
        public readonly ?string $cardNumber,
        public readonly ?string $note1,
        public readonly ?string $note2,
        public readonly ?int $paymentDays,
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
            type: isset($data['type']) ? (int) $data['type'] : null,
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
}
