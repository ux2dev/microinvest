<?php

declare(strict_types=1);

namespace Ux2Dev\Microinvest\Contracts;

use Ux2Dev\Microinvest\Dto\Input\Partners\PartnerInput;
use Ux2Dev\Microinvest\Dto\Result\Partners\PartnerResult;

/**
 * The partner operations both backends support. Backend-specific filtering
 * and paging stay on the concrete resource classes.
 */
interface PartnerRepository
{
    public function get(int $id): PartnerResult;

    public function create(PartnerInput $input): PartnerResult;

    public function update(int $id, PartnerInput $input): PartnerResult;

    /**
     * Every partner, transparently walking whatever paging model the backend
     * uses. Lazy: rows are fetched one page at a time.
     *
     * @return iterable<PartnerResult>
     */
    public function each(): iterable;
}
