<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\MicroBg\Resources;

use Ux2Dev\Microinvest\Dto\Result\Company\BankAccountResult;
use Ux2Dev\Microinvest\Dto\Result\Company\CompanyResult;
use Ux2Dev\Microinvest\Http\ResultList;

/**
 * The account's own details and the settings an integration has to respect.
 * micro.bg only.
 */
final class Company extends Resource
{
    public function get(): CompanyResult
    {
        return $this->transport->callOne('getCompanyData', [], null, CompanyResult::class);
    }

    /** @return ResultList<BankAccountResult> */
    public function bankAccounts(): ResultList
    {
        return $this->transport->callList('getBankAccounts', [], BankAccountResult::class);
    }
}
